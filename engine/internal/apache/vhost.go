package apache

import (
	"bytes"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"text/template"

	"panelze/engine/internal/config"
	"panelze/engine/internal/nginx"
)

const maxRawApacheVhostBytes = 512 << 10

func apachePrevPath(main string) string {
	if prev, ok := nginx.FindVhostPrevPath(main); ok {
		return prev
	}
	return main + ".panelze-prev"
}

const tplHTTP = `# Panelze — {{.Domain}} (Apache HTTP)
<VirtualHost *:{{.HTTPPort}}>
    ServerName {{.Domain}}
    ServerAlias {{.ServerAliasLine}}
    DocumentRoot {{.DocRoot}}
    RewriteEngine On
    RewriteRule ^/admin/assets/(.*)$ /assets/$1 [L]

    <Directory {{.DocRoot}}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Dotfile/Dotdir erişimi kapat (örn. .env, .git, .panelze). ACME challenge hariç.
    <LocationMatch "(^|/)\.(?!well-known(?:/|$))">
        Require all denied
    </LocationMatch>

    SetEnvIfNoCase Authorization "(.+)" HTTP_AUTHORIZATION=$1

    <FilesMatch "\.php$">
        SetHandler "proxy:unix:{{.PHPSocket}}|fcgi://localhost"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/panelze-{{.Domain}}-error.log
    CustomLog ${APACHE_LOG_DIR}/panelze-{{.Domain}}-access.log combined
</VirtualHost>
`

const tplHTTPS = `# Panelze — {{.Domain}} (Apache HTTPS)
{{if .ForceHTTPS}}
<VirtualHost *:{{.HTTPPort}}>
    ServerName {{.Domain}}
    ServerAlias {{.ServerAliasLine}}
    Redirect permanent / https://%{HTTP_HOST}%{REQUEST_URI}
</VirtualHost>
{{end}}

<VirtualHost *:443>
    ServerName {{.Domain}}
    ServerAlias {{.ServerAliasLine}}
    DocumentRoot {{.DocRoot}}
    RewriteEngine On
    RewriteRule ^/admin/assets/(.*)$ /assets/$1 [L]

    SSLEngine on
    SSLCertificateFile {{.SSLFullChain}}
    SSLCertificateKeyFile {{.SSLPrivKey}}

    Header always set Strict-Transport-Security "max-age=31536000"

    <Directory {{.DocRoot}}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Dotfile/Dotdir erişimi kapat (örn. .env, .git, .panelze). ACME challenge hariç.
    <LocationMatch "(^|/)\.(?!well-known(?:/|$))">
        Require all denied
    </LocationMatch>

    SetEnvIfNoCase Authorization "(.+)" HTTP_AUTHORIZATION=$1

    <FilesMatch "\.php$">
        SetHandler "proxy:unix:{{.PHPSocket}}|fcgi://localhost"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/panelze-{{.Domain}}-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/panelze-{{.Domain}}-ssl-access.log combined
</VirtualHost>
`

const tplHTTPSDual = `# Panelze — {{.Domain}} (Apache HTTP + HTTPS)
<VirtualHost *:{{.HTTPPort}}>
    ServerName {{.Domain}}
    ServerAlias {{.ServerAliasLine}}
    DocumentRoot {{.DocRoot}}
    RewriteEngine On
    RewriteRule ^/admin/assets/(.*)$ /assets/$1 [L]

    <Directory {{.DocRoot}}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <LocationMatch "(^|/)\.(?!well-known(?:/|$))">
        Require all denied
    </LocationMatch>

    SetEnvIfNoCase Authorization "(.+)" HTTP_AUTHORIZATION=$1

    <FilesMatch "\.php$">
        SetHandler "proxy:unix:{{.PHPSocket}}|fcgi://localhost"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/panelze-{{.Domain}}-error.log
    CustomLog ${APACHE_LOG_DIR}/panelze-{{.Domain}}-access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName {{.Domain}}
    ServerAlias {{.ServerAliasLine}}
    DocumentRoot {{.DocRoot}}
    RewriteEngine On
    RewriteRule ^/admin/assets/(.*)$ /assets/$1 [L]

    SSLEngine on
    SSLCertificateFile {{.SSLFullChain}}
    SSLCertificateKeyFile {{.SSLPrivKey}}

    Header always set Strict-Transport-Security "max-age=31536000"

    <Directory {{.DocRoot}}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <LocationMatch "(^|/)\.(?!well-known(?:/|$))">
        Require all denied
    </LocationMatch>

    SetEnvIfNoCase Authorization "(.+)" HTTP_AUTHORIZATION=$1

    <FilesMatch "\.php$">
        SetHandler "proxy:unix:{{.PHPSocket}}|fcgi://localhost"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/panelze-{{.Domain}}-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/panelze-{{.Domain}}-ssl-access.log combined
</VirtualHost>
`

