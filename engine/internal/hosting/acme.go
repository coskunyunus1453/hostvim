package hosting

import (
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"syscall"
)

// EnsureAcmeChallengeDir, HTTP-01 doğrulaması için web kökünde challenge dizinini hazırlar.
// İzin hatasında panelze-fix-hosting-perms ile onarır ve yeniden dener.
func EnsureAcmeChallengeDir(httpDoc, repairDomain string) error {
	acmeDir := filepath.Join(httpDoc, ".well-known", "acme-challenge")
	if err := os.MkdirAll(acmeDir, 0o755); err == nil {
		return nil
	} else if !isPermissionDenied(err) {
		return fmt.Errorf("acme challenge dir: %w", err)
	}

	repairDomain = strings.ToLower(strings.TrimSpace(repairDomain))
	if repairDomain == "" {
		return fmt.Errorf("acme challenge dir: permission denied")
	}
	if rerr := RepairSitePermissions(repairDomain); rerr != nil {
		return fmt.Errorf("acme challenge dir: repair failed: %v", rerr)
	}
	if err2 := os.MkdirAll(acmeDir, 0o755); err2 != nil {
		return fmt.Errorf("acme challenge dir: %w", err2)
	}
	return nil
}

func isPermissionDenied(err error) bool {
	if err == nil {
		return false
	}
	if errors.Is(err, os.ErrPermission) {
		return true
	}
	var pe *os.PathError
	if errors.As(err, &pe) {
		return errors.Is(pe.Err, syscall.EACCES) || errors.Is(pe.Err, syscall.EPERM)
	}
	return strings.Contains(strings.ToLower(err.Error()), "permission denied")
}
