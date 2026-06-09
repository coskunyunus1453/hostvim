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

	"hostvim/engine/internal/config"
	"hostvim/engine/internal/hosting"
	"hostvim/engine/internal/sites"
)

// Status PM2 süreç durumu.
type Status struct {
	Running   bool   `json:"running"`
	PM2Name   string `json:"pm2_name"`
	ListenPort int   `json:"listen_port"`
	Profile   string `json:"profile"`
	StartScript string `json:"start_script"`
	WorkDir   string `json:"work_dir"`
	Status    string `json:"status,omitempty"`
	PID       int    `json:"pid,omitempty"`
	Uptime    string `json:"uptime,omitempty"`
}

// ConfigView panel için birleşik görünüm.
type ConfigView struct {
	Enabled     bool   `json:"enabled"`
	Profile     string `json:"profile"`
	AppProfile  string `json:"app_profile"`
	WorkDir     string `json:"work_dir"`
	StartScript string `json:"start_script"`
	ListenPort  int    `json:"listen_port"`
	AutoStart   bool   `json:"auto_start"`
	EnvFile     string `json:"env_file"`
	Status      Status `json:"status"`
}

func siteBase(webRoot, domain string) string {
	return filepath.Clean(filepath.Join(webRoot, domain, "public_html"))
}

func resolveWorkAbs(webRoot, domain, workDirRel string) (string, string, error) {
	base := siteBase(webRoot, domain)
	workDirRel = strings.TrimSpace(workDirRel)
	if workDirRel == "" {
		workDirRel = "."
	}
	if strings.Contains(workDirRel, "..") || filepath.IsAbs(workDirRel) {
		return "", "", fmt.Errorf("invalid work_dir")
	}
	workAbs := base
	if workDirRel != "." {
		workAbs = filepath.Join(base, workDirRel)
	}
	workAbs = filepath.Clean(workAbs)
	if !strings.HasPrefix(workAbs+string(os.PathSeparator), base+string(os.PathSeparator)) && workAbs != base {
		return "", "", fmt.Errorf("work_dir escapes site base")
	}
	return workAbs, workDirRel, nil
}

func pm2Home(cfg *config.Config) string {
	parent := filepath.Dir(filepath.Clean(cfg.Paths.WebRoot))
	if s := strings.TrimSpace(cfg.Hosting.Pm2Home); s != "" {
		return s
	}
	return filepath.Join(parent, "pm2")
}

func pm2Bin(cfg *config.Config) string {
	if s := strings.TrimSpace(cfg.Hosting.Pm2Path); s != "" {
		return s
	}
	return "pm2"
}

func npmBin(cfg *config.Config) string {
	if s := strings.TrimSpace(cfg.Hosting.NpmPath); s != "" {
		return s
	}
	return "npm"
}

func pm2Name(domain string) string {
	s := strings.ToLower(strings.TrimSpace(domain))
	s = strings.ReplaceAll(s, ".", "-")
	return "panelze-" + s
}

func runUser(cfg *config.Config) string {
	u := strings.TrimSpace(cfg.Hosting.Pm2RunUser)
	if u == "" {
		u = "www-data"
	}
	return u
}

func pm2Wrapper(cfg *config.Config) string {
	return "/usr/local/sbin/panelze-node-pm2"
}

func ensurePm2Home(cfg *config.Config) error {
	home := pm2Home(cfg)
	return os.MkdirAll(home, 0o750)
}

func pm2Cmd(cfg *config.Config, args ...string) *exec.Cmd {
	wrapper := pm2Wrapper(cfg)
	cmdArgs := append([]string{wrapper}, args...)
	cmd := exec.Command("sudo", cmdArgs...)
	cmd.Env = append(os.Environ(),
		"PANELZE_PM2_HOME="+pm2Home(cfg),
		"PANELZE_PM2_USER="+runUser(cfg),
		"PANELZE_PM2_BIN="+pm2Bin(cfg),
	)
	return cmd
}

