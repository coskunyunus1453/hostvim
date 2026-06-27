package sitecage

import (
	"encoding/json"
	"fmt"
	"os/exec"
	"strings"

	"panelze/engine/internal/config"
)

const defaultHelper = "/usr/local/sbin/panelze-site-cage"

// Config PanelKafes host betiği ayarları.
type Config struct {
	Enabled          bool
	Group            string
	UserPrefix       string
	EngineUser       string
	DefaultCPUPercent int
	DefaultMemoryMB   int
	DefaultMaxChildren int
	DefaultMemoryLimit string
	Helper           string
}

// Status tek site PanelKafes durumu.
type Status struct {
	Domain     string `json:"domain"`
	CageUser   string `json:"cage_user"`
	UserExists bool   `json:"user_exists"`
	SiteExists bool   `json:"site_exists"`
	Owner      string `json:"owner"`
	Group      string `json:"group"`
}

// ApplyResult toplu uygulama satırı.
type ApplyResult struct {
	Domain  string `json:"domain"`
	OK      bool   `json:"ok"`
	Message string `json:"message,omitempty"`
	CageUser string `json:"cage_user,omitempty"`
}

func FromHosting(cfg *config.Config) Config {
	h := cfg.Hosting
	helper := strings.TrimSpace(h.SiteCageHelper)
	if helper == "" {
		helper = defaultHelper
	}
	memLim := strings.TrimSpace(h.SiteCageDefaultMemoryLimit)
	if memLim == "" {
		memLim = "256M"
	}
	maxCh := h.SiteCageDefaultMaxChildren
	if maxCh <= 0 {
		maxCh = 20
	}
	cpu := h.SiteCageDefaultCPUPercent
	if cpu <= 0 {
		cpu = 100
	}
	memMB := h.SiteCageDefaultMemoryMB
	if memMB <= 0 {
		memMB = 1024
	}
	grp := strings.TrimSpace(h.SiteCageGroup)
	if grp == "" {
		grp = "panelze-hosting"
	}
	prefix := strings.TrimSpace(h.SiteCageUserPrefix)
	if prefix == "" {
		prefix = "pk"
	}
	engineUser := strings.TrimSpace(h.SiteCageEngineUser)
	if engineUser == "" {
		engineUser = "www-data"
	}

	return Config{
		Enabled:            h.SiteCageEnabled,
		Group:              grp,
		UserPrefix:         prefix,
		EngineUser:         engineUser,
		DefaultCPUPercent:  cpu,
		DefaultMemoryMB:    memMB,
		DefaultMaxChildren: maxCh,
		DefaultMemoryLimit: memLim,
		Helper:             helper,
	}
}

// LinuxUserForDomain panel kullanıcı adı (betik ile aynı kural).
func LinuxUserForDomain(prefix, domain string) string {
	prefix = strings.TrimSpace(prefix)
	if prefix == "" {
		prefix = "pk"
	}
	domain = strings.ToLower(strings.TrimSpace(domain))
	slug := strings.Map(func(r rune) rune {
		if (r >= 'a' && r <= 'z') || (r >= '0' && r <= '9') || r == '-' {
			return r
		}
		if r == '.' {
			return '-'
		}
		return -1
	}, domain)
	if len(slug) > 48 {
		slug = slug[:48]
	}
	u := prefix + "-" + slug
	if len(u) > 32 {
		u = u[:32]
	}
	return u
}

// lastNonEmptyLine helper çıktısındaki son anlamlı satırı (kullanıcı adı) döner.
func lastNonEmptyLine(s string) string {
	lines := strings.Split(strings.TrimSpace(s), "\n")
	for i := len(lines) - 1; i >= 0; i-- {
		if l := strings.TrimSpace(lines[i]); l != "" {
			return l
		}
	}
	return ""
}

func (c Config) run(args ...string) (string, error) {
	cmd := exec.Command("sudo", append([]string{"-n", c.Helper}, args...)...)
	out, err := cmd.CombinedOutput()
	msg := strings.TrimSpace(string(out))
	if err != nil {
		if msg == "" {
			msg = err.Error()
		}
		return "", fmt.Errorf("%s", msg)
	}
	return msg, nil
}

// resolveLimits per-site cpu/mem değerlerini döndürür; <=0 ise global varsayılan kullanılır.
func (c Config) resolveLimits(cpuPercent, memMB int) (string, string) {
	cpu := c.DefaultCPUPercent
	if cpuPercent > 0 {
		cpu = cpuPercent
	}
	mem := c.DefaultMemoryMB
	if memMB > 0 {
		mem = memMB
	}
	return fmt.Sprintf("%d", cpu), fmt.Sprintf("%d", mem)
}

