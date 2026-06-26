package sites

import (
	"bytes"
	"encoding/json"
	"fmt"
	"html/template"
	"os"
	"path/filepath"
	"regexp"
	"strings"
)

var siteDomainRe = regexp.MustCompile(`^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$`)

// defaultSiteIndexTmpl yeni public_html index.html (Panelze karşılama sayfası).
var defaultSiteIndexTmpl = template.Must(template.New("default-site-index").Parse(`<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{.PrimaryLabel}} — Panelze</title>
<meta name="description" content="{{.Description}}">
<meta name="robots" content="noindex, follow">
<meta name="generator" content="Panelze Hosting">
<meta property="og:type" content="website">
<meta property="og:title" content="{{.PrimaryLabel}}">
<meta property="og:description" content="{{.Description}}">
<meta name="theme-color" content="#0f172a">
<style>
:root{--bg:#0f172a;--card:rgba(255,255,255,.06);--bd:rgba(148,163,184,.22);--text:#f1f5f9;--muted:#94a3b8;--brand1:#2563eb;--brand2:#06b6d4;--accent:#22c55e}
*,*::before,*::after{box-sizing:border-box}
html{font-size:16px;-webkit-text-size-adjust:100%}
body{margin:0;min-height:100vh;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Ubuntu,sans-serif;line-height:1.6;color:var(--text);background:var(--bg);background-image:radial-gradient(ellipse 120% 80% at 50% -20%,rgba(37,99,235,.35),transparent 55%),radial-gradient(ellipse 80% 50% at 100% 0%,rgba(6,182,212,.22),transparent 45%)}
main{max-width:38rem;margin:0 auto;padding:clamp(2rem,6vw,3.75rem) clamp(1.25rem,4vw,1.75rem) 3rem}
.header-badge{display:inline-flex;align-items:center;gap:.4rem;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--brand2);background:var(--card);border:1px solid var(--bd);padding:.35rem .75rem;border-radius:999px;margin-bottom:1.25rem}
.dot{width:.45rem;height:.45rem;background:var(--accent);border-radius:50%;box-shadow:0 0 0 3px rgba(34,197,94,.25);animation:pulse 2s ease-in-out infinite}
@keyframes pulse{50%{opacity:.55}}
h1{font-size:clamp(1.55rem,4.5vw,2rem);font-weight:700;margin:0 0 .5rem;line-height:1.25;background:linear-gradient(135deg,#fff,var(--muted));-webkit-background-clip:text;background-clip:text;color:transparent}
.lead{font-size:1rem;color:var(--muted);margin:0 0 1.75rem;max-width:36rem}
.card{background:var(--card);backdrop-filter:saturate(140%) blur(10px);border:1px solid var(--bd);border-radius:1rem;padding:clamp(1.15rem,3vw,1.5rem);margin-bottom:1.5rem}
.card h2{font-size:.85rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin:0 0 .75rem}
.steps{margin:0;padding-left:1.25rem;color:var(--muted);font-size:.95rem}
.steps li{margin-bottom:.5rem}
.steps li:last-child{margin-bottom:0}
.steps code{font-size:.88em;background:rgba(15,23,42,.5);padding:.1em .35em;border-radius:.25rem}
footer{margin-top:2rem;font-size:.8rem;color:var(--muted);text-align:center}
footer strong{color:#cbd5e1}
details{font-size:.85rem;color:var(--muted);cursor:pointer}
details summary{font-weight:500;color:#cbd5e1;outline-offset:4px;margin-bottom:.5rem}
details pre{margin:.5rem 0 0;white-space:pre-wrap;word-break:break-all;font-size:.75rem;line-height:1.45;color:#94a3b8;background:rgba(15,23,42,.65);padding:.75rem;border-radius:.5rem;border:1px solid var(--bd);overflow:auto}
.logo-row{display:flex;align-items:flex-start;gap:.65rem;margin-bottom:1rem}
.logo{width:44px;height:44px;border-radius:12px;background:#0f172a;border:1px solid var(--bd);flex-shrink:0;display:flex;align-items:center;justify-content:center}
.logo svg{width:26px;height:26px;display:block}
.php-badge{display:inline-block;font-variant-numeric:tabular-nums;font-weight:600;font-size:.82rem;color:var(--brand2);margin-top:.75rem;padding:.2rem .5rem;background:rgba(37,99,235,.18);border-radius:.35rem}
@media(prefers-reduced-motion:reduce){*{animation:none!important}}
</style>
</head>
<body>
<main id="icerik">
	<div class="logo-row">
		<div class="logo" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="presentation"><defs><linearGradient id="pz" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#2563eb"/><stop offset="100%" stop-color="#06b6d4"/></linearGradient></defs><rect width="64" height="64" rx="14" fill="#0f172a"/><path d="M16 46V18h13c8 0 13 5 13 11s-5 11-13 11h-5v6H16zm8-18h4c4 0 6 2 6 5s-2 5-6 5h-4v-10z" fill="url(#pz)"/><circle cx="49" cy="17" r="4" fill="#22c55e"/></svg></div>
		<div>
			<div class="header-badge"><span class="dot"></span>{{.Badge}}</div>
			<h1>{{.Subtitle}}</h1>
		</div>
	</div>
	{{if .IsSubdomain}}
	<p class="lead"><strong>{{.PrimaryLabel}}</strong> alt alanı için belge dizini hazır. İçeriği bu dizine yükleyerek veya panelden uygulama kurarak yayına geçebilirsiniz.</p>
	{{else}}
	<p class="lead"><strong>{{.PrimaryLabel}}</strong> için belge dizini oluşturuldu. Dosya yükleme, WordPress kurulumu veya kendi kodunuzu <code>public_html</code> altına taşımanız yeterli.</p>
	{{end}}
	<div class="card" aria-labelledby="adm-baslik">
		<h2 id="adm-baslik">Önerilen adımlar</h2>
		<ul class="steps">
			<li>Üretim için <strong>HTTPS</strong> (Let’s Encrypt) sertifikasını panelden tanımlayın.</li>
			<li>İçeriği <strong>FTP veya Dosya Yöneticisi</strong> ile <code>public_html</code> altına yükleyin.</li>
			<li>İhtiyaç halinde bir <strong>veritabanı</strong> oluşturup CMS kurulumunu tamamlayın.</li>
		</ul>
		<span class="php-badge" title="Bu alan için atanan PHP sürümü">PHP {{.PHPVersion}}</span>
	</div>
	<details>
		<summary>Teknik ayrıntılar (işletici)</summary>
		<pre>Belge kökü: {{.DocRoot}}
PHP (panel ayarı): {{.PHPVersion}}</pre>
	</details>
	<footer>
		Bu ortam <strong>Panelze</strong> ile yönetiliyor — modern Linux barındırma kontrol paneli.
	</footer>
</main>
<!--
Panelze provisioning metadata (doğrulama / destek)
domain_or_host={{.PrimaryLabel}}
document_root={{.DocRoot}}
php_version={{.PHPVersion}}
-->
</body>
</html>`))

