package nodeapp

import (
	"crypto/sha256"
	"encoding/binary"
	"encoding/json"
	"os"
	"path/filepath"
	"regexp"
	"strconv"
	"strings"
	"time"

	"panelze/engine/internal/config"
	"panelze/engine/internal/sites"
)

var (
	portFlagRe    = regexp.MustCompile(`(?:^|\s)(?:-p|--port)(?:=|\s+)(\d{2,5})(?:\s|$)`)
	portEnvLineRe = regexp.MustCompile(`^PORT\s*=\s*["']?(\d{2,5})["']?\s*$`)
)

// DefaultPortForProfile framework varsayılan dinleme portu.
func DefaultPortForProfile(profile string) int {
	switch strings.ToLower(strings.TrimSpace(profile)) {
	case "strapi":
		return 1337
	case "n8n":
		return 5678
	case "nextjs", "nuxt", "node":
		return 3000
	default:
		return 3000
	}
}

func parsePortFromScript(script string) int {
	script = strings.TrimSpace(script)
	if script == "" {
		return 0
	}
	if m := portFlagRe.FindStringSubmatch(script); len(m) >= 2 {
		if p, err := strconv.Atoi(m[1]); err == nil && p >= 1024 && p <= 65535 {
			return p
		}
	}
	return 0
}

func parsePortFromDotEnv(path string) int {
	b, err := os.ReadFile(path)
	if err != nil {
		return 0
	}
	for _, line := range strings.Split(string(b), "\n") {
		line = strings.TrimSpace(line)
		if line == "" || strings.HasPrefix(line, "#") {
			continue
		}
		if m := portEnvLineRe.FindStringSubmatch(line); len(m) >= 2 {
			if p, err := strconv.Atoi(m[1]); err == nil && p >= 1024 && p <= 65535 {
				return p
			}
		}
	}
	return 0
}

func inferPortFromWorkDir(siteBase, workDirRel, profile string) int {
	workAbs := siteBase
	if workDirRel != "" && workDirRel != "." {
		workAbs = filepath.Join(siteBase, workDirRel)
	}
	workAbs = filepath.Clean(workAbs)

	pkgPath := filepath.Join(workAbs, "package.json")
	if b, err := os.ReadFile(pkgPath); err == nil {
		var pkg pkgJSON
		if json.Unmarshal(b, &pkg) == nil {
			for _, pref := range []string{"start", "start:prod", "prod", "serve", "dev"} {
				if script, ok := pkg.Scripts[pref]; ok {
					if p := parsePortFromScript(script); p > 0 {
						return p
					}
				}
			}
		}
	}

	for _, envName := range []string{".env", ".env.local", ".env.production"} {
		if p := parsePortFromDotEnv(filepath.Join(workAbs, envName)); p > 0 {
			return p
		}
	}
	if p := parsePortFromDotEnv(filepath.Join(siteBase, ".env")); p > 0 {
		return p
	}

	return DefaultPortForProfile(profile)
}

type portOwner struct {
	scope SiteScope
}

func collectUsedPorts(webRoot string) map[int]portOwner {
	used := map[int]portOwner{}
	domains, err := sites.ListDomains(webRoot)
	if err != nil {
		return used
	}
	for _, d := range domains {
		recordPort(used, webRoot, scopeFrom(d, ""))
		subDir := filepath.Join(webRoot, d, ".panelze", "subdomains")
		entries, err := os.ReadDir(subDir)
		if err != nil {
			continue
		}
		for _, ent := range entries {
			if ent.IsDir() || !strings.HasSuffix(ent.Name(), ".json") {
				continue
			}
			seg := strings.TrimSuffix(ent.Name(), ".json")
			recordPort(used, webRoot, scopeFrom(d, seg))
		}
	}
	return used
}

func recordPort(used map[int]portOwner, webRoot string, sc SiteScope) {
	meta, err := sc.readMeta(webRoot)
	if err != nil || meta == nil || meta.NodeApp == nil {
		return
	}
	p := meta.NodeApp.ListenPort
	if p >= 1024 && p <= 65535 {
		used[p] = portOwner{scope: sc}
	}
}

func portTakenByOther(used map[int]portOwner, sc SiteScope, port int) bool {
	owner, ok := used[port]
	if !ok {
		return false
	}
	if owner.scope.ParentDomain == sc.ParentDomain && owner.scope.PathSegment == sc.PathSegment {
		return false
	}
	// Başka sitede meta kaydı var ama süreç dinlemiyorsa portu serbest bırak.
	if !portListeningQuick(port) {
		return false
	}
	return true
}

func portListeningQuick(port int) bool {
	return waitForListen(port, 800*time.Millisecond) == nil
}

func stableHashPort(key string, base int) int {
	if base < 1024 {
		base = 3011
	}
	h := sha256.Sum256([]byte(key))
	n := int(binary.BigEndian.Uint32(h[:4]) % 89)
	return base + n
}

// AllocateListenPort siteye özgü, çakışmasız dinleme portu seçer.
func AllocateListenPort(cfg *config.Config, sc SiteScope, profile string, preferred int, siteBasePath string, workDirRel string) int {
	used := collectUsedPorts(cfg.Paths.WebRoot)
	inferred := inferPortFromWorkDir(siteBasePath, workDirRel, profile)

	try := func(port int) (int, bool) {
		if port < 1024 || port > 65535 {
			return 0, false
		}
		if portTakenByOther(used, sc, port) {
			return 0, false
		}
		// Kayıtlı port docker/başka süreç tarafından tutuluyorsa package.json portunu tercih et.
		if preferred > 0 && port == preferred && inferred > 0 && port != inferred && portListeningQuick(port) {
			return 0, false
		}
		if portListeningQuick(port) {
			if owner, ok := used[port]; !ok || owner.scope.ParentDomain != sc.ParentDomain || owner.scope.PathSegment != sc.PathSegment {
				return 0, false
			}
		}
		return port, true
	}

	candidates := []int{}
	if inferred > 0 {
		candidates = append(candidates, inferred)
	}
	if preferred > 0 {
		candidates = append(candidates, preferred)
	}
	candidates = append(candidates, DefaultPortForProfile(profile))
	candidates = append(candidates, stableHashPort(sc.pm2Name(), 3011))

	seen := map[int]bool{}
	for _, p := range candidates {
		if seen[p] {
			continue
		}
		seen[p] = true
		if port, ok := try(p); ok {
			return port
		}
	}
	for p := 3011; p <= 3099; p++ {
		if port, ok := try(p); ok {
			return port
		}
	}
	return DefaultPortForProfile(profile)
}
