package sites

import (
	"encoding/json"
	"os"
	"path/filepath"
	"strings"
)

// NodeAppConfig — Node.js uygulaması (PM2 + nginx reverse proxy).
type NodeAppConfig struct {
	Enabled     bool   `json:"enabled,omitempty"`
	Profile     string `json:"profile,omitempty"`      // nextjs, nuxt, strapi, n8n, node
	WorkDir     string `json:"work_dir,omitempty"`     // public_html göreli (varsayılan ".")
	StartScript string `json:"start_script,omitempty"` // package.json scripts anahtarı
	ListenPort  int    `json:"listen_port,omitempty"`
	AutoStart   bool   `json:"auto_start,omitempty"`
	EnvFile     string `json:"env_file,omitempty"` // public_html göreli, örn. .env
}

// SiteMeta — alan adı başına engine tarafından saklanan küçük durum (silme / PHP değişimi / SSL için).
type SiteMeta struct {
	PHPVersion   string   `json:"php_version"`
	DocumentRoot string   `json:"document_root"`
	ServerType   string   `json:"server_type"`
	SSLEnabled   bool     `json:"ssl_enabled"`
	// ForceHTTPS — SSL aktifken HTTP→HTTPS yönlendirmesi; nil = true (geriye dönük uyumluluk).
	ForceHTTPS   *bool    `json:"force_https,omitempty"`
	// PerformanceMode: boş = kapalı; "standard" = gzip + statik cache header preset (nginx vhost).
	PerformanceMode string `json:"performance_mode,omitempty"`
	Aliases      []string `json:"aliases,omitempty"` // Örn. example.net — aynı belge kökü, vhost server_name
	Hostname     string   `json:"hostname,omitempty"` // Alt site meta dosyalarında FQDN (silme / vhost için)
	AppProfile   string         `json:"app_profile,omitempty"` // laravel, nextjs, node, ...
	NodeApp      *NodeAppConfig `json:"node_app,omitempty"`
	RedirectRules []RedirectRule `json:"redirect_rules,omitempty"`
	// PanelKafes — site başına Linux kullanıcı izolasyonu.
	CageEnabled bool   `json:"cage_enabled,omitempty"`
	CageUser    string `json:"cage_user,omitempty"`
	// Paket bazlı kaynak limiti (PanelKafes cgroup: CPUQuota / MemoryMax).
	// 0 = engine global varsayılanı kullan (geriye dönük uyumluluk).
	CPUPercent int `json:"cpu_percent,omitempty"`
	MemoryMB   int `json:"memory_mb,omitempty"`
	// ShellFunctions true ise site PHP-FPM havuzunda exec/shell_exec/system vb.
	// tehlikeli fonksiyonlar AÇIK bırakılır (varsayılan: kapalı — güvenli).
	// Yalnızca bu fonksiyonlara ihtiyaç duyan (ör. CLI çağıran) uygulamalar için
	// yönetici tarafından bilinçli olarak açılmalıdır.
	ShellFunctions bool `json:"shell_functions,omitempty"`
}

// ForceHTTPSRedirect SSL etkin sitelerde HTTP→HTTPS yönlendirmesi isteniyor mu (varsayılan: true).
func (m *SiteMeta) ForceHTTPSRedirect() bool {
	if m == nil || !m.SSLEnabled {
		return false
	}
	if m.ForceHTTPS == nil {
		return true
	}
	return *m.ForceHTTPS
}

// NodeProxyPort etkin Node uygulaması için nginx upstream portunu döndürür; yoksa 0.
func (m *SiteMeta) NodeProxyPort() int {
	if m == nil || m.NodeApp == nil || !m.NodeApp.Enabled || m.NodeApp.ListenPort <= 0 {
		return 0
	}
	return m.NodeApp.ListenPort
}

// IsNodeProfile uygulama profili Node tabanlı mı.
func IsNodeProfile(profile string) bool {
	switch strings.ToLower(strings.TrimSpace(profile)) {
	case "nextjs", "nuxt", "strapi", "n8n", "node":
		return true
	default:
		return false
	}
}

const metaDirName = ".panelze"
const legacyMetaDirName = ".panelsar"
const hostvimMetaDirName = ".hostvim"

func metaDir(webRoot, domain string) string {
	return filepath.Join(webRoot, domain, metaDirName)
}

// SitePrivateDir alan adı altındaki .panelze dizinini döndürür (site.json ile aynı konum).
// Tipik kurulumda bu yol, web sunucusunun belge kökünün (ör. public_html) dışında kalır; HTTP ile doğrudan servis edilmez.
func SitePrivateDir(webRoot, domain string) string {
	if domain == "" || strings.Contains(domain, "..") {
		return ""
	}
	return metaDir(webRoot, domain)
}

func legacySiteMetaDir(webRoot, domain string) string {
	return filepath.Join(webRoot, domain, legacyMetaDirName)
}

func metaFile(webRoot, domain string) string {
	return filepath.Join(metaDir(webRoot, domain), "site.json")
}

func legacyMetaFile(webRoot, domain string) string {
	return filepath.Join(legacySiteMetaDir(webRoot, domain), "site.json")
}

func hostvimMetaFile(webRoot, domain string) string {
	return filepath.Join(webRoot, domain, hostvimMetaDirName, "site.json")
}

func readSiteMetaBytes(webRoot, domain string) ([]byte, error) {
	candidates := []string{
		metaFile(webRoot, domain),
		legacyMetaFile(webRoot, domain),
		hostvimMetaFile(webRoot, domain),
	}
	var lastErr error
	for _, p := range candidates {
		b, err := os.ReadFile(p)
		if err == nil {
			return b, nil
		}
		if !os.IsNotExist(err) {
			lastErr = err
		}
	}
	if lastErr != nil {
		return nil, lastErr
	}
	return nil, os.ErrNotExist
}

// ReadSiteMeta mevcut site meta verisini okur; yoksa nil, nil döner.
func ReadSiteMeta(webRoot, domain string) (*SiteMeta, error) {
	if domain == "" || strings.Contains(domain, "..") {
		return nil, nil
	}
	b, err := readSiteMetaBytes(webRoot, domain)
	if err != nil {
		if os.IsNotExist(err) {
			return nil, nil
		}
		return nil, err
	}
	var m SiteMeta
	if err := json.Unmarshal(b, &m); err != nil {
		return nil, err
	}
	return &m, nil
}

// WriteSiteMeta .panelze/site.json yazar.
func WriteSiteMeta(webRoot, domain string, m *SiteMeta) error {
	if domain == "" || strings.Contains(domain, "..") {
		return nil
	}
	dir := metaDir(webRoot, domain)
	if err := os.MkdirAll(dir, 0o750); err != nil {
		return err
	}
	b, err := json.MarshalIndent(m, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(metaFile(webRoot, domain), b, 0o640)
}
