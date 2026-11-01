package hosting

import (
	"fmt"
	"os/exec"
	"strings"
	"time"
)

// RepairSitePermissions runs panelze-fix-hosting-perms for one domain (sudo, www-data).
// WARNING: On large sites this can take minutes (full chown -R). Prefer RepairPathPermissions
// for file-manager create/write/mkdir flows.
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

// RepairPathPermissions repairs only the ancestor chain for absPath under the domain
// (no full-tree chown). Safe for file manager operations on large sites.
func RepairPathPermissions(domain, absPath string) error {
	domain = strings.ToLower(strings.TrimSpace(domain))
	absPath = strings.TrimSpace(absPath)
	if domain == "" {
		return fmt.Errorf("domain required")
	}
	if absPath == "" {
		return fmt.Errorf("path required")
	}

	cmd := exec.Command(
		"sudo", "-n", "/usr/local/sbin/panelze-fix-hosting-perms",
		"--domain", domain,
		"--path", absPath,
	)
	// Hard cap so panel HTTP clients never hang for minutes.
	done := make(chan struct{})
	var out []byte
	var err error
	go func() {
		out, err = cmd.CombinedOutput()
		close(done)
	}()
	select {
	case <-done:
		if err != nil {
			msg := strings.TrimSpace(string(out))
			if msg == "" {
				msg = err.Error()
			}
			return fmt.Errorf("repair path permissions: %s", msg)
		}
		return nil
	case <-time.After(20 * time.Second):
		if cmd.Process != nil {
			_ = cmd.Process.Kill()
		}
		return fmt.Errorf("repair path permissions: timeout after 20s")
	}
}
