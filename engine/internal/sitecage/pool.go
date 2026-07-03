package sitecage

import (
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
		MaxChildren:  c.DefaultMaxChildren,
		MemoryLimit:  c.DefaultMemoryLimit,
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
	opts.ExtraBasedir = filepath.Join(siteRoot, "tmp")
	return opts
}

// ManagePools PanelKafes açıkken domain başına pool zorunlu.
func ManagePools(cfg *config.Config) bool {
	if cfg == nil {
		return false
	}
	return cfg.Hosting.PHPFPMmanagePools || cfg.Hosting.SiteCageEnabled
}
