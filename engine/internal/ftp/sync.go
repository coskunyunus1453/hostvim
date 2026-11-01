package ftp

import (
	"fmt"
	"os/exec"
	"strings"

	"panelze/engine/internal/config"
	"panelze/engine/internal/panelmirror"
)

const defaultProvisionScript = "/usr/local/sbin/panelze-ftp-provision"

func provisionScript(cfg *config.Config) string {
	if cfg != nil {
		s := strings.TrimSpace(cfg.Hosting.FtpProvisionScript)
		if s != "" {
			return s
		}
	}
	return defaultProvisionScript
}

func userPrefix(cfg *config.Config) string {
	if cfg != nil {
		p := strings.TrimSpace(cfg.Hosting.SiteCageUserPrefix)
		if p != "" {
			return p
		}
	}
	return "pk"
}

// SyncProvision engine-state/ftp JSON dosyalarını Pure-FTPd PureDB'ye uygular.
func SyncProvision(cfg *config.Config) error {
	stateDir := panelmirror.EngineStateDir(cfg)
	if strings.TrimSpace(stateDir) == "" {
		return fmt.Errorf("engine state dir not configured")
	}
	webRoot := ""
	if cfg != nil {
		webRoot = strings.TrimSpace(cfg.Paths.WebRoot)
	}
	if webRoot == "" {
		return fmt.Errorf("web root not configured")
	}
	script := provisionScript(cfg)
	cmd := exec.Command("sudo", "-n", script, stateDir, webRoot, userPrefix(cfg))
	out, err := cmd.CombinedOutput()
	if err != nil {
		msg := strings.TrimSpace(string(out))
		if msg == "" {
			msg = err.Error()
		}
		return fmt.Errorf("ftp provision: %s", msg)
	}
	return nil
}
