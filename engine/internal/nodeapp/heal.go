package nodeapp

import (
	"encoding/json"
	"fmt"
	"net"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"time"

	"hostvim/engine/internal/config"
	"hostvim/engine/internal/hosting"
	"hostvim/engine/internal/sites"
)

// HealResult otomatik onarım adımları.
type HealResult struct {
	Steps   []string `json:"steps"`
	Healthy bool     `json:"healthy"`
	Message string   `json:"message,omitempty"`
}

func waitForListen(port int, timeout time.Duration) error {
	if port <= 0 {
		return fmt.Errorf("invalid listen port")
	}
	addr := net.JoinHostPort("127.0.0.1", strconv.Itoa(port))
	deadline := time.Now().Add(timeout)
	for time.Now().Before(deadline) {
		conn, err := net.DialTimeout("tcp", addr, 2*time.Second)
		if err == nil {
			_ = conn.Close()
			return nil
		}
		time.Sleep(500 * time.Millisecond)
	}
	return fmt.Errorf("port %d not listening after %s", port, timeout)
}

func needsInstall(workAbs string) bool {
	st, err := os.Stat(filepath.Join(workAbs, "node_modules"))
	return err != nil || !st.IsDir()
}

func needsBuild(workAbs, profile string) bool {
	pkgPath := filepath.Join(workAbs, "package.json")
	b, err := os.ReadFile(pkgPath)
	if err != nil {
		return false
	}
	var pkg pkgJSON
	if jsonErr := json.Unmarshal(b, &pkg); jsonErr != nil {
		return false
	}
	if _, ok := pkg.Scripts["build"]; !ok {
		return false
	}
	profile = strings.ToLower(strings.TrimSpace(profile))
	switch profile {
	case "nextjs":
		return !pathIsDir(filepath.Join(workAbs, ".next"))
	default:
		return !pathIsDir(filepath.Join(workAbs, "dist")) &&
			!pathIsDir(filepath.Join(workAbs, "build")) &&
			!pathIsDir(filepath.Join(workAbs, ".output"))
	}
}

func pathIsDir(p string) bool {
	st, err := os.Stat(p)
	return err == nil && st.IsDir()
}

func ensureProductionStartScript(cfg *config.Config, domain string, meta *sites.SiteMeta, workAbs string) (bool, error) {
	if meta == nil || meta.NodeApp == nil {
		return false, nil
	}
	cur := strings.TrimSpace(meta.NodeApp.StartScript)
	if cur != "dev" {
		return false, nil
	}
	for _, pref := range []string{"start", "start:prod", "prod", "serve"} {
		if err := validateScriptExists(workAbs, pref); err == nil {
			meta.NodeApp.StartScript = pref
			if err := sites.WriteSiteMeta(cfg.Paths.WebRoot, domain, meta); err != nil {
				return true, err
			}
			return true, nil
		}
	}
	return false, nil
}

func reapplyWebServer(cfg *config.Config, domain string, meta *sites.SiteMeta) error {
	sock := ""
	return hosting.ApplyWebServer(cfg, domain, meta.DocumentRoot, meta, sock)
}

// Heal nginx proxy, bağımlılıklar, build ve PM2 sürecini otomatik düzeltir.
func Heal(cfg *config.Config, domain string) (*HealResult, error) {
	if !cfg.Hosting.ManageNodeApps {
		return nil, fmt.Errorf("manage_node_apps devre dışı")
	}
	meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
	if err != nil || meta == nil {
		return nil, fmt.Errorf("site not found")
	}
	if meta.NodeApp == nil || !meta.NodeApp.Enabled {
		return nil, fmt.Errorf("node app not enabled")
	}

	res := &HealResult{Steps: []string{}}
	add := func(s string) { res.Steps = append(res.Steps, s) }

	workAbs, _, werr := resolveWorkAbs(cfg.Paths.WebRoot, domain, meta.NodeApp.WorkDir)
	if werr != nil {
		return nil, werr
	}

	if changed, err := ensureProductionStartScript(cfg, domain, meta, workAbs); err != nil {
		return nil, err
	} else if changed {
		add("start_script: dev → production")
		meta, _ = sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
	}

	if err := reapplyWebServer(cfg, domain, meta); err != nil {
		return nil, fmt.Errorf("nginx vhost: %w", err)
	}
	add("nginx vhost yenilendi (Node reverse proxy)")

	if needsInstall(workAbs) {
		if _, err := NpmInstall(cfg, domain, false); err != nil {
			return res, fmt.Errorf("npm install: %w", err)
		}
		add("npm install")
	}

	profile := meta.NodeApp.Profile
	if profile == "" {
		profile = meta.AppProfile
	}
	if needsBuild(workAbs, profile) {
		if _, err := NpmBuild(cfg, domain); err != nil {
			return res, fmt.Errorf("npm build: %w", err)
		}
		add("npm run build")
	}

	port := meta.NodeApp.ListenPort
	if port <= 0 {
		port = DefaultPortForProfile(meta.NodeApp.Profile)
	}

	st, _ := StatusOf(cfg, domain, meta)
	if !st.Running {
		if _, err := Start(cfg, domain); err != nil {
			return res, fmt.Errorf("start: %w", err)
		}
		add("pm2 start")
	} else {
		if _, err := Restart(cfg, domain); err != nil {
			return res, fmt.Errorf("restart: %w", err)
		}
		add("pm2 restart")
	}

	if err := waitForListen(port, 90*time.Second); err != nil {
		res.Message = err.Error()
		return res, nil
	}
	add("port dinleniyor: " + strconv.Itoa(port))
	res.Healthy = true
	res.Message = "ok"
	return res, nil
}

// ensureListening Start/Restart sonrası port hazır olana kadar bekler.
func ensureListening(cfg *config.Config, domain string) error {
	meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
	if err != nil || meta == nil || meta.NodeApp == nil {
		return fmt.Errorf("site meta")
	}
	port := meta.NodeApp.ListenPort
	if port <= 0 {
		port = DefaultPortForProfile(meta.NodeApp.Profile)
	}
	return waitForListen(port, 90*time.Second)
}