// Ensure site için Linux kullanıcısı + izinler + kaynak sınırları.
// cpuPercent/memMB <=0 ise engine global varsayılanı uygulanır.
func Ensure(c Config, webRoot, domain string, cpuPercent, memMB int) (string, error) {
	if !c.Enabled {
		return "", nil
	}
	domain = strings.ToLower(strings.TrimSpace(domain))
	if domain == "" || strings.Contains(domain, "..") {
		return "", fmt.Errorf("invalid domain")
	}
	webRoot = strings.TrimRight(strings.TrimSpace(webRoot), "/")
	if webRoot == "" {
		return "", fmt.Errorf("web root required")
	}
	if _, err := c.run("ensure-group"); err != nil {
		return "", err
	}
	user, err := c.run("ensure-user", domain, webRoot)
	if err != nil {
		return "", err
	}
	user = lastNonEmptyLine(user)
	if _, err := c.run("apply", domain, webRoot); err != nil {
		return "", err
	}
	cpu, mem := c.resolveLimits(cpuPercent, memMB)
	_, _ = c.run("limits", domain, cpu, mem)
	return user, nil
}

// Remove site Linux kullanıcısını ve slice/limits dosyalarını kaldırır.
func Remove(c Config, domain string) error {
	if !c.Enabled {
		return nil
	}
	domain = strings.ToLower(strings.TrimSpace(domain))
	if domain == "" {
		return nil
	}
	_, err := c.run("remove-user", domain)
	return err
}

// FetchStatus tek site durumu.
func FetchStatus(c Config, webRoot, domain string) (*Status, error) {
	domain = strings.ToLower(strings.TrimSpace(domain))
	raw, err := c.run("status", domain, strings.TrimRight(webRoot, "/"))
	if err != nil {
		return nil, err
	}
	line := strings.TrimSpace(raw)
	if i := strings.LastIndex(line, "{"); i >= 0 {
		line = line[i:]
	}
	var st Status
	if err := json.Unmarshal([]byte(line), &st); err != nil {
		return &Status{Domain: domain, CageUser: LinuxUserForDomain(c.UserPrefix, domain)}, nil
	}
	return &st, nil
}

// ApplyAll mevcut tüm sitelere PanelKafes uygular.
func ApplyAll(c Config, webRoot string) ([]ApplyResult, error) {
	if !c.Enabled {
		return nil, fmt.Errorf("panelkafes disabled")
	}
	webRoot = strings.TrimRight(strings.TrimSpace(webRoot), "/")
	if webRoot == "" {
		return nil, fmt.Errorf("web root required")
	}
	if _, err := c.run("apply-all", webRoot); err != nil {
		return nil, err
	}
	return []ApplyResult{{Domain: "*", OK: true, Message: "apply-all completed"}}, nil
}

// ApplyService site için ayrı PHP-FPM systemd servisi + kaynak slice'i kurar.
// CPU/RAM limiti (CPUQuota/MemoryMax) kernel cgroup ile gerçekten enforce edilir.
// WritePool ile pool.d'ye yazıldıktan SONRA çağrılmalıdır (pool'u kendi servisine taşır).
// cpuPercent/memMB <=0 ise engine global varsayılanı uygulanır.
func ApplyService(c Config, domain, phpVersion string, cpuPercent, memMB int) error {
	if !c.Enabled {
		return nil
	}
	domain = strings.ToLower(strings.TrimSpace(domain))
	if domain == "" || strings.Contains(domain, "..") {
		return fmt.Errorf("invalid domain")
	}
	phpVersion = strings.TrimSpace(phpVersion)
	if phpVersion == "" {
		phpVersion = "8.2"
	}
	cpu, mem := c.resolveLimits(cpuPercent, memMB)
	_, err := c.run("cage-service", domain, phpVersion, cpu, mem)
	return err
}

// RemoveService per-site FPM servisini kaldırır, pool'u paylaşımlı master'a geri taşır.
func RemoveService(c Config, domain, phpVersion string) error {
	domain = strings.ToLower(strings.TrimSpace(domain))
	if domain == "" {
		return nil
	}
	phpVersion = strings.TrimSpace(phpVersion)
	if phpVersion == "" {
		phpVersion = "8.2"
	}
	_, err := c.run("cage-service-remove", domain, phpVersion)
	return err
}

// ReloadFPM cage modunda php-fpm servisini root helper ile yeniden yükler.
func ReloadFPM(c Config, phpVersion string) error {
	phpVersion = strings.TrimSpace(phpVersion)
	if phpVersion == "" {
		return fmt.Errorf("php version required")
	}
	_, err := c.run("reload-fpm", phpVersion)
	return err
}

// PoolUser site meta veya domain için FPM çalıştırma kullanıcısı.
func PoolUser(c Config, cageUser, domain string) string {
	if strings.TrimSpace(cageUser) != "" {
		return strings.TrimSpace(cageUser)
	}
	if c.Enabled {
		return LinuxUserForDomain(c.UserPrefix, domain)
	}
	return ""
}
