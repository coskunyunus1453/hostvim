package hosting

import (
	"os"
	"path/filepath"
)

// EnsureStoragePublicLink Laravel public/storage → storage/app/public sembolik bağlantısını oluşturur.
func EnsureStoragePublicLink(siteBase string) error {
	siteBase = filepath.Clean(siteBase)
	publicDir := filepath.Join(siteBase, "public")
	target := filepath.Join(siteBase, "storage", "app", "public")
	linkPath := filepath.Join(publicDir, "storage")

	if st, err := os.Stat(publicDir); err != nil || !st.IsDir() {
		return os.ErrNotExist
	}
	if st, err := os.Stat(target); err != nil || !st.IsDir() {
		return os.ErrNotExist
	}

	if fi, err := os.Lstat(linkPath); err == nil {
		if fi.Mode()&os.ModeSymlink != 0 {
			dest, rerr := os.Readlink(linkPath)
			if rerr == nil {
				absDest, _ := filepath.Abs(filepath.Join(publicDir, dest))
				absTarget, _ := filepath.Abs(target)
				if absDest == absTarget {
					return nil
				}
			}
		}
		_ = os.RemoveAll(linkPath)
	}

	rel, err := filepath.Rel(publicDir, target)
	if err != nil {
		return err
	}
	return os.Symlink(rel, linkPath)
}