type vhostVars struct {
	HTTPPort       int
	Domain         string
	ServerAliasLine string
	DocRoot        string
	PHPSocket      string
	SSLFullChain   string
	SSLPrivKey     string
	ForceHTTPS     bool
}

func buildApacheServerAliasLine(primary string, aliases []string) string {
	primary = strings.ToLower(strings.TrimSpace(primary))
	if primary == "" {
		return ""
	}
	seen := map[string]struct{}{}
	var parts []string
	add := func(s string) {
		s = strings.ToLower(strings.TrimSpace(s))
		if s == "" {
			return
		}
		if _, ok := seen[s]; ok {
			return
		}
		seen[s] = struct{}{}
		parts = append(parts, s)
	}
	add("www." + primary)
	for _, a := range aliases {
		al := strings.ToLower(strings.TrimSpace(a))
		if al == "" || al == primary {
			continue
		}
		if !nginx.DomainSafe(al) {
			continue
		}
		add(al)
		if !strings.HasPrefix(al, "www.") {
			add("www." + al)
		}
	}
	return strings.Join(parts, " ")
}

func confBaseName(cfg *config.Config, domain string) string {
	return nginx.ConfBaseName(nginx.VhostConfPrefix(cfg), domain)
}

func sitesAvailable(cfg *config.Config) string {
	s := strings.TrimSpace(cfg.Hosting.ApacheSitesAvailable)
	if s == "" {
		return "/etc/apache2/sites-available"
	}
	return s
}

func sitesEnabled(cfg *config.Config) string {
	s := strings.TrimSpace(cfg.Hosting.ApacheSitesEnabled)
	if s == "" {
		return "/etc/apache2/sites-enabled"
	}
	return s
}

func stagingDir(cfg *config.Config) string {
	if cfg != nil && strings.TrimSpace(cfg.Paths.VhostsDir) != "" {
		return filepath.Join(filepath.Dir(cfg.Paths.VhostsDir), "apache-vhosts")
	}
	if cfg != nil && strings.TrimSpace(cfg.Paths.WebRoot) != "" {
		return filepath.Join(filepath.Dir(cfg.Paths.WebRoot), "apache-vhosts")
	}
	return "/var/www/panelze/data/apache-vhosts"
}

const defaultApacheVhostHelper = "/usr/local/sbin/panelze-apache-vhost"

func apacheVhostHelperPath(cfg *config.Config) string {
	if cfg == nil {
		return defaultApacheVhostHelper
	}
	s := strings.TrimSpace(cfg.Hosting.ApacheVhostHelper)
	if s != "" {
		return s
	}
	return defaultApacheVhostHelper
}

func runApacheVhostHelper(cfg *config.Config, action, arg string) error {
	helper := apacheVhostHelperPath(cfg)
	if _, err := os.Stat(helper); err != nil {
		return fmt.Errorf("apache vhost helper yok (%s): sunucuda root ile bash deploy/scripts/ensure-webserver-stack.sh çalıştırın", helper)
	}
	out, err := exec.Command("sudo", "-n", helper, action, arg).CombinedOutput()
	if err != nil {
		msg := strings.TrimSpace(string(out))
		if strings.Contains(msg, "not allowed") || strings.Contains(msg, "password") {
			return fmt.Errorf("apache vhost sudo reddedildi — ensure-engine-sudoers.sh ve ensure-webserver-stack.sh çalıştırın: %s", msg)
		}
		return fmt.Errorf("%s %s: %w — %s", helper, action, err, msg)
	}
	return nil
}

// ApplyBackendVhost nginx edge proxy arkasında yalnızca HTTP backend (apache_http_port, varsayılan 8080).
func ApplyBackendVhost(cfg *config.Config, domain, docRoot, phpSocket string, aliases []string) error {
	return applyVhostInner(cfg, domain, docRoot, phpSocket, "", "", aliases, false, true)
}

// ApplyVhost Debian/Ubuntu sites-available + sites-enabled sembolik bağ.
func ApplyVhost(cfg *config.Config, domain, docRoot, phpSocket, sslFullchain, sslPrivkey string, aliases []string, forceHTTPS bool) error {
	return applyVhostInner(cfg, domain, docRoot, phpSocket, sslFullchain, sslPrivkey, aliases, forceHTTPS, false)
}

