package hosting

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"

	"panelze/engine/internal/apache"
	"panelze/engine/internal/config"
	"panelze/engine/internal/nginx"
	"panelze/engine/internal/openlitespeed"
	"panelze/engine/internal/phpfpm"
	"panelze/engine/internal/sitecage"
	"panelze/engine/internal/sites"
	"panelze/engine/internal/ssl"
)

func poolSettings(cfg *config.Config) phpfpm.HostingPoolSettings {
	c := sitecage.FromHosting(cfg)
	ps := phpfpm.HostingPoolSettings{
		PoolDirTemplate: cfg.Hosting.PHPFPMpoolDirTemplate,
		SocketListenDir: cfg.Hosting.PHPFPMlistenDir,
		FPMUser:         cfg.Hosting.PHPFPMpoolUser,
		FPMGroup:        cfg.Hosting.PHPFPMpoolGroup,
	}
	if c.Enabled {
		if strings.TrimSpace(ps.FPMGroup) == "" || ps.FPMGroup == "www-data" {
			ps.FPMGroup = c.Group
		}
		ps.ListenOwner = c.EngineUser
		ps.ListenGroup = c.EngineUser
		ps.Helper = c.Helper
	}
	return ps
}

func serverTypeOf(meta *sites.SiteMeta) string {
	if meta == nil {
		return "nginx"
	}
	return sites.NormalizeServerType(meta.ServerType)
}

func resolvePHPSocket(cfg *config.Config, domain string, meta *sites.SiteMeta, explicit string) string {
	if strings.TrimSpace(explicit) != "" {
		return strings.TrimSpace(explicit)
	}
	v := "8.2"
	if meta != nil && strings.TrimSpace(meta.PHPVersion) != "" {
		v = meta.PHPVersion
	}
	if sitecage.ManagePools(cfg) {
		return poolSettings(cfg).SocketForDomain(domain)
	}
	return nginx.EffectivePHPSocket(v, cfg.Hosting.PHPFPMsocket)
}

func sslPathsIfEnabled(cfg *config.Config, domain string, meta *sites.SiteMeta) (chain, key string) {
	chain, key = ssl.LiveCertPaths(cfg, domain)
	if ssl.CertsExist(chain, key) {
		// Panel meta ile engine site.json senkron kayması olabilir; diskte sertifika varsa HTTPS vhost üret.
		if meta != nil && !meta.SSLEnabled {
			meta.SSLEnabled = true
			if meta.ForceHTTPS == nil {
				force := true
				meta.ForceHTTPS = &force
			}
			_ = sites.WriteSiteMeta(cfg.Paths.WebRoot, domain, meta)
		}
		return chain, key
	}
	if meta == nil || !meta.SSLEnabled {
		return "", ""
	}
	return chain, key
}

func nginxRedirectArgs(meta *sites.SiteMeta) (blocks, fullReturn string, err error) {
	if meta == nil || len(meta.RedirectRules) == 0 {
		return "", "", nil
	}
	render, err := sites.BuildNginxRedirectRender(meta.RedirectRules)
	if err != nil {
		return "", "", err
	}
	return render.Blocks, sites.FullSiteReturnDirective(render), nil
}

func serverNameExtras(meta *sites.SiteMeta) []string {
	if meta == nil {
		return nil
	}
	return append([]string(nil), meta.Aliases...)
}

func applyNginxSiteVhost(cfg *config.Config, domain, docRoot string, meta *sites.SiteMeta, phpSocket, chain, key string) error {
	docRoot = ResolveHTTPDocRoot(docRoot)
	extras := serverNameExtras(meta)
	sn := nginx.BuildServerNamesLine(domain, extras)
	perf := ""
	proxyPort := 0
	if meta != nil {
		perf = meta.PerformanceMode
		proxyPort = meta.NodeProxyPort()
	}
	redirBlocks, fullRet, rerr := nginxRedirectArgs(meta)
	if rerr != nil {
		return rerr
	}
	forceHTTPS := meta != nil && meta.ForceHTTPSRedirect()
	return nginx.ApplyVhost(cfg, domain, docRoot, phpSocket, chain, key, sn, perf, proxyPort, redirBlocks, fullRet, forceHTTPS)
}

func applyNginxEdgeProxyVhost(cfg *config.Config, domain, docRoot string, meta *sites.SiteMeta, phpSocket, chain, key string, backendPort int) error {
	docRoot = ResolveHTTPDocRoot(docRoot)
	extras := serverNameExtras(meta)
	sn := nginx.BuildServerNamesLine(domain, extras)
	redirBlocks, fullRet, rerr := nginxRedirectArgs(meta)
	if rerr != nil {
		return rerr
	}
	forceHTTPS := meta != nil && meta.ForceHTTPSRedirect()
	return nginx.ApplyVhost(cfg, domain, docRoot, phpSocket, chain, key, sn, "", backendPort, redirBlocks, fullRet, forceHTTPS)
}

