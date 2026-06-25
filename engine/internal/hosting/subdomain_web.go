package hosting

import (
	"fmt"
	"path/filepath"
	"strings"

	"panelze/engine/internal/config"
	"panelze/engine/internal/sites"
)

// SubdomainSiteBase parent/pathSegment altındaki public_html yolu.
func SubdomainSiteBase(webRoot, parent, pathSegment string) string {
	return filepath.Clean(filepath.Join(webRoot, parent, pathSegment, "public_html"))
}

// FinalizeSubdomainWebStack alt alan için PHP sürümü, Laravel public kökü ve vhost senkronu.
func FinalizeSubdomainWebStack(cfg *config.Config, parent, pathSegment, hostname string) error {
	parent = strings.ToLower(strings.TrimSpace(parent))
	pathSegment = strings.TrimSpace(pathSegment)
	hostname = strings.ToLower(strings.TrimSpace(hostname))
	if parent == "" || pathSegment == "" || hostname == "" {
		return fmt.Errorf("invalid subdomain finalize args")
	}

	meta, err := sites.ReadSubdomainMeta(cfg.Paths.WebRoot, parent, pathSegment)
	if err != nil || meta == nil {
		return fmt.Errorf("subdomain meta not found")
	}

	siteBase := SubdomainSiteBase(cfg.Paths.WebRoot, parent, pathSegment)
	docRoot := strings.TrimSpace(meta.DocumentRoot)
	if docRoot == "" {
		docRoot = siteBase
		meta.DocumentRoot = siteBase
	}

	scan, scanErr := ScanSiteStack(siteBase, docRoot, meta.ServerType)
	if scanErr == nil && scan != nil {
		if p := strings.TrimSpace(scan.SuggestedPHPVersion); p != "" {
			meta.PHPVersion = p
		}
		if prof := NormalizeAppProfile(scan.Profile); prof != "" && prof != "standard" {
			meta.AppProfile = prof
		}
	}

	if detected := DetectPHPVersionFromSite(siteBase); detected != "" {
		meta.PHPVersion = detected
	}

	if meta.AppProfile == "laravel" || (scan != nil && scan.Profile == "laravel") {
		_ = EnsureStoragePublicLink(siteBase)
		if hostingDocrootIsPublic(siteBase, docRoot) {
			_, _ = NormalizePublicDocrootEnv(siteBase, hostname)
		}
	}

	if err := sites.WriteSubdomainMeta(cfg.Paths.WebRoot, parent, pathSegment, meta); err != nil {
		return err
	}
	return ApplySubdomainVhost(cfg, parent, hostname, meta.DocumentRoot, meta)
}

func hostingDocrootIsPublic(siteBase, docRoot string) bool {
	return DocrootIsPublicVariant(filepath.Clean(docRoot), filepath.Clean(siteBase)) ||
		filepath.Clean(ResolveHTTPDocRoot(docRoot)) != filepath.Clean(docRoot)
}
