package nodeapp

import (
	"bufio"
	"os"
	"path/filepath"
	"strconv"
	"strings"

	"panelze/engine/internal/sites"
)

// buildStartEnv PM2/npm sürecine verilecek ortam değişkenleri (OAuth / reverse proxy uyumu).
func buildStartEnv(webRoot, domain string, meta *sites.SiteMeta, workAbs string, port int) []string {
	merged := map[string]string{
		"PORT":       strconv.Itoa(port),
		"HOST":       "127.0.0.1",
		"NODE_ENV":   "production",
		"TRUST_PROXY": "1",
	}

	if meta != nil && meta.NodeApp != nil {
		rel := strings.TrimSpace(meta.NodeApp.EnvFile)
		if rel == "" {
			rel = ".env"
		}
		if !filepath.IsAbs(rel) {
			rel = filepath.Join(siteBase(webRoot, domain), rel)
		}
		rel = filepath.Clean(rel)
		if st, err := os.Stat(rel); err == nil && !st.IsDir() {
			for k, v := range parseDotEnvFile(rel) {
				merged[k] = v
			}
		}
		// .env.local / .env.production (Next.js) — dosyada tanımlı değerleri yükle
		for _, extra := range []string{".env.local", ".env.production"} {
			p := filepath.Join(workAbs, extra)
			if st, err := os.Stat(p); err == nil && !st.IsDir() {
				for k, v := range parseDotEnvFile(p) {
					if _, ok := merged[k]; !ok {
						merged[k] = v
					}
				}
			}
		}
	}

	ssl := meta != nil && meta.SSLEnabled
	for k, v := range publicURLVars(domain, ssl) {
		merged[k] = v
	}

	out := make([]string, 0, len(merged))
	for k, v := range merged {
		out = append(out, k+"="+v)
	}
	return out
}

func publicURLVars(domain string, ssl bool) map[string]string {
	domain = strings.ToLower(strings.TrimSpace(domain))
	scheme := "http"
	if ssl {
		scheme = "https"
	}
	base := scheme + "://" + domain
	return map[string]string{
		"TRUST_PROXY":              "1",
		"AUTH_TRUST_HOST":          "true",
		"NEXTAUTH_URL":             base,
		"AUTH_URL":                 base,
		"APP_URL":                  base,
		"SITE_URL":                 base,
		"PUBLIC_URL":               base,
		"PANELZE_PUBLIC_URL":       base,
		"NEXT_PUBLIC_APP_URL":      base,
		"NEXT_PUBLIC_SITE_URL":     base,
		"NEXT_PUBLIC_VERCEL_URL":   domain,
	}
}

func parseDotEnvFile(path string) map[string]string {
	out := map[string]string{}
	f, err := os.Open(path)
	if err != nil {
		return out
	}
	defer f.Close()
	sc := bufio.NewScanner(f)
	for sc.Scan() {
		line := strings.TrimSpace(sc.Text())
		if line == "" || strings.HasPrefix(line, "#") {
			continue
		}
		if strings.HasPrefix(line, "export ") {
			line = strings.TrimSpace(strings.TrimPrefix(line, "export "))
		}
		i := strings.Index(line, "=")
		if i <= 0 {
			continue
		}
		key := strings.TrimSpace(line[:i])
		val := strings.TrimSpace(line[i+1:])
		val = strings.Trim(val, `"'`)
		if key != "" {
			out[key] = val
		}
	}
	return out
}
