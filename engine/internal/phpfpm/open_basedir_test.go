package phpfpm

import (
	"os"
	"path/filepath"
	"strings"
	"testing"
)

func TestAppendOpenBasedirRealpaths_SymlinkOutsideBase(t *testing.T) {
	tmp := t.TempDir()
	landing := filepath.Join(tmp, "landing", "public")
	if err := os.MkdirAll(landing, 0o755); err != nil {
		t.Fatal(err)
	}
	site := filepath.Join(tmp, "panelze.com")
	if err := os.MkdirAll(site, 0o755); err != nil {
		t.Fatal(err)
	}
	link := filepath.Join(site, "public_html")
	if err := os.Symlink(landing, link); err != nil {
		t.Fatal(err)
	}

	got := appendOpenBasedirRealpaths(site, site, link)
	if !strings.Contains(got, landing) {
		t.Fatalf("expected realpath %q in %q", landing, got)
	}
	landingRoot := filepath.Dir(landing)
	if !strings.Contains(got, landingRoot) {
		t.Fatalf("expected landing root %q in %q", landingRoot, got)
	}
}
