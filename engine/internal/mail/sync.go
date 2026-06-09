package mail

import (
	"fmt"
	"os/exec"
	"strings"

	"hostvim/engine/internal/config"
	"hostvim/engine/internal/panelmirror"
)

const defaultProvisionScript = "/usr/local/sbin/hostvim-mail-provision"

func provisionScript(cfg *config.Config) string {
	if cfg != nil {
		s := strings.TrimSpace(cfg.Hosting.MailProvisionScript)
		if s != "" {
			return s
		}
	}
	return defaultProvisionScript
}

// SyncProvision engine-state/mail JSON dosyalarını Dovecot/Postfix'e uygular.
func SyncProvision(cfg *config.Config) error {
	stateDir := panelmirror.EngineStateDir(cfg)
	if strings.TrimSpace(stateDir) == "" {
		return fmt.Errorf("engine state dir not configured")
	}
	script := provisionScript(cfg)
	cmd := exec.Command("sudo", "-n", script, stateDir)
	out, err := cmd.CombinedOutput()
	if err != nil {
		msg := strings.TrimSpace(string(out))
		if msg == "" {
			msg = err.Error()
		}
		return fmt.Errorf("mail provision: %s", msg)
	}
	return nil
}
