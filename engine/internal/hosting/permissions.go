package hosting

import (
	"fmt"
	"os/exec"
	"strings"
)

// RepairSitePermissions runs panelze-fix-hosting-perms for one domain (sudo, www-data).
func RepairSitePermissions(domain string) error {
	domain = strings.ToLower(strings.TrimSpace(domain))
	if domain == "" {
		return fmt.Errorf("domain required")
	}
	cmd := exec.Command("sudo", "-n", "/usr/local/sbin/panelze-fix-hosting-perms", "--domain", domain)
	out, err := cmd.CombinedOutput()
	if err != nil {
		msg := strings.TrimSpace(string(out))
		if msg == "" {
			msg = err.Error()
		}
		return fmt.Errorf("repair site permissions: %s", msg)
	}
	return nil
}