func applyVhostInner(cfg *config.Config, domain, docRoot, phpSocket, sslFullchain, sslPrivkey string, aliases []string, forceHTTPS, backendOnly bool) error {
	if !cfg.Hosting.ApacheManageVhosts {
		return nil
	}
	if !nginx.DomainSafe(domain) {
		return fmt.Errorf("invalid domain for apache vhost")
	}
	if strings.Contains(docRoot, "..") {
		return fmt.Errorf("invalid document root")
	}
	docRoot = filepath.Clean(docRoot)

	sock := strings.TrimSpace(phpSocket)
	if sock == "" {
		sock = "/run/php/php8.2-fpm.sock"
	}

	chain := strings.TrimSpace(sslFullchain)
	key := strings.TrimSpace(sslPrivkey)
	useSSL := !backendOnly && chain != "" && key != ""

	tplStr := tplHTTP
	if useSSL {
		if forceHTTPS {
			tplStr = tplHTTPS
		} else {
			tplStr = tplHTTPSDual
		}
	}
	tpl, err := template.New("apache").Parse(tplStr)
	if err != nil {
		return err
	}
	httpPort := cfg.Hosting.ApacheHTTPPort
	if httpPort <= 0 {
		httpPort = 80
	}
	sal := buildApacheServerAliasLine(domain, aliases)
	if sal == "" {
		return fmt.Errorf("invalid server aliases")
	}
	vars := vhostVars{
		HTTPPort:        httpPort,
		Domain:          domain,
		ServerAliasLine: sal,
		DocRoot:         docRoot,
		PHPSocket:       sock,
		SSLFullChain:    chain,
		SSLPrivKey:      key,
		ForceHTTPS:      forceHTTPS,
	}
	var buf bytes.Buffer
	if err := tpl.Execute(&buf, vars); err != nil {
		return err
	}

	base := confBaseName(cfg, domain)
	staging := stagingDir(cfg)
	if err := os.MkdirAll(staging, 0o755); err != nil {
		return fmt.Errorf("apache staging dir: %w", err)
	}
	avail := filepath.Join(staging, base)

	oldAvail, readAvailErr := os.ReadFile(avail)
	hadAvail := readAvailErr == nil

	if err := os.WriteFile(avail, buf.Bytes(), 0o644); err != nil {
		return fmt.Errorf("write apache vhost: %w", err)
	}

	if err := runApacheVhostHelper(cfg, "enable", avail); err != nil {
		if hadAvail {
			_ = os.WriteFile(avail, oldAvail, 0o644)
		} else {
			_ = os.Remove(avail)
		}
		return err
	}
	return nil
}

// RemoveVhost conf ve etkin bağlantıyı kaldırır.
func RemoveVhost(cfg *config.Config, domain string) error {
	if !cfg.Hosting.ApacheManageVhosts {
		return nil
	}
	if domain == "" || strings.Contains(domain, "..") {
		return fmt.Errorf("invalid domain")
	}
	base := confBaseName(cfg, domain)
	_ = runApacheVhostHelper(cfg, "disable", base)
	_ = os.Remove(filepath.Join(stagingDir(cfg), base))
	return nil
}

// RemoveVhostBestEffort site silme yolu: panelze ve eski panelsar-* apache vhost dosyalarını kaldırmayı dener.
func RemoveVhostBestEffort(cfg *config.Config, domain string) {
	if domain == "" || strings.Contains(domain, "..") {
		return
	}
	base := confBaseName(cfg, domain)
	_ = runApacheVhostHelper(cfg, "disable", base)
	_ = os.Remove(filepath.Join(stagingDir(cfg), base))
	leg := "panelsar-" + strings.ToLower(domain) + ".conf"
	_ = runApacheVhostHelper(cfg, "disable", leg)
	_ = os.Remove(filepath.Join(stagingDir(cfg), leg))
}

func apacheTestConfig() error {
	var out []byte
	var err error
	if _, e := exec.LookPath("apache2ctl"); e == nil {
		out, err = exec.Command("apache2ctl", "configtest").CombinedOutput()
	} else {
		out, err = exec.Command("apachectl", "configtest").CombinedOutput()
	}
	if err != nil {
		return fmt.Errorf("apache configtest: %w — %s", err, strings.TrimSpace(string(out)))
	}
	return nil
}

