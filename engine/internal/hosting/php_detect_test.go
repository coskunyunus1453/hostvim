package hosting

import (
	"os"
	"path/filepath"
	"testing"
)

func TestPickInstalledPHPVersion(t *testing.T) {
	t.Parallel()
	got := PickInstalledPHPVersion("8.3.0")
	if got == "" {
		t.Skip("no php-fpm sockets in test env")
	}
	if !semverGTE(parseSemver(got), parseSemver("8.3.0")) {
		t.Fatalf("expected >= 8.3, got %q", got)
	}
}

func TestDetectPHPVersionFromPlatformCheck(t *testing.T) {
	t.Parallel()
	base := t.TempDir()
	dir := filepath.Join(base, "vendor", "composer")
	if err := os.MkdirAll(dir, 0o755); err != nil {
		t.Fatal(err)
	}
	content := `<?php // Your Composer dependencies require a PHP version ">= 8.4.1".`
	if err := os.WriteFile(filepath.Join(dir, "platform_check.php"), []byte(content), 0o644); err != nil {
		t.Fatal(err)
	}
	min := readMinPHPFromPlatformCheck(base)
	if min != "8.4.1" {
		t.Fatalf("expected 8.4.1, got %q", min)
	}
}
