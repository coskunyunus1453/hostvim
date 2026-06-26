package nodeapp

import (
	"context"
	"encoding/json"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"time"

	"panelze/engine/internal/config"
)

func profileNeedsProductionBuild(profile string) bool {
	switch strings.ToLower(strings.TrimSpace(profile)) {
	case "nextjs", "nuxt", "strapi":
		return true
	default:
		return false
	}
}

func nextjsBuildMarkers(workAbs string) []string {
	return []string{
		filepath.Join(workAbs, ".next", "BUILD_ID"),
		filepath.Join(workAbs, ".next", "prerender-manifest.json"),
		filepath.Join(workAbs, ".next", "routes-manifest.json"),
		filepath.Join(workAbs, ".next", "server"),
	}
}

func hasProductionBuild(workAbs, profile string) bool {
	profile = strings.ToLower(strings.TrimSpace(profile))
	switch profile {
	case "nextjs":
		for _, p := range nextjsBuildMarkers(workAbs) {
			st, err := os.Stat(p)
			if err != nil || (strings.HasSuffix(p, "server") && !st.IsDir()) {
				return false
			}
		}
		return true
	case "nuxt":
		if _, err := os.Stat(filepath.Join(workAbs, ".output", "nitro.json")); err == nil {
			return true
		}
		_, err := os.Stat(filepath.Join(workAbs, ".nuxt", "dist"))
		return err == nil
	case "strapi":
		_, err := os.Stat(filepath.Join(workAbs, "build", "index.html"))
		return err == nil
	default:
		return true
	}
}

func needsBuild(workAbs, profile string) bool {
	if !profileNeedsProductionBuild(profile) {
		return false
	}
	if hasProductionBuild(workAbs, profile) {
		return false
	}
	b, err := os.ReadFile(filepath.Join(workAbs, "package.json"))
	if err != nil {
		return false
	}
	var pkg pkgJSON
	if json.Unmarshal(b, &pkg) != nil {
		return false
	}
	_, ok := pkg.Scripts["build"]
	return ok
}

func buildLockPath(workAbs string) string {
	return filepath.Join(workAbs, ".panelze-npm-build.lock")
}

func acquireBuildLock(workAbs string) (func(), error) {
	lock := buildLockPath(workAbs)
	if err := os.WriteFile(lock, []byte("1"), 0o644); err != nil {
		return nil, err
	}
	return func() { _ = os.Remove(lock) }, nil
}

func buildInProgress(workAbs string) bool {
	_, err := os.Stat(buildLockPath(workAbs))
	return err == nil
}

func ensureWritableArtifacts(cfg *config.Config, workAbs string) {
	user := runUser(cfg)
	grp := user
	for _, rel := range []string{".next", "node_modules", ".output", "build", "dist"} {
		p := filepath.Join(workAbs, rel)
		if st, err := os.Stat(p); err == nil && (st.IsDir() || st.Mode().IsRegular()) {
			_ = exec.Command("chown", "-R", user+":"+grp, p).Run()
		}
	}
}

func runNpmAsSiteUser(cfg *config.Config, workAbs string, timeoutSec int, args ...string) (string, error) {
	if timeoutSec <= 0 {
		timeoutSec = 600
	}
	ensureWritableArtifacts(cfg, workAbs)
	user := runUser(cfg)
	ctx, cancel := context.WithTimeout(context.Background(), time.Duration(timeoutSec)*time.Second)
	defer cancel()
	cmdArgs := []string{"-u", user, "--", npmBin(cfg)}
	cmdArgs = append(cmdArgs, args...)
	cmd := exec.CommandContext(ctx, "sudo", cmdArgs...)
	cmd.Dir = workAbs
	cmd.Env = append(os.Environ(),
		"NODE_ENV=production",
		"HOME=/tmp",
		"npm_config_cache=/tmp/.npm",
	)
	out, err := cmd.CombinedOutput()
	s := strings.TrimSpace(string(out))
	if err != nil {
		if s != "" {
			return s, fmt.Errorf("%w — %s", err, s)
		}
		return s, err
	}
	return s, nil
}

// ensureProductionReady gerekirse npm run build çalıştırır.
func ensureProductionReady(cfg *config.Config, domain, pathSegment string, workAbs, profile string) (string, error) {
	if buildInProgress(workAbs) {
		return "", fmt.Errorf("npm build devam ediyor; tamamlanmasını bekleyin")
	}
	if !needsBuild(workAbs, profile) {
		return "", nil
	}
	return NpmBuild(cfg, domain, pathSegment)
}