func reloadApacheErr() error {
	if _, err := exec.LookPath("apache2ctl"); err == nil {
		out, err2 := exec.Command("apache2ctl", "graceful").CombinedOutput()
		if err2 != nil {
			return fmt.Errorf("apache2ctl graceful: %w — %s", err2, strings.TrimSpace(string(out)))
		}
		return nil
	}
	out, err := exec.Command("apachectl", "graceful").CombinedOutput()
	if err != nil {
		return fmt.Errorf("apachectl graceful: %w — %s", err, strings.TrimSpace(string(out)))
	}
	return nil
}

// VhostFilePath staging/apache-vhosts altındaki vhost dosyasının mutlak yolu (mevcut veya canonical).
func VhostFilePath(cfg *config.Config, domain string) (string, error) {
	p, _, err := nginx.ResolveVhostPath(cfg, stagingDir(cfg), domain)
	return p, err
}

// VhostCanRevert son başarılı kayıttan önceki içerik dosyası var mı.
func VhostCanRevert(cfg *config.Config, domain string) (bool, error) {
	p, exists, err := nginx.ResolveVhostPath(cfg, stagingDir(cfg), domain)
	if err != nil {
		return false, err
	}
	if !exists {
		return false, nil
	}
	_, ok := nginx.FindVhostPrevPath(p)
	return ok, nil
}

// ReadVhostFile mevcut Apache vhost dosyasını okur.
func ReadVhostFile(cfg *config.Config, domain string) ([]byte, error) {
	if !cfg.Hosting.ApacheManageVhosts {
		return nil, fmt.Errorf("apache vhost management is disabled")
	}
	p, exists, err := nginx.ResolveVhostPath(cfg, stagingDir(cfg), domain)
	if err != nil {
		return nil, err
	}
	if !exists {
		return nil, os.ErrNotExist
	}
	return os.ReadFile(p)
}

// WriteVhostRaw Apache vhost içeriğini yazar, configtest ve istenirse graceful reload uygular.
func WriteVhostRaw(cfg *config.Config, domain string, content []byte) error {
	if !cfg.Hosting.ApacheManageVhosts {
		return fmt.Errorf("apache vhost management is disabled")
	}
	if len(content) > maxRawApacheVhostBytes {
		return fmt.Errorf("vhost content too large (max %d bytes)", maxRawApacheVhostBytes)
	}
	if bytes.IndexByte(content, 0) >= 0 {
		return fmt.Errorf("invalid vhost content")
	}
	p, err := VhostFilePath(cfg, domain)
	if err != nil {
		return err
	}
	if err := os.MkdirAll(filepath.Dir(p), 0o755); err != nil {
		return fmt.Errorf("apache staging dir: %w", err)
	}

	oldAvail, readAvailErr := os.ReadFile(p)
	hadAvail := readAvailErr == nil

	if err := os.WriteFile(p, content, 0o644); err != nil {
		return fmt.Errorf("write apache vhost: %w", err)
	}
	if err := runApacheVhostHelper(cfg, "enable", p); err != nil {
		if hadAvail {
			_ = os.WriteFile(p, oldAvail, 0o644)
		} else {
			_ = os.Remove(p)
		}
		return err
	}
	prev := apachePrevPath(p)
	if hadAvail && len(oldAvail) > 0 {
		_ = os.WriteFile(prev, oldAvail, 0o600)
	} else {
		_ = os.Remove(prev)
	}
	return nil
}

// RevertVhostRaw son başarılı kayıttan önceki içeriği geri yükler.
func RevertVhostRaw(cfg *config.Config, domain string) error {
	if !cfg.Hosting.ApacheManageVhosts {
		return fmt.Errorf("apache vhost management is disabled")
	}
	p, err := VhostFilePath(cfg, domain)
	if err != nil {
		return err
	}
	prev := apachePrevPath(p)
	prevBody, err := os.ReadFile(prev)
	if err != nil || len(bytes.TrimSpace(prevBody)) == 0 {
		return fmt.Errorf("no saved previous version to restore")
	}
	var curContent []byte
	hadCur := false
	if b, rerr := os.ReadFile(p); rerr == nil {
		curContent = b
		hadCur = true
	}

	if err := os.WriteFile(p, prevBody, 0o644); err != nil {
		return fmt.Errorf("write apache vhost: %w", err)
	}
	if err := runApacheVhostHelper(cfg, "enable", p); err != nil {
		if hadCur {
			_ = os.WriteFile(p, curContent, 0o644)
			_ = runApacheVhostHelper(cfg, "enable", p)
		}
		return err
	}
	if hadCur && len(curContent) > 0 {
		_ = os.WriteFile(prev, curContent, 0o600)
	} else {
		_ = os.Remove(prev)
	}
	return nil
}