func pm2Output(cfg *config.Config, args ...string) (string, error) {
	if err := ensurePm2Home(cfg); err != nil {
		return "", err
	}
	cmd := pm2Cmd(cfg, args...)
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

// GetConfig meta + durum döndürür.
func GetConfig(cfg *config.Config, domain string) (*ConfigView, error) {
	meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
	if err != nil || meta == nil {
		return nil, fmt.Errorf("site not found")
	}
	view := &ConfigView{
		AppProfile: strings.TrimSpace(meta.AppProfile),
	}
	if meta.NodeApp != nil {
		view.Enabled = meta.NodeApp.Enabled
		view.Profile = meta.NodeApp.Profile
		view.WorkDir = meta.NodeApp.WorkDir
		view.StartScript = meta.NodeApp.StartScript
		view.ListenPort = meta.NodeApp.ListenPort
		view.AutoStart = meta.NodeApp.AutoStart
		view.EnvFile = meta.NodeApp.EnvFile
	}
	if view.WorkDir == "" {
		view.WorkDir = "."
	}
	if view.Profile == "" {
		view.Profile = view.AppProfile
	}
	st, _ := StatusOf(cfg, domain, meta)
	view.Status = st
	return view, nil
}

// StatusOf PM2 durumunu okur.
func StatusOf(cfg *config.Config, domain string, meta *sites.SiteMeta) (Status, error) {
	st := Status{PM2Name: pm2Name(domain)}
	if meta != nil && meta.NodeApp != nil {
		st.ListenPort = meta.NodeApp.ListenPort
		st.Profile = meta.NodeApp.Profile
		st.StartScript = meta.NodeApp.StartScript
		st.WorkDir = meta.NodeApp.WorkDir
		if st.WorkDir == "" {
			st.WorkDir = "."
		}
	}
	if !cfg.Hosting.ManageNodeApps {
		return st, nil
	}
	out, err := pm2Output(cfg, "jlist")
	if err != nil {
		return st, nil
	}
	name := pm2Name(domain)
	// basit JSON arama — tam parse yerine hızlı eşleşme
	if !strings.Contains(out, `"name":"`+name+`"`) && !strings.Contains(out, `"name": "`+name+`"`) {
		return st, nil
	}
	st.Running = strings.Contains(out, `"status":"online"`) || strings.Contains(out, `"status": "online"`)
	st.Status = pm2StatusFromJlist(out, name)
	if st.Status == "" {
		if st.Running {
			st.Status = "online"
		} else {
			st.Status = "stopped"
		}
	}
	if strings.EqualFold(st.Status, "online") {
		st.Running = true
	}
	return st, nil
}

func pm2StatusFromJlist(jlist, name string) string {
	needle := `"name":"` + name + `"`
	if !strings.Contains(jlist, needle) {
		needle = `"name": "` + name + `"`
	}
	if !strings.Contains(jlist, needle) {
		return ""
	}
	idx := strings.Index(jlist, needle)
	chunk := jlist[idx:]
	if len(chunk) > 1200 {
		chunk = chunk[:1200]
	}
	for _, st := range []string{"errored", "stopped", "stopping", "launching", "online"} {
		if strings.Contains(chunk, `"status":"`+st+`"`) || strings.Contains(chunk, `"status": "`+st+`"`) {
			return st
		}
	}
	return ""
}

// UpdateConfig meta günceller, nginx vhost yeniler, isteğe bağlı auto_start.
func UpdateConfig(cfg *config.Config, domain string, patch *sites.NodeAppConfig, appProfile string) (*ConfigView, error) {
	if !cfg.Hosting.ManageNodeApps {
		return nil, fmt.Errorf("manage_node_apps devre dışı")
	}
	meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
	if err != nil || meta == nil {
		return nil, fmt.Errorf("site not found")
	}
	if meta.NodeApp == nil {
		meta.NodeApp = &sites.NodeAppConfig{}
	}
	if patch != nil {
		meta.NodeApp.Enabled = patch.Enabled
		if strings.TrimSpace(patch.Profile) != "" {
			meta.NodeApp.Profile = hosting.NormalizeAppProfile(patch.Profile)
		}
		if patch.WorkDir != "" {
			if _, _, werr := resolveWorkAbs(cfg.Paths.WebRoot, domain, patch.WorkDir); werr != nil {
				return nil, werr
			}
			meta.NodeApp.WorkDir = strings.TrimSpace(patch.WorkDir)
		}
		if strings.TrimSpace(patch.StartScript) != "" {
			meta.NodeApp.StartScript = strings.TrimSpace(patch.StartScript)
		}
		if patch.ListenPort > 0 {
			meta.NodeApp.ListenPort = patch.ListenPort
		}
		meta.NodeApp.AutoStart = patch.AutoStart
		if patch.EnvFile != "" {
			meta.NodeApp.EnvFile = strings.TrimSpace(patch.EnvFile)
		}
	}
	if strings.TrimSpace(appProfile) != "" {
		meta.AppProfile = hosting.NormalizeAppProfile(appProfile)
		if meta.NodeApp.Profile == "" {
			meta.NodeApp.Profile = meta.AppProfile
		}
	}
	if meta.NodeApp.WorkDir == "" {
		meta.NodeApp.WorkDir = "."
	}
	if meta.NodeApp.ListenPort <= 0 {
		meta.NodeApp.ListenPort = DefaultPortForProfile(meta.NodeApp.Profile)
	}
	if meta.NodeApp.StartScript == "" {
		meta.NodeApp.StartScript = "start"
	}

	if err := sites.WriteSiteMeta(cfg.Paths.WebRoot, domain, meta); err != nil {
		return nil, err
	}

	sock := ""
	if err := hosting.ApplyWebServer(cfg, domain, meta.DocumentRoot, meta, sock); err != nil {
		return nil, err
	}

	if meta.NodeApp.AutoStart && meta.NodeApp.Enabled {
		if _, err := Start(cfg, domain); err != nil {
			return nil, err
		}
	}

	return GetConfig(cfg, domain)
}

// Start PM2 ile uygulamayı başlatır.
func Start(cfg *config.Config, domain string) (string, error) {
	if !cfg.Hosting.ManageNodeApps {
		return "", fmt.Errorf("manage_node_apps devre dışı")
	}
	meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
	if err != nil || meta == nil || meta.NodeApp == nil || !meta.NodeApp.Enabled {
		return "", fmt.Errorf("node app not enabled")
	}
	workAbs, _, err := resolveWorkAbs(cfg.Paths.WebRoot, domain, meta.NodeApp.WorkDir)
	if err != nil {
		return "", err
	}
	if err := validateStartScript(workAbs, meta.NodeApp.StartScript); err != nil {
		return "", err
	}

	name := pm2Name(domain)
	_, _ = pm2Output(cfg, "delete", name)

	port := meta.NodeApp.ListenPort
	if port <= 0 {
		port = DefaultPortForProfile(meta.NodeApp.Profile)
	}

	env := buildStartEnv(cfg.Paths.WebRoot, domain, meta, workAbs, port)

	script := meta.NodeApp.StartScript
	args := []string{
		"start", npmBin(cfg),
		"--name", name,
		"--cwd", workAbs,
		"--max-restarts", "100",
		"--restart-delay", "4000",
		"--min-uptime", "5000",
		"--max-memory-restart", "512M",
		"--time",
		"--",
		"run", script,
	}
	// pm2 start npm --name ... --cwd ... -- run start
	cmd := pm2Cmd(cfg, args...)
	cmd.Env = append(os.Environ(), env...)
	out, err := cmd.CombinedOutput()
	s := strings.TrimSpace(string(out))
	if err != nil {
		if s != "" {
			return s, fmt.Errorf("%w — %s", err, s)
		}
		return s, err
	}
	_, _ = pm2Output(cfg, "save")
	if err := ensureListening(cfg, domain); err != nil {
		return s, fmt.Errorf("started but not listening: %w", err)
	}
	return s, nil
}

// Stop PM2 sürecini durdurur.
func Stop(cfg *config.Config, domain string) (string, error) {
	if !cfg.Hosting.ManageNodeApps {
		return "", fmt.Errorf("manage_node_apps devre dışı")
	}
	return pm2Output(cfg, "delete", pm2Name(domain))
}

// Restart yeniden başlatır.
func Restart(cfg *config.Config, domain string) (string, error) {
	if !cfg.Hosting.ManageNodeApps {
		return "", fmt.Errorf("manage_node_apps devre dışı")
	}
	name := pm2Name(domain)
	out, err := pm2Output(cfg, "restart", name)
	if err != nil && strings.Contains(err.Error(), "not found") {
		return Start(cfg, domain)
	}
	if err == nil {
		if listenErr := ensureListening(cfg, domain); listenErr != nil {
			return out, fmt.Errorf("restarted but not listening: %w", listenErr)
		}
	}
	return out, err
}

// RemoveSite cleanup on delete.
func RemoveSite(cfg *config.Config, domain string) {
	if !cfg.Hosting.ManageNodeApps {
		return
	}
	_, _ = Stop(cfg, domain)
}

func validateStartScript(workAbs, script string) error {
	return validateScriptExists(workAbs, script)
}

func validateScriptExists(workAbs, script string) error {
	script = strings.TrimSpace(script)
	if script == "" {
		return fmt.Errorf("start_script required")
	}
	if strings.ContainsAny(script, ";&|`$") {
		return fmt.Errorf("invalid start_script")
	}
	b, err := os.ReadFile(filepath.Join(workAbs, "package.json"))
	if err != nil {
		return fmt.Errorf("package.json not found")
	}
	var pkg pkgJSON
	if err := json.Unmarshal(b, &pkg); err != nil {
		return fmt.Errorf("package.json invalid")
	}
	if _, ok := pkg.Scripts[script]; !ok {
		return fmt.Errorf("script %q not in package.json", script)
	}
	return nil
}

// NpmInstall npm ci/install çalıştırır.
func NpmInstall(cfg *config.Config, domain string, useCI bool) (string, error) {
	if !cfg.Hosting.ManageNodeApps {
		return "", fmt.Errorf("manage_node_apps devre dışı")
	}
	meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
	if err != nil || meta == nil {
		return "", fmt.Errorf("site not found")
	}
	workDir := "."
	if meta.NodeApp != nil && meta.NodeApp.WorkDir != "" {
		workDir = meta.NodeApp.WorkDir
	}
	workAbs, _, err := resolveWorkAbs(cfg.Paths.WebRoot, domain, workDir)
	if err != nil {
		return "", err
	}
	maxSec := cfg.Hosting.ToolsMaxSeconds
	if maxSec <= 0 {
		maxSec = 600
	}
	ctx, cancel := context.WithTimeout(context.Background(), time.Duration(maxSec)*time.Second)
	defer cancel()
	var args []string
	if useCI {
		args = []string{"ci", "--no-audit", "--no-fund"}
	} else {
		args = []string{"install", "--no-audit", "--no-fund"}
	}
	cmd := exec.CommandContext(ctx, npmBin(cfg), args...)
	cmd.Dir = workAbs
	cmd.Env = append(os.Environ(), "NODE_ENV=production")
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

// NpmBuild npm run build.
func NpmBuild(cfg *config.Config, domain string) (string, error) {
	if !cfg.Hosting.ManageNodeApps {
		return "", fmt.Errorf("manage_node_apps devre dışı")
	}
	meta, err := sites.ReadSiteMeta(cfg.Paths.WebRoot, domain)
	if err != nil || meta == nil {
		return "", fmt.Errorf("site not found")
	}
	workDir := "."
	if meta.NodeApp != nil && meta.NodeApp.WorkDir != "" {
		workDir = meta.NodeApp.WorkDir
	}
	workAbs, _, err := resolveWorkAbs(cfg.Paths.WebRoot, domain, workDir)
	if err != nil {
		return "", err
	}
	if err := validateScriptExists(workAbs, "build"); err != nil {
		return "", err
	}
	maxSec := cfg.Hosting.ToolsMaxSeconds
	if maxSec <= 0 {
		maxSec = 900
	}
	ctx, cancel := context.WithTimeout(context.Background(), time.Duration(maxSec)*time.Second)
	defer cancel()
	cmd := exec.CommandContext(ctx, npmBin(cfg), "run", "build")
	cmd.Dir = workAbs
	cmd.Env = append(os.Environ(), "NODE_ENV=production")
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

// AutoConfigureFromDetect algılama sonucunu uygular (aaPanel benzeri tek tık).
func AutoConfigureFromDetect(cfg *config.Config, domain, appProfile string) (*ConfigView, error) {
	base := siteBase(cfg.Paths.WebRoot, domain)
	det, err := DetectBest(base)
	if err != nil {
		return nil, err
	}
	if !det.HasPackageJSON {
		return nil, fmt.Errorf("package.json not found")
	}
	profile := appProfile
	if profile == "" {
		profile = det.Profile
	}
	patch := &sites.NodeAppConfig{
		Enabled:     true,
		Profile:     profile,
		WorkDir:     det.WorkDir,
		StartScript: det.StartScript,
		ListenPort:  det.SuggestedPort,
		AutoStart:   true,
	}
	view, err := UpdateConfig(cfg, domain, patch, profile)
	if err != nil {
		return nil, err
	}
	if _, healErr := Heal(cfg, domain); healErr != nil {
		return view, healErr
	}
	return GetConfig(cfg, domain)
}