// ApplyWebServer site meta’sına göre nginx (doğrudan veya edge proxy), apache veya openlitespeed yazar.
func ApplyWebServer(cfg *config.Config, domain, docRoot string, meta *sites.SiteMeta, phpSocket string) error {
	st := serverTypeOf(meta)
	edge := NginxEdgeProxyEnabled(cfg)
	sock := resolvePHPSocket(cfg, domain, meta, phpSocket)
	chain, key := sslPathsIfEnabled(cfg, domain, meta)

	if st != "nginx" && !edge {
		nginx.RemoveVhostBestEffort(cfg, domain)
	}
	if st != "apache" {
		apache.RemoveVhostBestEffort(cfg, domain)
	}
	if st != "openlitespeed" {
		openlitespeed.RemoveVhostBestEffort(cfg, domain)
	}

	docRoot = ResolveHTTPDocRoot(docRoot)

	switch st {
	case "apache":
		aliases := serverNameExtras(meta)
		if edge {
			if err := apache.ApplyBackendVhost(cfg, domain, docRoot, sock, aliases); err != nil {
				return err
			}
			return applyNginxEdgeProxyVhost(cfg, domain, docRoot, meta, sock, chain, key, ApacheBackendPort(cfg))
		}
		forceHTTPS := meta != nil && meta.ForceHTTPSRedirect()
		return apache.ApplyVhost(cfg, domain, docRoot, sock, chain, key, aliases, forceHTTPS)
	case "openlitespeed":
		aliases := serverNameExtras(meta)
		if edge {
			if err := openlitespeed.ApplyBackendVhost(cfg, domain, docRoot, sock, aliases); err != nil {
				return err
			}
			return applyNginxEdgeProxyVhost(cfg, domain, docRoot, meta, sock, chain, key, OLSBackendPort(cfg))
		}
		forceHTTPS := meta != nil && meta.ForceHTTPSRedirect()
		return openlitespeed.ApplyVhost(cfg, domain, docRoot, sock, chain, key, aliases, forceHTTPS)
	default:
		return applyNginxSiteVhost(cfg, domain, docRoot, meta, sock, chain, key)
	}
}

// ApplySubdomainVhost alt FQDN için kendi PHP sürümü/FPM havuzu ile sanal host yazar.
// Not: Eski davranış ana sitenin soketini paylaşıyordu; alt alan farklı PHP
// sürümü seçince (ör. firma → 8.4, parent → 8.2) Composer/Laravel 500 üretiyordu.
func ApplySubdomainVhost(cfg *config.Config, parentPrimary, hostname, docRoot string, subMeta *sites.SiteMeta) error {
	h := strings.ToLower(strings.TrimSpace(hostname))
	docRoot = ResolveHTTPDocRoot(docRoot)
	phpV := "8.2"
	if subMeta != nil && strings.TrimSpace(subMeta.PHPVersion) != "" {
		phpV = strings.TrimSpace(subMeta.PHPVersion)
	}

	sock := ""
	if sitecage.ManagePools(cfg) {
		opts := sitecage.PoolOptions(cfg, subMeta, h, docRoot)
		// Alt alan dosyaları genelde webRoot/parent/segment altında; BaseDir'i
		// docroot'tan türet (webRoot/hostname çoğu zaman yoktur).
		siteBase := filepath.Clean(docRoot)
		if filepath.Base(siteBase) == "public" {
			siteBase = filepath.Dir(siteBase)
		}
		opts.BaseDir = siteBase
		opts.ExtraBasedir = filepath.Join(siteBase, "tmp")
		// Cage kullanıcısı: alt meta'da yoksa parent ile aynı (dosyalar parent/segment altında).
		if subMeta == nil || strings.TrimSpace(subMeta.CageUser) == "" {
			parentMeta, _ := sites.ReadSiteMeta(cfg.Paths.WebRoot, parentPrimary)
			if parentMeta != nil && strings.TrimSpace(parentMeta.CageUser) != "" {
				opts.SiteUser = strings.TrimSpace(parentMeta.CageUser)
			} else if parentPrimary != "" {
				opts.SiteUser = sitecage.LinuxUserForDomain(sitecage.FromHosting(cfg).UserPrefix, parentPrimary)
			}
		}
		ps := poolSettings(cfg)
		s, _, _, err := phpfpm.WritePool(ps, h, phpV, docRoot, opts)
		if err != nil {
			return fmt.Errorf("subdomain fpm pool: %w", err)
		}
		sock = s
		if cage := sitecage.FromHosting(cfg); cage.Enabled {
			_ = sitecage.ApplyService(cage, h, phpV, 0, 0)
		}
	} else {
		sock = resolvePHPSocket(cfg, h, subMeta, "")
	}

	chain, key := sslPathsIfEnabled(cfg, h, subMeta)
	edge := NginxEdgeProxyEnabled(cfg)
	st := serverTypeOf(subMeta)

	switch st {
	case "apache":
		if edge {
			if err := apache.ApplyBackendVhost(cfg, h, docRoot, sock, nil); err != nil {
				return err
			}
			return applyNginxEdgeProxyVhost(cfg, h, docRoot, subMeta, sock, chain, key, ApacheBackendPort(cfg))
		}
		forceHTTPS := subMeta != nil && subMeta.ForceHTTPSRedirect()
		return apache.ApplyVhost(cfg, h, docRoot, sock, chain, key, nil, forceHTTPS)
	case "openlitespeed":
		if edge {
			if err := openlitespeed.ApplyBackendVhost(cfg, h, docRoot, sock, nil); err != nil {
				return err
			}
			return applyNginxEdgeProxyVhost(cfg, h, docRoot, subMeta, sock, chain, key, OLSBackendPort(cfg))
		}
		forceHTTPS := subMeta != nil && subMeta.ForceHTTPSRedirect()
		return openlitespeed.ApplyVhost(cfg, h, docRoot, sock, chain, key, nil, forceHTTPS)
	default:
		return applyNginxSiteVhost(cfg, h, docRoot, subMeta, sock, chain, key)
	}
}

