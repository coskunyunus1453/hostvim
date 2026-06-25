package hosting

import (
	"os"
	"path/filepath"
	"testing"
)

func TestResolveHTTPDocRoot(t *testing.T) {
	t.Parallel()
	base := t.TempDir()
	site := filepath.Join(base, "kodsar.com", "firma", "public_html")
	pub := filepath.Join(site, "public")
	if err := os.MkdirAll(pub, 0o755); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(filepath.Join(pub, "index.php"), []byte("<?php"), 0o644); err != nil {
		t.Fatal(err)
	}

	got := ResolveHTTPDocRoot(site)
	if got != pub {
		t.Fatalf("expected %q, got %q", pub, got)
	}
	if ResolveHTTPDocRoot(pub) != pub {
		t.Fatalf("already public variant should stay %q", pub)
	}

	plain := filepath.Join(base, "example.com", "public_html")
	if err := os.MkdirAll(plain, 0o755); err != nil {
		t.Fatal(err)
	}
	if ResolveHTTPDocRoot(plain) != plain {
		t.Fatalf("plain public_html should stay %q", plain)
	}
}
