package nodeapp

import (
	"os"
	"path/filepath"
	"strings"
	"time"

	"panelze/engine/internal/config"
)

// EnsurePm2Daemon PM2 süreç listesini yükler; gerekirse resurrect dener.
func EnsurePm2Daemon(cfg *config.Config) (resurrected bool, err error) {
	if !cfg.Hosting.ManageNodeApps {
		return false, nil
	}
	if err := ensurePm2Home(cfg); err != nil {
		return false, err
	}

	if pm2DaemonAlive(cfg) {
		return false, nil
	}

	dump := filepath.Join(pm2Home(cfg), "dump.pm2")
	if _, statErr := os.Stat(dump); statErr != nil {
		return false, nil
	}

	if _, err := pm2Output(cfg, "resurrect"); err != nil {
		return false, err
	}
	time.Sleep(2 * time.Second)
	return true, nil
}

func pm2DaemonAlive(cfg *config.Config) bool {
	out, err := pm2Output(cfg, "jlist")
	if err != nil {
		return false
	}
	s := strings.TrimSpace(out)
	return s == "[]" || strings.Contains(s, `"name"`)
}