type defaultIndexData struct {
	PrimaryLabel string
	IsSubdomain  bool
	Description  string
	DocRoot      string
	PHPVersion   string
	Badge        string
	Subtitle     string
}

// defaultSiteIndexHTML yeni domain / alt alan public_html için tek dosyalık karşılama sayfası.
// noindex: çok sayıda geçici sayfada ince içerik indeksini önler; canlı site dosyalarınızla değiştirirsiniz.
func defaultSiteIndexHTML(primaryLabel string, subdomain bool, docRoot, phpVersion string) []byte {
	var desc string
	if subdomain {
		desc = primaryLabel + " — Panelze üzerinden oluşturulmuş alt site. FTP veya dosya yöneticisi ile içerik yayınlamaya başlayabilirsiniz."
	} else {
		desc = primaryLabel + " — Bu alan adı Panelze barındırma paneli üzerinden yapılandırıldı. İçeriğinizi yükleyerek veya bir uygulama kurarak canlıya alabilirsiniz."
	}
	badge := "Alan adınız yayına hazır"
	subtitle := "Panelze ile barındırma alanınız aktif"
	if subdomain {
		badge = "Alt site hazır"
		subtitle = "Bu alt alan adı yayına hazır"
	}
	d := defaultIndexData{
		PrimaryLabel: primaryLabel,
		IsSubdomain:  subdomain,
		Description:  desc,
		DocRoot:      docRoot,
		PHPVersion:   phpVersion,
		Badge:        badge,
		Subtitle:     subtitle,
	}
	var buf bytes.Buffer
	if err := defaultSiteIndexTmpl.Execute(&buf, d); err != nil {
		return []byte("<!DOCTYPE html><html lang=\"tr\"><meta charset=\"utf-8\"><title>Panelze</title><body><p>Site hazır.</p></body></html>")
	}
	return buf.Bytes()
}