// ApplySSLVhost Let's Encrypt / manuel SSL sonrası web sunucusu yapılandırmasını günceller.
func ApplySSLVhost(cfg *config.Config, parentPrimary, pathSegment, certHostname string, meta *sites.SiteMeta) error {
	if meta == nil {
		return nil
	}
	certHostname = strings.ToLower(strings.TrimSpace(certHostname))
	parentPrimary = strings.ToLower(strings.TrimSpace(parentPrimary))
	pathSegment = strings.TrimSpace(pathSegment)
	if pathSegment != "" && parentPrimary != "" {
		return ApplySubdomainVhost(cfg, parentPrimary, certHostname, meta.DocumentRoot, meta)
	}
	sock := resolvePHPSocket(cfg, certHostname, meta, "")
	return ApplyWebServer(cfg, certHostname, meta.DocumentRoot, meta, sock)
}

// RemoveWebServer meta’daki server_type’a göre ilgili vhost’u kaldırır.
func RemoveWebServer(cfg *config.Config, domain string, meta *sites.SiteMeta) error {
	st := serverTypeOf(meta)
	if NginxEdgeProxyEnabled(cfg) && st != "nginx" {
		nginx.RemoveVhostBestEffort(cfg, domain)
	}
	switch st {
	case "apache":
		return apache.RemoveVhost(cfg, domain)
	case "openlitespeed":
		return openlitespeed.RemoveVhost(cfg, domain)
	default:
		return nginx.RemoveVhost(cfg, domain)
	}
}

func removePanelSiteLogs(cfg *config.Config, domain string) {
	d := strings.ToLower(strings.TrimSpace(domain))
	if d == "" || strings.Contains(d, "..") {
		return
	}
	if err := os.MkdirAll(cfg.Paths.LogDir, 0o755); err != nil {
		return
	}
	base := filepath.Join(cfg.Paths.LogDir, d)
	_ = os.Remove(base + "_access.log")
	_ = os.Remove(base + "_error.log")
}

// RemoveWebServerForSiteDeletion ana siteyi sunucudan kaldırırken nginx, apache ve openlitespeed kalıntılarını temizler.
func RemoveWebServerForSiteDeletion(cfg *config.Config, domain string) {
	if domain == "" || strings.Contains(domain, "..") {
		return
	}
	nginx.RemoveVhostBestEffort(cfg, domain)
	apache.RemoveVhostBestEffort(cfg, domain)
	openlitespeed.RemoveVhostBestEffort(cfg, domain)
	removePanelSiteLogs(cfg, domain)
}

// RemoveSubdomainVhost alt FQDN için yazılmış sanal hostu kaldırır.
func RemoveSubdomainVhost(cfg *config.Config, hostname string, meta *sites.SiteMeta) error {
	h := strings.ToLower(strings.TrimSpace(hostname))
	if h == "" {
		return nil
	}
	st := serverTypeOf(meta)
	if NginxEdgeProxyEnabled(cfg) && st != "nginx" {
		nginx.RemoveVhostBestEffort(cfg, h)
	}
	switch st {
	case "apache":
		apache.RemoveVhostBestEffort(cfg, h)
	case "openlitespeed":
		openlitespeed.RemoveVhostBestEffort(cfg, h)
	default:
		nginx.RemoveVhostBestEffort(cfg, h)
	}
	removePanelSiteLogs(cfg, h)
	return nil
}
