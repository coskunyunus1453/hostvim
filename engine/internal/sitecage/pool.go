package sitecage

import (
	"os"
	"path/filepath"
	"strings"

	"panelze/engine/internal/config"
	"panelze/engine/internal/phpfpm"
	"panelze/engine/internal/sites"
)

// PoolOptions site için PHP-FPM pool seçenekleri (PanelKafes).
func PoolOptions(cfg *config.Config, meta *sites.SiteMeta, domain, docRoot string) phpfpm.PoolOptions {
	c := FromHosting(cfg)
	opts := phpfpm.PoolOptions{
		MaxChildren: c.DefaultMaxChildren,
		MemoryLimit: c.DefaultMemoryLimit,
	}
	// Site bilinçli olarak shell fonksiyonlarına izin verdiyse disable_functions'ı kaldır.
	if meta != nil && meta.ShellFunctions {
		opts.DisableFunctions = "none"
	}
	if !c.Enabled {
		return opts
	}
	cageUser := ""
	if meta != nil && strings.TrimSpace(meta.CageUser) != "" {
		cageUser = strings.TrimSpace(meta.CageUser)
	} else {
		cageUser = LinuxUserForDomain(c.UserPrefix, domain)
	}
	opts.SiteUser = cageUser
	opts.SiteGroup = c.Group
	siteRoot := filepath.Join(cfg.Paths.WebRoot, domain)
	// open_basedir kökü site kökü olmalı; aksi halde docroot=public/ olan
	// projeler vendor/storage/.env'e erişemez (500).
	opts.BaseDir = siteRoot
	// Docroot symlink ile site kökünün dışına çıkıyorsa (panelze → landing)
	// gerçek ağacı da BaseDir yap; RenderPool ayrıca realpath ekler.
	if doc := strings.TrimSpace(docRoot); doc != "" {
		if real, err := filepath.EvalSymlinks(doc); err == nil && real != "" {
			real = filepath.Clean(real)
			if !strings.HasPrefix(real, filepath.Clean(siteRoot)+string(filepath.Separator)) && real != filepath.Clean(siteRoot) {
				base := real
				if filepath.Base(base) == "public" {
					base = filepath.Dir(base)
				}
				opts.BaseDir = base
			}
		} else if _, err := os.Stat(siteRoot); err != nil {
			base := filepath.Clean(doc)
			if filepath.Base(base) == "public" {
				base = filepath.Dir(base)
			}
			opts.BaseDir = base
		}
	}
	opts.ExtraBasedir = filepath.Join(opts.BaseDir, "tmp")
	return opts
}

// ManagePools PanelKafes açıkken domain başına pool zorunlu.
func ManagePools(cfg *config.Config) bool {
	if cfg == nil {
		return false
	}
	return cfg.Hosting.PHPFPMmanagePools || cfg.Hosting.SiteCageEnabled
}
