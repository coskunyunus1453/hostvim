package nginx

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"

	"panelze/engine/internal/config"
)

const DefaultVhostConfPrefix = "panelze"

var LegacyVhostConfPrefixes = []string{"panelze", "panelsar", "hostvim"}

var VhostPrevSuffixes = []string{".panelze-prev", ".hostvim-prev", ".panelsar-prev"}

func VhostConfPrefix(cfg *config.Config) string {
	if cfg != nil {
		p := strings.TrimSpace(cfg.Hosting.VhostConfPrefix)
		if p != "" {
			return p
		}
	}
	return DefaultVhostConfPrefix
}

func ConfBaseName(prefix, domain string) string {
	return prefix + "-" + strings.ToLower(strings.TrimSpace(domain)) + ".conf"
}

func prefixCandidates(cfg *config.Config) []string {
	seen := map[string]bool{}
	var out []string
	add := func(p string) {
		p = strings.TrimSpace(p)
		if p == "" || seen[p] {
			return
		}
		seen[p] = true
		out = append(out, p)
	}
	add(VhostConfPrefix(cfg))
	for _, leg := range LegacyVhostConfPrefixes {
		add(leg)
	}
	return out
}

func CanonicalVhostPath(cfg *config.Config, vhostsDir, domain string) (string, error) {
	domain = strings.ToLower(strings.TrimSpace(domain))
	if domain == "" || strings.Contains(domain, "..") || !domainSafe.MatchString(domain) {
		return "", fmt.Errorf("invalid domain")
	}
	vd := strings.TrimSpace(vhostsDir)
	if vd == "" {
		return "", fmt.Errorf("vhosts dir is not set")
	}
	vdClean, err := filepath.Abs(filepath.Clean(vd))
	if err != nil {
		return "", fmt.Errorf("vhosts_dir: %w", err)
	}
	base := ConfBaseName(VhostConfPrefix(cfg), domain)
	p := filepath.Join(vdClean, base)
	p = filepath.Clean(p)
	rel, err := filepath.Rel(vdClean, p)
	if err != nil || rel == ".." || strings.HasPrefix(rel, "../") {
		return "", fmt.Errorf("invalid vhost path")
	}
	return p, nil
}

func ResolveVhostPath(cfg *config.Config, vhostsDir, domain string) (path string, exists bool, err error) {
	canonical, err := CanonicalVhostPath(cfg, vhostsDir, domain)
	if err != nil {
		return "", false, err
	}
	if fi, statErr := os.Stat(canonical); statErr == nil && !fi.IsDir() {
		return canonical, true, nil
	}
	vd := filepath.Dir(canonical)
	for _, prefix := range prefixCandidates(cfg) {
		if prefix == VhostConfPrefix(cfg) {
			continue
		}
		p := filepath.Join(vd, ConfBaseName(prefix, domain))
		if fi, statErr := os.Stat(p); statErr == nil && !fi.IsDir() {
			return p, true, nil
		}
	}
	return canonical, false, nil
}

func FindVhostPrevPath(mainPath string) (string, bool) {
	for _, suf := range VhostPrevSuffixes {
		p := mainPath + suf
		if fi, err := os.Stat(p); err == nil && fi.Size() > 0 {
			return p, true
		}
	}
	return "", false
}