// docRootHasSiteContent mevcut müşteri dosyaları varsa placeholder yazılmamalı.
func docRootHasSiteContent(docRoot string) bool {
	entries, err := os.ReadDir(docRoot)
	if err != nil || len(entries) == 0 {
		return false
	}
	for _, e := range entries {
		name := e.Name()
		switch name {
		case "index.php", "artisan", "wp-config.php", "wp-load.php", "composer.json", "app", "vendor", "public", "wp-content", "wp-admin":
			return true
		case "index.html":
			if info, err := e.Info(); err == nil && info.Size() > 7000 {
				return true
			}
		default:
			if name != "." && name != ".." {
				return true
			}
		}
	}
	return false
}

func Provision(webRoot, domain, phpVersion, serverType string) (documentRoot string, err error) {
	domain = normalizeDomain(domain)
	if !isValidDomain(domain) {
		return "", fmt.Errorf("invalid domain")
	}
	if phpVersion == "" {
		phpVersion = "8.2"
	}
	oldMeta, _ := ReadSiteMeta(webRoot, domain)
	st := strings.ToLower(strings.TrimSpace(serverType))
	if st == "" && oldMeta != nil && oldMeta.ServerType != "" {
		st = strings.ToLower(strings.TrimSpace(oldMeta.ServerType))
	}
	st = NormalizeServerType(st)
	sslEn := false
	if oldMeta != nil {
		sslEn = oldMeta.SSLEnabled
	}
	docRoot := filepath.Join(webRoot, domain, "public_html")
	if oldMeta != nil && strings.TrimSpace(oldMeta.DocumentRoot) != "" {
		docRoot = filepath.Clean(oldMeta.DocumentRoot)
	}
	if err := os.MkdirAll(docRoot, 0o2775); err != nil {
		return "", err
	}
	if err := os.Chmod(docRoot, 0o2775); err != nil {
		return "", err
	}
	if oldMeta == nil && !docRootHasSiteContent(docRoot) {
		html := defaultSiteIndexHTML(domain, false, docRoot, phpVersion)
		index := filepath.Join(docRoot, "index.html")
		if err := os.WriteFile(index, html, 0o644); err != nil {
			return "", err
		}
	}
	meta := &SiteMeta{
		PHPVersion:   phpVersion,
		DocumentRoot: docRoot,
		ServerType:   st,
		SSLEnabled:   sslEn,
	}
	if oldMeta != nil {
		meta.Aliases = append([]string(nil), oldMeta.Aliases...)
		meta.ForceHTTPS = oldMeta.ForceHTTPS
		meta.PerformanceMode = oldMeta.PerformanceMode
		meta.AppProfile = oldMeta.AppProfile
		meta.NodeApp = oldMeta.NodeApp
		meta.RedirectRules = append([]RedirectRule(nil), oldMeta.RedirectRules...)
		meta.SSLEnabled = oldMeta.SSLEnabled
		meta.CageEnabled = oldMeta.CageEnabled
		meta.CageUser = oldMeta.CageUser
	}
	if err := WriteSiteMeta(webRoot, domain, meta); err != nil {
		return "", err
	}
	return docRoot, nil
}

