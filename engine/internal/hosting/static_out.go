package hosting

import (
	"os"
	"path/filepath"

	"panelze/engine/internal/config"
	"panelze/engine/internal/sites"
)

// HasStaticOutExport public_html/out/index.html var mı (Next.js static export vb.).
func HasStaticOutExport(siteBase string) bool {
	p := filepath.Join(filepath.Clean(siteBase), "out", "index.html")
	st, err := os.Stat(p)
	return err == nil && !st.IsDir()
}

// ActivateStaticOutExport Node proxy kapatır, belge kökünü out yapar, vhost yeniler.
func ActivateStaticOutExport(cfg *config.Config, domain, siteBase string, meta *sites.SiteMeta) (string, error) {
	if !HasStaticOutExport(siteBase) {
		return "", os.ErrNotExist
	}
	outRoot := filepath.Join(filepath.Clean(siteBase), "out")
	if meta == nil {
		return "", os.ErrInvalid
	}
	if meta.NodeApp != nil {
		meta.NodeApp.Enabled = false
		meta.NodeApp.AutoStart = false
	}
	meta.DocumentRoot = outRoot
	meta.AppProfile = "static"
	if err := sites.WriteSiteMeta(cfg.Paths.WebRoot, domain, meta); err != nil {
		return "", err
	}
	if err := ApplyWebServer(cfg, domain, outRoot, meta, ""); err != nil {
		return "", err
	}
	return outRoot, nil
}
