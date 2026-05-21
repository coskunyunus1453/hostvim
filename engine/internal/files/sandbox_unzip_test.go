package files

import (
	"archive/zip"
	"bytes"
	"os"
	"path/filepath"
	"testing"
)

func TestUnzipRejectsTooManyEntries(t *testing.T) {
	ConfigureUnzipLimits(1_000, 1<<30, 300, 200)
	defer ConfigureUnzipLimits(100_000, 5<<30, 300, 200)

	root := t.TempDir()
	arc := filepath.Join(root, "big.zip")
	w, err := os.Create(arc)
	if err != nil {
		t.Fatal(err)
	}
	zw := zip.NewWriter(w)
	for i := 0; i < 1_001; i++ {
		f, err := zw.Create(filepath.Join("d", string(rune('a'+i%26)), "f.txt"))
		if err != nil {
			t.Fatal(err)
		}
		if _, err := f.Write([]byte("x")); err != nil {
			t.Fatal(err)
		}
	}
	if err := zw.Close(); err != nil {
		t.Fatal(err)
	}
	if err := w.Close(); err != nil {
		t.Fatal(err)
	}

	err = UnzipPath(root, "big.zip", "extracted", "fail")
	if err == nil || !bytes.Contains([]byte(err.Error()), []byte("too many entries")) {
		t.Fatalf("expected too many entries error, got %v", err)
	}
}

func TestUnzipAllowsManyEntriesWithinLimit(t *testing.T) {
	ConfigureUnzipLimits(100_000, 5<<30, 300, 200)

	root := t.TempDir()
	arc := filepath.Join(root, "ok.zip")
	w, err := os.Create(arc)
	if err != nil {
		t.Fatal(err)
	}
	zw := zip.NewWriter(w)
	for i := 0; i < 50; i++ {
		f, err := zw.Create(filepath.Join("pkg", string(rune('a'+i%26)), "file.txt"))
		if err != nil {
			t.Fatal(err)
		}
		if _, err := f.Write([]byte("payload")); err != nil {
			t.Fatal(err)
		}
	}
	if err := zw.Close(); err != nil {
		t.Fatal(err)
	}
	if err := w.Close(); err != nil {
		t.Fatal(err)
	}

	if err := UnzipPath(root, "ok.zip", "extracted", "fail"); err != nil {
		t.Fatalf("unzip failed: %v", err)
	}
}