func Remove(webRoot, domain string) error {
	domain = normalizeDomain(domain)
	if !isValidDomain(domain) {
		return fmt.Errorf("invalid domain")
	}
	return os.RemoveAll(filepath.Join(webRoot, domain))
}

// ProvisionSubdomain parent ör. example.com altında pathSegment ör. blog → webRoot/example.com/blog/public_html
func ProvisionSubdomain(webRoot, parentDomain, hostname, pathSegment, phpVersion, serverType string) (documentRoot string, err error) {
	parentDomain = strings.ToLower(strings.TrimSpace(parentDomain))
	hostname = strings.ToLower(strings.TrimSpace(hostname))
	pathSegment = strings.TrimSpace(pathSegment)
	if !isValidDomain(parentDomain) {
		return "", fmt.Errorf("invalid parent domain")
	}
	if !isValidDomain(hostname) {
		return "", fmt.Errorf("invalid hostname")
	}
	if pathSegment == "" || strings.Contains(pathSegment, "/") || strings.Contains(pathSegment, "..") {
		return "", fmt.Errorf("invalid path segment")
	}
	if phpVersion == "" {
		phpVersion = "8.2"
	}
	st := NormalizeServerType(serverType)
	base := filepath.Join(webRoot, parentDomain, pathSegment)
	docRoot := filepath.Join(base, "public_html")
	if err := os.MkdirAll(docRoot, 0o2775); err != nil {
		return "", err
	}
	if err := os.Chmod(docRoot, 0o2775); err != nil {
		return "", err
	}
	if !docRootHasSiteContent(docRoot) {
		html := defaultSiteIndexHTML(hostname, true, docRoot, phpVersion)
		if err := os.WriteFile(filepath.Join(docRoot, "index.html"), html, 0o644); err != nil {
			return "", err
		}
	}
	meta := &SiteMeta{
		Hostname:     hostname,
		PHPVersion:   phpVersion,
		DocumentRoot: docRoot,
		ServerType:   st,
		SSLEnabled:   false,
	}
	subMetaDir := filepath.Join(webRoot, parentDomain, ".panelze", "subdomains")
	if err := os.MkdirAll(subMetaDir, 0o750); err != nil {
		return "", err
	}
	metaPath := filepath.Join(subMetaDir, pathSegment+".json")
	b, err := json.MarshalIndent(meta, "", "  ")
	if err != nil {
		return "", err
	}
	if err := os.WriteFile(metaPath, b, 0o640); err != nil {
		return "", err
	}
	return docRoot, nil
}

func normalizeDomain(v string) string {
	return strings.ToLower(strings.TrimSpace(v))
}

func isValidDomain(v string) bool {
	return IsValidDomain(v)
}

// IsValidDomain barındırma FQDN doğrulaması (engine site oluşturma / yeniden adlandırma).
func IsValidDomain(v string) bool {
	v = normalizeDomain(v)
	if v == "" || v == "." || strings.Contains(v, "..") || strings.Contains(v, "/") || strings.Contains(v, "\\") || filepath.IsAbs(v) {
		return false
	}
	return siteDomainRe.MatchString(v)
}

