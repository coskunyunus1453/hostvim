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
func buildStartEnv(webRoot string, sc SiteScope, meta *sites.SiteMeta, workAbs string, port int) []string {
	merged := map[string]string{
		"PORT":        strconv.Itoa(port),
		"HOST":        "127.0.0.1",
		"NODE_ENV":    "production",
		"TRUST_PROXY": "1",
	}

	if meta != nil && meta.NodeApp != nil {
		rel := strings.TrimSpace(meta.NodeApp.EnvFile)
		if rel == "" {
			rel = ".env"
		}
		envCandidates := []string{
			filepath.Join(workAbs, rel),
			filepath.Join(sc.siteBase(webRoot), rel),
		}
		seen := map[string]bool{}
		for _, envPath := range envCandidates {
			envPath = filepath.Clean(envPath)
			if seen[envPath] {
				continue
			}
			seen[envPath] = true
			if st, err := os.Stat(envPath); err == nil && !st.IsDir() {
				for k, v := range parseDotEnvFile(envPath) {
					merged[k] = v
				}
			}
		}
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
	host := sc.publicHostname(meta)
	if host == "" {
		host = sc.ParentDomain
	}
	for k, v := range publicURLVars(host, ssl) {
		merged[k] = v
	}

	// Panelin seçtiği port/her zaman geçerli (package.json -p bayrağını geçersiz kılar).
	merged["PORT"] = strconv.Itoa(port)
	merged["HOST"] = "127.0.0.1"
	merged["NODE_ENV"] = "production"

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
