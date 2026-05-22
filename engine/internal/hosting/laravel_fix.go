package hosting

import (
	"bufio"
	"os"
	"path/filepath"
	"strings"
)

// urlEnvKeys — belge kökü zaten .../public iken .env içinde /public soneki hatalı medya URL üretir.
var urlEnvKeys = map[string]bool{
	"APP_URL":              true,
	"ASSET_URL":            true,
	"MIX_ASSET_URL":        true,
	"SITE_URL":             true,
	"CMS_URL":              true,
	"BASE_URL":             true,
	"PUBLIC_URL":           true,
	"MEDIA_URL":            true,
	"UPLOAD_URL":           true,
	"NEXT_PUBLIC_APP_URL":  true,
}

// EnvHasPublicURLSuffix .env içinde APP_URL vb. /public ile bitiyor mu?
func EnvHasPublicURLSuffix(siteBase string) bool {
	_, ok := scanEnvPublicSuffix(filepath.Join(siteBase, ".env"))
	return ok
}

func scanEnvPublicSuffix(envPath string) (keys []string, bad bool) {
	f, err := os.Open(envPath)
	if err != nil {
		return nil, false
	}
	defer f.Close()
	sc := bufio.NewScanner(f)
	for sc.Scan() {
		line := strings.TrimSpace(sc.Text())
		if line == "" || strings.HasPrefix(line, "#") {
			continue
		}
		k, v, ok := strings.Cut(line, "=")
		if !ok {
			continue
		}
		k = strings.TrimSpace(k)
		if !urlEnvKeys[k] {
			continue
		}
		if urlValueHasPublicSuffix(unquoteEnvValue(v)) {
			keys = append(keys, k)
			bad = true
		}
	}
	return keys, bad
}

func unquoteEnvValue(v string) string {
	v = strings.TrimSpace(v)
	if len(v) >= 2 {
		if (v[0] == '"' && v[len(v)-1] == '"') || (v[0] == '\'' && v[len(v)-1] == '\'') {
			return v[1 : len(v)-1]
		}
	}
	return v
}

func urlValueHasPublicSuffix(v string) bool {
	v = strings.TrimSpace(v)
	if v == "" {
		return false
	}
	if v == "/public" || strings.HasSuffix(v, "/public/") {
		return true
	}
	trim := strings.TrimRight(v, "/")
	return strings.HasSuffix(trim, "/public")
}

func stripPublicURLSuffix(v string) string {
	v = strings.TrimSpace(v)
	if v == "" {
		return v
	}
	quote := ""
	if len(v) >= 2 {
		if v[0] == '"' && v[len(v)-1] == '"' {
			quote = "\""
			v = v[1 : len(v)-1]
		} else if v[0] == '\'' && v[len(v)-1] == '\'' {
			quote = "'"
			v = v[1 : len(v)-1]
		}
	}
	inner := strings.TrimSpace(v)
	if inner == "/public" {
		inner = "/"
	} else {
		inner = strings.TrimRight(inner, "/")
		if strings.HasSuffix(inner, "/public") {
			inner = strings.TrimSuffix(inner, "/public")
			inner = strings.TrimRight(inner, "/")
		}
	}
	if inner == "" {
		inner = "/"
	}
	return quote + inner + quote
}

// NormalizePublicDocrootEnv belge kökü public iken .env URL anahtarlarındaki /public sonekini kaldırır.
func NormalizePublicDocrootEnv(siteBase, primaryDomain string) ([]string, error) {
	envPath := filepath.Join(siteBase, ".env")
	data, err := os.ReadFile(envPath)
	if err != nil {
		if os.IsNotExist(err) {
			return nil, nil
		}
		return nil, err
	}
	lines := strings.Split(string(data), "\n")
	changed := []string{}
	for i, line := range lines {
		trim := strings.TrimSpace(line)
		if trim == "" || strings.HasPrefix(trim, "#") {
			continue
		}
		k, v, ok := strings.Cut(line, "=")
		if !ok {
			continue
		}
		key := strings.TrimSpace(k)
		if !urlEnvKeys[key] {
			continue
		}
		raw := strings.TrimSpace(v)
		if !urlValueHasPublicSuffix(unquoteEnvValue(raw)) {
			continue
		}
		newVal := stripPublicURLSuffix(raw)
		if unquoteEnvValue(newVal) == "" || unquoteEnvValue(newVal) == "/" {
			if primaryDomain != "" {
				newVal = "https://" + strings.TrimPrefix(primaryDomain, "www.")
			}
		}
		if newVal != raw {
			lines[i] = key + "=" + newVal
			changed = append(changed, key)
		}
	}
	if len(changed) == 0 {
		return nil, nil
	}
	out := strings.Join(lines, "\n")
	if !strings.HasSuffix(string(data), "\n") && len(lines) > 0 {
		// preserve trailing newline only if file had one
	} else if strings.HasSuffix(string(data), "\n") {
		out += "\n"
	}
	if err := os.WriteFile(envPath, []byte(out), 0o640); err != nil {
		return changed, err
	}
	return changed, nil
}

// DocrootIsPublicVariant hedef belge kökü public_html/public altında mı?
func DocrootIsPublicVariant(docRoot, siteBase string) bool {
	docRoot = filepath.Clean(docRoot)
	siteBase = filepath.Clean(siteBase)
	want := filepath.Join(siteBase, "public")
	return docRoot == want
}

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