// ReadSubdomainMeta alt site meta json (silmeden önce okumak için).
func ReadSubdomainMeta(webRoot, parentDomain, pathSegment string) (*SiteMeta, error) {
	parentDomain = strings.ToLower(strings.TrimSpace(parentDomain))
	pathSegment = strings.TrimSpace(pathSegment)
	if parentDomain == "" || pathSegment == "" {
		return nil, nil
	}
	metaPath := filepath.Join(webRoot, parentDomain, ".panelze", "subdomains", pathSegment+".json")
	b, err := os.ReadFile(metaPath)
	if err != nil {
		if os.IsNotExist(err) {
			leg := filepath.Join(webRoot, parentDomain, ".panelsar", "subdomains", pathSegment+".json")
			b, err = os.ReadFile(leg)
		}
		if err != nil {
			if os.IsNotExist(err) {
				return nil, nil
			}
			return nil, err
		}
	}
	var m SiteMeta
	if err := json.Unmarshal(b, &m); err != nil {
		return nil, err
	}
	return &m, nil
}

// RemoveSubdomain alt dizini ve meta dosyasını siler (pathSegment = klasör adı, örn. blog).
func RemoveSubdomain(webRoot, parentDomain, pathSegment string) (hostname string, err error) {
	parentDomain = strings.ToLower(strings.TrimSpace(parentDomain))
	pathSegment = strings.TrimSpace(pathSegment)
	if parentDomain == "" || pathSegment == "" || strings.Contains(parentDomain, "..") {
		return "", fmt.Errorf("invalid parent or path segment")
	}
	if strings.Contains(pathSegment, "/") || strings.Contains(pathSegment, "..") {
		return "", fmt.Errorf("invalid path segment")
	}
	metaPath := filepath.Join(webRoot, parentDomain, ".panelze", "subdomains", pathSegment+".json")
	legacyMetaPath := filepath.Join(webRoot, parentDomain, ".panelsar", "subdomains", pathSegment+".json")
	b, rerr := os.ReadFile(metaPath)
	if rerr != nil && os.IsNotExist(rerr) {
		b, rerr = os.ReadFile(legacyMetaPath)
	}
	if rerr == nil {
		var m SiteMeta
		if json.Unmarshal(b, &m) == nil && strings.TrimSpace(m.Hostname) != "" {
			hostname = strings.ToLower(strings.TrimSpace(m.Hostname))
		}
	}
	base := filepath.Join(webRoot, parentDomain, pathSegment)
	if err := os.RemoveAll(base); err != nil {
		return hostname, err
	}
	_ = os.Remove(metaPath)
	_ = os.Remove(legacyMetaPath)
	return hostname, nil
}

// WriteSubdomainMeta .panelze/subdomains/<pathSegment>.json yazar (alan adı yeniden adlandırma sonrası güncelleme için).
func WriteSubdomainMeta(webRoot, parentDomain, pathSegment string, meta *SiteMeta) error {
	parentDomain = strings.ToLower(strings.TrimSpace(parentDomain))
	pathSegment = strings.TrimSpace(pathSegment)
	if parentDomain == "" || pathSegment == "" || strings.Contains(parentDomain, "..") ||
		strings.Contains(pathSegment, "/") || strings.Contains(pathSegment, "..") || meta == nil {
		return fmt.Errorf("invalid subdomain meta write")
	}
	subMetaDir := filepath.Join(webRoot, parentDomain, ".panelze", "subdomains")
	if err := os.MkdirAll(subMetaDir, 0o750); err != nil {
		return err
	}
	metaPath := filepath.Join(subMetaDir, pathSegment+".json")
	b, err := json.MarshalIndent(meta, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(metaPath, b, 0o640)
}

func ListDomains(webRoot string) ([]string, error) {
	entries, err := os.ReadDir(webRoot)
	if err != nil {
		if os.IsNotExist(err) {
			if mk := os.MkdirAll(webRoot, 0o755); mk != nil {
				return nil, mk
			}
			return []string{}, nil
		}
		return nil, err
	}
	var out []string
	for _, e := range entries {
		if e.IsDir() && !strings.HasPrefix(e.Name(), ".") {
			out = append(out, e.Name())
		}
	}
	return out, nil
}
