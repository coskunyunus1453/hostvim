package nodeapp

import (
	"encoding/json"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strings"

	"panelze/engine/internal/config"
	"panelze/engine/internal/hosting"
	"panelze/engine/internal/sites"
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
	return scopeFrom(domain, "").siteBase(webRoot)
}

func resolveWorkAbs(webRoot, domain, workDirRel string) (string, string, error) {
	return scopeFrom(domain, "").resolveWorkAbs(webRoot, workDirRel)
}

func resolveWorkAbsScoped(webRoot string, sc SiteScope, workDirRel string) (string, string, error) {
	return sc.resolveWorkAbs(webRoot, workDirRel)
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
	return scopeFrom(domain, "").pm2Name()
}

func pm2NameScoped(sc SiteScope) string {
	return sc.pm2Name()
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
func GetConfig(cfg *config.Config, domain, pathSegment string) (*ConfigView, error) {
	sc := scopeFrom(domain, pathSegment)
	meta, err := sc.readMeta(cfg.Paths.WebRoot)
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
	st, _ := StatusOf(cfg, sc, meta)
	view.Status = st
	return view, nil
}

// StatusOf PM2 durumunu okur.
func StatusOf(cfg *config.Config, sc SiteScope, meta *sites.SiteMeta) (Status, error) {
	st := Status{PM2Name: sc.pm2Name()}
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
	name := sc.pm2Name()
	if !strings.Contains(out, `"name":"`+name+`"`) && !strings.Contains(out, `"name": "`+name+`"`) {
		return st, nil
	}
	st.Status = pm2StatusFromJlist(out, name)
	st.Running = strings.EqualFold(st.Status, "online")
	if st.Status == "" {
		st.Status = "stopped"
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
func UpdateConfig(cfg *config.Config, domain, pathSegment string, patch *sites.NodeAppConfig, appProfile string) (*ConfigView, error) {
	if !cfg.Hosting.ManageNodeApps {
		return nil, fmt.Errorf("manage_node_apps devre dışı")
	}
	sc := scopeFrom(domain, pathSegment)
	meta, err := sc.readMeta(cfg.Paths.WebRoot)
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
			if _, _, werr := sc.resolveWorkAbs(cfg.Paths.WebRoot, patch.WorkDir); werr != nil {
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
	if meta.NodeApp.StartScript == "" {
		meta.NodeApp.StartScript = "start"
	}
	if meta.NodeApp.EnvFile == "" {
		meta.NodeApp.EnvFile = ".env"
	}
	preferred := meta.NodeApp.ListenPort
	meta.NodeApp.ListenPort = AllocateListenPort(
		cfg,
		sc,
		meta.NodeApp.Profile,
		preferred,
		sc.siteBase(cfg.Paths.WebRoot),
		meta.NodeApp.WorkDir,
	)

	if err := sc.writeMeta(cfg.Paths.WebRoot, meta); err != nil {
		return nil, err
	}

	if err := sc.applyWebServer(cfg, meta); err != nil {
		return nil, err
	}

	if meta.NodeApp.AutoStart && meta.NodeApp.Enabled {
		if _, err := startWithPrep(cfg, domain, pathSegment); err != nil {
			return nil, err
		}
	}

	return GetConfig(cfg, domain, pathSegment)
}

// StartWithPrep gerekirse build alır, ardından PM2 ile başlatır.
func StartWithPrep(cfg *config.Config, domain, pathSegment string) (string, error) {
	return startWithPrep(cfg, domain, pathSegment)
}

func startWithPrep(cfg *config.Config, domain, pathSegment string) (string, error) {
	sc := scopeFrom(domain, pathSegment)
	meta, err := sc.readMeta(cfg.Paths.WebRoot)
	if err != nil || meta == nil || meta.NodeApp == nil {
		return "", fmt.Errorf("site not found")
	}
	workAbs, _, err := sc.resolveWorkAbs(cfg.Paths.WebRoot, meta.NodeApp.WorkDir)
	if err != nil {
		return "", err
	}
	profile := meta.NodeApp.Profile
	if profile == "" {
		profile = meta.AppProfile
	}
	if _, err := ensureProductionReady(cfg, domain, pathSegment, workAbs, profile); err != nil {
		return "", err
	}
	return Start(cfg, domain, pathSegment)
}

// Start PM2 ile uygulamayı başlatır.
func Start(cfg *config.Config, domain, pathSegment string) (string, error) {
	if !cfg.Hosting.ManageNodeApps {
		return "", fmt.Errorf("manage_node_apps devre dışı")
	}
	sc := scopeFrom(domain, pathSegment)
	meta, err := sc.readMeta(cfg.Paths.WebRoot)
	if err != nil || meta == nil || meta.NodeApp == nil || !meta.NodeApp.Enabled {
		return "", fmt.Errorf("node app not enabled")
	}
	workAbs, _, err := sc.resolveWorkAbs(cfg.Paths.WebRoot, meta.NodeApp.WorkDir)
	if err != nil {
		return "", err
	}
	if err := validateStartScript(workAbs, meta.NodeApp.StartScript); err != nil {
		return "", err
	}
	profile := meta.NodeApp.Profile
	if profile == "" {
		profile = meta.AppProfile
	}
	if profileNeedsProductionBuild(profile) && !hasProductionBuild(workAbs, profile) {
		return "", fmt.Errorf("production build eksik veya tamamlanmamış; önce npm run build çalıştırın")
	}
	if buildInProgress(workAbs) {
		return "", fmt.Errorf("npm build devam ediyor; tamamlanmasını bekleyin")
	}

	name := sc.pm2Name()
	st, _ := StatusOf(cfg, sc, meta)
	if st.Running {
		_, _ = pm2Output(cfg, "stop", name)
	} else {
		_, _ = pm2Output(cfg, "delete", name)
	}

	if _, _, err := syncNodeListenPort(cfg, sc, meta); err != nil {
		return "", err
	}
	meta, err = sc.readMeta(cfg.Paths.WebRoot)
	if err != nil || meta == nil || meta.NodeApp == nil {
		return "", fmt.Errorf("site meta")
	}
	port := meta.NodeApp.ListenPort
	if port <= 0 {
		return "", fmt.Errorf("listen port not configured")
	}
	if err := sc.applyWebServer(cfg, meta); err != nil {
		return "", fmt.Errorf("vhost: %w", err)
	}

	env := buildStartEnv(cfg.Paths.WebRoot, sc, meta, workAbs, port)

	plan, err := buildPm2StartPlan(workAbs, profile, meta.NodeApp.StartScript, port)
	if err != nil {
		return "", err
	}
	args := []string{
		"start", plan.Program,
		"-f",
		"--name", name,
		"--cwd", workAbs,
	}
	if len(plan.Args) > 0 {
		args = append(args, "--")
		args = append(args, plan.Args...)
	}
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
	if err := ensureListening(cfg, sc); err != nil {
		return s, fmt.Errorf("started but not listening: %w", err)
	}
	return s, nil
}

// Stop PM2 sürecini durdurur.
func Stop(cfg *config.Config, domain, pathSegment string) (string, error) {
	if !cfg.Hosting.ManageNodeApps {
		return "", fmt.Errorf("manage_node_apps devre dışı")
	}
	return pm2Output(cfg, "delete", scopeFrom(domain, pathSegment).pm2Name())
}

// Restart yeniden başlatır.
func Restart(cfg *config.Config, domain, pathSegment string) (string, error) {
	if !cfg.Hosting.ManageNodeApps {
		return "", fmt.Errorf("manage_node_apps devre dışı")
	}
	sc := scopeFrom(domain, pathSegment)
	name := sc.pm2Name()
	out, err := pm2Output(cfg, "restart", name)
	if err != nil && strings.Contains(err.Error(), "not found") {
		return startWithPrep(cfg, domain, pathSegment)
	}
	if err == nil {
		if listenErr := ensureListening(cfg, sc); listenErr != nil {
			return out, fmt.Errorf("restarted but not listening: %w", listenErr)
		}
	}
	return out, err
}

// RemoveSite cleanup on delete.
func RemoveSite(cfg *config.Config, domain, pathSegment string) {
	if !cfg.Hosting.ManageNodeApps {
		return
	}
	_, _ = Stop(cfg, domain, pathSegment)
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
func NpmInstall(cfg *config.Config, domain, pathSegment string, useCI bool) (string, error) {
	if !cfg.Hosting.ManageNodeApps {
		return "", fmt.Errorf("manage_node_apps devre dışı")
	}
	sc := scopeFrom(domain, pathSegment)
	meta, err := sc.readMeta(cfg.Paths.WebRoot)
	if err != nil || meta == nil {
		return "", fmt.Errorf("site not found")
	}
	workDir := "."
	if meta.NodeApp != nil && meta.NodeApp.WorkDir != "" {
		workDir = meta.NodeApp.WorkDir
	}
	workAbs, _, err := sc.resolveWorkAbs(cfg.Paths.WebRoot, workDir)
	if err != nil {
		return "", err
	}
	maxSec := cfg.Hosting.ToolsMaxSeconds
	if maxSec <= 0 {
		maxSec = 600
	}
	var args []string
	if useCI {
		args = []string{"ci", "--no-audit", "--no-fund"}
	} else {
		args = []string{"install", "--no-audit", "--no-fund"}
	}
	return runNpmAsSiteUser(cfg, workAbs, maxSec, args...)
}

// NpmBuild npm run build.
func NpmBuild(cfg *config.Config, domain, pathSegment string) (string, error) {
	if !cfg.Hosting.ManageNodeApps {
		return "", fmt.Errorf("manage_node_apps devre dışı")
	}
	sc := scopeFrom(domain, pathSegment)
	meta, err := sc.readMeta(cfg.Paths.WebRoot)
	if err != nil || meta == nil {
		return "", fmt.Errorf("site not found")
	}
	workDir := "."
	if meta.NodeApp != nil && meta.NodeApp.WorkDir != "" {
		workDir = meta.NodeApp.WorkDir
	}
	workAbs, _, err := sc.resolveWorkAbs(cfg.Paths.WebRoot, workDir)
	if err != nil {
		return "", err
	}
	if err := validateScriptExists(workAbs, "build"); err != nil {
		return "", err
	}
	if buildInProgress(workAbs) {
		return "", fmt.Errorf("npm build zaten çalışıyor")
	}
	release, err := acquireBuildLock(workAbs)
	if err != nil {
		return "", fmt.Errorf("build lock: %w", err)
	}
	defer release()
	maxSec := cfg.Hosting.ToolsMaxSeconds
	if maxSec <= 0 {
		maxSec = 900
	}
	out, err := runNpmAsSiteUser(cfg, workAbs, maxSec, "run", "build")
	if err != nil {
		return out, err
	}
	ensureWritableArtifacts(cfg, workAbs)
	return out, nil
}

// AutoConfigureFromDetect algılama sonucunu uygular (aaPanel benzeri tek tık).
func AutoConfigureFromDetect(cfg *config.Config, domain, pathSegment, appProfile string) (*ConfigView, error) {
	sc := scopeFrom(domain, pathSegment)
	base := sc.siteBase(cfg.Paths.WebRoot)
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
	port := AllocateListenPort(cfg, sc, profile, det.SuggestedPort, base, det.WorkDir)
	patch := &sites.NodeAppConfig{
		Enabled:     true,
		Profile:     profile,
		WorkDir:     det.WorkDir,
		StartScript: det.StartScript,
		ListenPort:  port,
		AutoStart:   true,
		EnvFile:     ".env",
	}
	view, err := UpdateConfig(cfg, domain, pathSegment, patch, profile)
	if err != nil {
		return nil, err
	}
	if _, healErr := Heal(cfg, domain, pathSegment); healErr != nil {
		return view, healErr
	}
	return GetConfig(cfg, domain, pathSegment)
}
