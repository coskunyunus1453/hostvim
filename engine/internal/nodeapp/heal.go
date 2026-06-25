package nodeapp

import (
	"fmt"
	"net"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"time"

	"panelze/engine/internal/config"
	"panelze/engine/internal/sites"
)

// HealResult otomatik onarım adımları.
type HealResult struct {
	Steps   []string `json:"steps"`
	Healthy bool     `json:"healthy"`
	Message string   `json:"message,omitempty"`
}

func isPortListening(port int) bool {
	if port <= 0 {
		return false
	}
	addr := net.JoinHostPort("127.0.0.1", strconv.Itoa(port))
	conn, err := net.DialTimeout("tcp", addr, 500*time.Millisecond)
	if err != nil {
		return false
	}
	_ = conn.Close()
	return true
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

func ensureProductionStartScript(cfg *config.Config, sc SiteScope, meta *sites.SiteMeta, workAbs string) (bool, error) {
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
			if err := sc.writeMeta(cfg.Paths.WebRoot, meta); err != nil {
				return true, err
			}
			return true, nil
		}
	}
	return false, nil
}

// Heal nginx proxy, bağımlılıklar, build ve PM2 sürecini otomatik düzeltir.
func Heal(cfg *config.Config, domain, pathSegment string) (*HealResult, error) {
	if !cfg.Hosting.ManageNodeApps {
		return nil, fmt.Errorf("manage_node_apps devre dışı")
	}
	sc := scopeFrom(domain, pathSegment)
	meta, err := sc.readMeta(cfg.Paths.WebRoot)
	if err != nil || meta == nil {
		return nil, fmt.Errorf("site not found")
	}
	if meta.NodeApp == nil || !meta.NodeApp.Enabled {
		return nil, fmt.Errorf("node app not enabled")
	}

	res := &HealResult{Steps: []string{}}
	add := func(s string) { res.Steps = append(res.Steps, s) }

	workAbs, _, werr := sc.resolveWorkAbs(cfg.Paths.WebRoot, meta.NodeApp.WorkDir)
	if werr != nil {
		return nil, werr
	}

	if changed, err := ensureProductionStartScript(cfg, sc, meta, workAbs); err != nil {
		return nil, err
	} else if changed {
		add("start_script: dev → production")
		meta, _ = sc.readMeta(cfg.Paths.WebRoot)
	}

	port := AllocateListenPort(cfg, sc, meta.NodeApp.Profile, 0, sc.siteBase(cfg.Paths.WebRoot), meta.NodeApp.WorkDir)
	if meta.NodeApp.ListenPort != port {
		meta.NodeApp.ListenPort = port
		_ = sc.writeMeta(cfg.Paths.WebRoot, meta)
		add("listen_port: " + strconv.Itoa(port))
	}

	if err := sc.applyWebServer(cfg, meta); err != nil {
		return nil, fmt.Errorf("nginx vhost: %w", err)
	}
	add("nginx vhost yenilendi (Node reverse proxy + OAuth başlıkları)")

	if needsInstall(workAbs) {
		if _, err := NpmInstall(cfg, domain, pathSegment, false); err != nil {
			return res, fmt.Errorf("npm install: %w", err)
		}
		add("npm install")
	}

	profile := meta.NodeApp.Profile
	if profile == "" {
		profile = meta.AppProfile
	}
	if needsBuild(workAbs, profile) {
		if _, err := NpmBuild(cfg, domain, pathSegment); err != nil {
			return res, fmt.Errorf("npm build: %w", err)
		}
		add("npm run build")
	}

	st, _ := StatusOf(cfg, sc, meta)
	if isPortListening(port) || (st.Running && waitForListen(port, 45*time.Second) == nil) {
		add("pm2 zaten dinliyor: " + strconv.Itoa(port))
	} else {
		if _, err := startWithPrep(cfg, domain, pathSegment); err != nil {
			return res, fmt.Errorf("start: %w", err)
		}
		add("pm2 start")
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
func ensureListening(cfg *config.Config, sc SiteScope) error {
	meta, err := sc.readMeta(cfg.Paths.WebRoot)
	if err != nil || meta == nil || meta.NodeApp == nil {
		return fmt.Errorf("site meta")
	}
	port := meta.NodeApp.ListenPort
	if port <= 0 {
		if p, _, serr := syncNodeListenPort(cfg, sc, meta); serr == nil {
			port = p
		}
	}
	if port <= 0 {
		port = AllocateListenPort(cfg, sc, meta.NodeApp.Profile, 0, sc.siteBase(cfg.Paths.WebRoot), meta.NodeApp.WorkDir)
	}
	if err := waitForListen(port, 90*time.Second); err == nil {
		return nil
	}
	// Uygulama package.json'daki -p ile farklı portta dinliyorsa meta/nginx'i düzelt.
	inferred := inferPortFromWorkDir(sc.siteBase(cfg.Paths.WebRoot), meta.NodeApp.WorkDir, meta.NodeApp.Profile)
	if inferred > 0 && inferred != port && isPortListening(inferred) {
		meta.NodeApp.ListenPort = inferred
		if werr := sc.writeMeta(cfg.Paths.WebRoot, meta); werr != nil {
			return err
		}
		if fresh, rerr := sc.readMeta(cfg.Paths.WebRoot); rerr == nil && fresh != nil {
			_ = sc.applyWebServer(cfg, fresh)
		}
		return nil
	}
	return err
}
