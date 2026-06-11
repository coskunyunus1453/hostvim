// Package openlitespeed — OpenLiteSpeed sanal host parçaları (vhconf + listener map + include indeksi).
// Kurulum: ana httpd_config.conf içinde (bir kez) şunları ekleyin:
//   include conf/conf.d/panelze-ols-vhosts.conf
// HTTP listener bloğu içinde:  include conf/conf.d/panelze-ols-http-maps.conf
// HTTPS listener bloğu içinde: include conf/conf.d/panelze-ols-https-maps.conf
package openlitespeed

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

const (
	olsVhostsIndex   = "panelze-ols-vhosts.conf"
	olsHTTPMapsFile  = "panelze-ols-http-maps.conf"
	olsHTTPSMapsFile = "panelze-ols-https-maps.conf"
)

func confRoot(cfg *config.Config) string {
	s := strings.TrimSpace(cfg.Hosting.OLSConfRoot)
	if s == "" {
		return "/usr/local/lsws"
	}
	return s
}

func ctrlBin(cfg *config.Config) string {
	s := strings.TrimSpace(cfg.Hosting.OLSCtrlPath)
	if s != "" {
		return s
	}
	p := filepath.Join(confRoot(cfg), "bin", "lswsctrl")
	if _, err := os.Stat(p); err == nil {
		return p
	}
	if path, err := exec.LookPath("lswsctrl"); err == nil {
		return path
	}
	return p
}

// VhostID sanal host adı (map + virtualhost bloğu); noktasız güvenli kimlik.
func VhostID(domain string) string {
	d := strings.ToLower(strings.TrimSpace(domain))
	d = strings.ReplaceAll(d, ".", "-")
	return "panelze-" + d
}

func vhConfDir(cfg *config.Config, domain string) string {
	return filepath.Join(confRoot(cfg), "conf", "vhosts", "panelze-"+strings.ToLower(strings.TrimSpace(domain)))
}

func vhConfPath(cfg *config.Config, domain string) string {
	return filepath.Join(vhConfDir(cfg, domain), "vhconf.conf")
}

func fragmentPath(cfg *config.Config, vhid string) string {
	return filepath.Join(confRoot(cfg), "conf", "conf.d", vhid+".conf")
}

func vhostsIndexPath(cfg *config.Config) string {
	return filepath.Join(confRoot(cfg), "conf", "conf.d", olsVhostsIndex)
}

func httpMapsPath(cfg *config.Config) string {
	return filepath.Join(confRoot(cfg), "conf", "conf.d", olsHTTPMapsFile)
}

func httpsMapsPath(cfg *config.Config) string {
	return filepath.Join(confRoot(cfg), "conf", "conf.d", olsHTTPSMapsFile)
}

func olsStagingDir(cfg *config.Config, domain string) string {
	base := filepath.Dir(cfg.Paths.WebRoot)
	if base == "" || base == "." {
		base = "/var/www/panelze/data"
	}
	return filepath.Join(base, "ols-staging", strings.ToLower(strings.TrimSpace(domain)))
}

const defaultOLSVhostHelper = "/usr/local/sbin/panelze-ols-vhost"

func olsVhostHelperPath(cfg *config.Config) string {
	if cfg == nil {
		return defaultOLSVhostHelper
	}
	s := strings.TrimSpace(cfg.Hosting.OLSVhostHelper)
	if s != "" {
		return s
	}
	return defaultOLSVhostHelper
}

func runOLSVhostHelper(cfg *config.Config, action, arg string) error {
	helper := olsVhostHelperPath(cfg)
	if _, err := os.Stat(helper); err != nil {
		return fmt.Errorf("ols vhost helper yok (%s): sunucuda root ile bash deploy/scripts/ensure-webserver-stack.sh çalıştırın", helper)
	}
	out, err := exec.Command("sudo", "-n", helper, action, arg).CombinedOutput()
	if err != nil {
		msg := strings.TrimSpace(string(out))
		if strings.Contains(msg, "not allowed") || strings.Contains(msg, "password") {
			return fmt.Errorf("ols vhost sudo reddedildi — ensure-engine-sudoers.sh ve ensure-webserver-stack.sh çalıştırın: %s", msg)
		}
		return fmt.Errorf("%s %s: %w — %s", helper, action, err, msg)
	}
	return nil
}

func olsUDS(sock string) string {
	s := strings.TrimSpace(sock)
	s = strings.TrimPrefix(s, "unix:")
	s = strings.TrimPrefix(s, "unix://")
	s = strings.TrimSpace(s)
	s = strings.TrimPrefix(s, "/")
	return "uds://" + s
}

func buildAliasLine(primary string, aliases []string) string {
	primary = strings.ToLower(strings.TrimSpace(primary))
	if primary == "" {
		return ""
	}
	seen := map[string]struct{}{}
	var parts []string
	add := func(s string) {
		s = strings.ToLower(strings.TrimSpace(s))
		if s == "" || s == primary {
			return
		}
		if _, ok := seen[s]; ok {
			return
		}
		if !nginx.DomainSafe(s) {
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
	return strings.Join(parts, ", ")
}

func mapDomains(primary string, aliases []string) []string {
	line := strings.TrimSpace(nginx.BuildServerNamesLine(primary, aliases))
	if line == "" {
		return nil
	}
	return strings.Fields(line)
}

func readLines(path string) ([]string, error) {
	b, err := os.ReadFile(path)
	if err != nil {
		if os.IsNotExist(err) {
			return nil, nil
		}
		return nil, err
	}
	var out []string
	for _, ln := range strings.Split(string(b), "\n") {
		out = append(out, ln)
	}
	if len(out) > 0 && out[len(out)-1] == "" {
		out = out[:len(out)-1]
	}
	return out, nil
}

func writeLines(path string, lines []string) error {
	if err := os.MkdirAll(filepath.Dir(path), 0o755); err != nil {
		return err
	}
	body := strings.Join(lines, "\n")
	if body != "" {
		body += "\n"
	}
	return os.WriteFile(path, []byte(body), 0o644)
}

func upsertMapFile(path, vhid string, domains []string) error {
	lines, err := readLines(path)
	if err != nil {
		return err
	}
	if len(lines) == 0 {
		lines = []string{
			"# Panelze — OpenLiteSpeed HTTP(S) listener map parçası.",
			"# Bu dosyayı ilgili listener bloğunun içine include edin (httpd_config.conf).",
		}
	}
	var kept []string
	prefix := "map " + vhid + " "
	for _, ln := range lines {
		t := strings.TrimSpace(ln)
		if t == "" || strings.HasPrefix(t, "#") {
			kept = append(kept, ln)
			continue
		}
		if strings.HasPrefix(t, prefix) || (strings.HasPrefix(t, "map ") && len(strings.Fields(t)) > 1 && strings.Fields(t)[1] == vhid) {
			continue
		}
		kept = append(kept, ln)
	}
	if len(domains) > 0 {
		kept = append(kept, prefix+strings.Join(domains, " "))
	}
	return writeLines(path, kept)
}

func removeMapLines(path, vhid string) error {
	lines, err := readLines(path)
	if err != nil || len(lines) == 0 {
		return err
	}
	var kept []string
	prefix := "map " + vhid + " "
	for _, ln := range lines {
		t := strings.TrimSpace(ln)
		if strings.HasPrefix(t, prefix) || (strings.HasPrefix(t, "map ") && len(strings.Fields(t)) > 1 && strings.Fields(t)[1] == vhid) {
			continue
		}
		kept = append(kept, ln)
	}
	return writeLines(path, kept)
}

func upsertIncludeIndex(cfg *config.Config, vhid string) error {
	idx := vhostsIndexPath(cfg)
	rel := "conf/conf.d/" + vhid + ".conf"
	line := "include " + rel
	lines, err := readLines(idx)
	if err != nil {
		return err
	}
	if len(lines) == 0 {
		lines = []string{
			"# Panelze — sanal host parçaları. Ana httpd_config sonuna bir kez ekleyin:",
			"# include conf/conf.d/" + olsVhostsIndex,
		}
	}
	for _, ln := range lines {
		if strings.TrimSpace(ln) == line {
			return nil
		}
	}
	lines = append(lines, line)
	return writeLines(idx, lines)
}

func removeIncludeIndex(cfg *config.Config, vhid string) error {
	idx := vhostsIndexPath(cfg)
	rel := "conf/conf.d/" + vhid + ".conf"
	line := "include " + rel
	lines, err := readLines(idx)
	if err != nil || len(lines) == 0 {
		return err
	}
	var kept []string
	for _, ln := range lines {
		if strings.TrimSpace(ln) == line {
			continue
		}
		kept = append(kept, ln)
	}
	return writeLines(idx, kept)
}

const vhconfTpl = `# Panelze — {{.Primary}}
docRoot                   {{.DocRoot}}
vhDomain                  {{.Primary}}
vhAliases                 {{.AliasLine}}

index  {
  useServer               0
  indexFiles              index.php, index.html
  autoIndex               0
}

extprocessor panelze-fpm {
  type                    fcgi
  address                 {{.UDS}}
  maxConns                10
  initTimeout             60
  retryTimeout            0
  respBuffer              0
  autoStart               0
}

scripthandler  {
  add                     panelze-fpm php
}

context /.well-known/acme-challenge {
  location                {{.DocRoot}}/.well-known/acme-challenge/
  allowBrowse             1
  addDefaultCharset       off
}

context / {
  type                    directory
  location                {{.DocRoot}}/
  allowBrowse             1
  addDefaultCharset       off
  indexFiles              index.php, index.html
  rewrite  {
    enable                  1
    autoLoadHtaccess        1
  }
}
{{if .RewriteHTTPS}}
rewrite  {
  enable                  1
  logLevel                0
  rules                   <<<END_HVIM_HTTPS_REDIRECT
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
END_HVIM_HTTPS_REDIRECT
}
{{end}}
{{if .SSLBlock}}
vhssl  {
  keyFile                 {{.SSLKey}}
  certFile                {{.SSLCert}}
  certChain               1
}
{{end}}

errorlog {{.ErrLog}} {
  logLevel                WARN
  rollingSize             10M
  useServer               0
}

accesslog {{.AccLog}} {
  rollingSize             10M
  compressArchive         1
  useServer               0
}
`

type vhTplData struct {
	Primary       string
	DocRoot       string
	AliasLine     string
	UDS           string
	RewriteHTTPS  bool
	SSLBlock      bool
	SSLKey        string
	SSLCert       string
	ErrLog        string
	AccLog        string
}

const fragTpl = `virtualhost {{.VhostID}} {
  vhRoot                  {{.VHRoot}}
  configFile              {{.ConfigRel}}
  allowSymbolLink         1
  enableScript            1
  restrained              0
}
`

// ApplyBackendVhost nginx edge proxy arkasında yalnızca HTTP backend (openlitespeed_http_port, varsayılan 8088).
func ApplyBackendVhost(cfg *config.Config, domain, docRoot, phpSocket string, aliases []string) error {
	return applyVhostInner(cfg, domain, docRoot, phpSocket, "", "", aliases, false, true)
}

// ApplyVhost vhconf + virtualhost parçası + map + include indeksini yazar; istenirse lswsctrl reload.
func ApplyVhost(cfg *config.Config, domain, docRoot, phpSocket, sslFullchain, sslPrivkey string, aliases []string, forceHTTPS bool) error {
	return applyVhostInner(cfg, domain, docRoot, phpSocket, sslFullchain, sslPrivkey, aliases, forceHTTPS, false)
}

func applyVhostInner(cfg *config.Config, domain, docRoot, phpSocket, sslFullchain, sslPrivkey string, aliases []string, forceHTTPS, backendOnly bool) error {
	if !cfg.Hosting.OLSManageVhosts {
		return nil
	}
	domain = strings.ToLower(strings.TrimSpace(domain))
	if !nginx.DomainSafe(domain) {
		return fmt.Errorf("invalid domain for openlitespeed vhost")
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

	vhid := VhostID(domain)
	vhRoot := filepath.Join(cfg.Paths.WebRoot, domain, ".panelze", "ols")
	if err := os.MkdirAll(filepath.Join(vhRoot, "logs"), 0o755); err != nil {
		return fmt.Errorf("ols vh root: %w", err)
	}

	aliasLine := buildAliasLine(domain, aliases)
	if aliasLine == "" {
		return fmt.Errorf("invalid openlitespeed aliases")
	}

	tpl, err := template.New("ols").Parse(vhconfTpl)
	if err != nil {
		return err
	}
	data := vhTplData{
		Primary:      domain,
		DocRoot:      docRoot,
		AliasLine:    aliasLine,
		UDS:          olsUDS(sock),
		RewriteHTTPS: useSSL && forceHTTPS,
		SSLBlock:     useSSL,
		SSLKey:       key,
		SSLCert:      chain,
		ErrLog:       filepath.Join(vhRoot, "logs", "error.log"),
		AccLog:       filepath.Join(vhRoot, "logs", "access.log"),
	}
	var vhbuf bytes.Buffer
	if err := tpl.Execute(&vhbuf, data); err != nil {
		return err
	}

	staging := olsStagingDir(cfg, domain)
	if err := os.MkdirAll(staging, 0o755); err != nil {
		return fmt.Errorf("ols staging dir: %w", err)
	}

	if err := os.WriteFile(filepath.Join(staging, "vhconf.conf"), vhbuf.Bytes(), 0o644); err != nil {
		return fmt.Errorf("write ols vhconf: %w", err)
	}

	configRel := "conf/vhosts/panelze-" + domain + "/vhconf.conf"
	ft, err := template.New("frag").Parse(fragTpl)
	if err != nil {
		return err
	}
	var fbuf bytes.Buffer
	if err := ft.Execute(&fbuf, map[string]string{
		"VhostID":   vhid,
		"VHRoot":    vhRoot,
		"ConfigRel": configRel,
	}); err != nil {
		return err
	}
	if err := os.WriteFile(filepath.Join(staging, "fragment.conf"), fbuf.Bytes(), 0o644); err != nil {
		return fmt.Errorf("write ols virtualhost fragment: %w", err)
	}

	doms := mapDomains(domain, aliases)
	if len(doms) == 0 {
		return fmt.Errorf("invalid map domains")
	}
	httpMapLine := "map " + vhid + " " + strings.Join(doms, " ")
	if err := os.WriteFile(filepath.Join(staging, "http-map.txt"), []byte(httpMapLine+"\n"), 0o644); err != nil {
		return fmt.Errorf("write ols http map: %w", err)
	}
	if useSSL {
		if err := os.WriteFile(filepath.Join(staging, "https-map.txt"), []byte(httpMapLine+"\n"), 0o644); err != nil {
			return fmt.Errorf("write ols https map: %w", err)
		}
	} else {
		_ = os.Remove(filepath.Join(staging, "https-map.txt"))
	}

	if err := runOLSVhostHelper(cfg, "apply", staging); err != nil {
		return err
	}
	return nil
}

func olsConfigTest(cfg *config.Config) error {
	// OpenLiteSpeed lswsctrl'de configtest yok; yapılandırma reload/restart ile uygulanır.
	_ = cfg
	return nil
}

func olsReload(cfg *config.Config) error {
	bin := ctrlBin(cfg)
	if _, err := os.Stat(bin); err != nil {
		if err := exec.Command("systemctl", "reload", "lshttpd").Run(); err == nil {
			return nil
		}
		_ = exec.Command("systemctl", "reload", "openlitespeed").Run()
		return nil
	}
	out, err := exec.Command(bin, "reload").CombinedOutput()
	if err != nil {
		out2, err2 := exec.Command(bin, "restart").CombinedOutput()
		if err2 != nil {
			return fmt.Errorf("openlitespeed reload: %w — %s; restart: %w — %s", err, strings.TrimSpace(string(out)), err2, strings.TrimSpace(string(out2)))
		}
	}
	return nil
}

// RemoveVhost yönetim açıksa dosyaları ve map girdilerini kaldırır.
func RemoveVhost(cfg *config.Config, domain string) error {
	if !cfg.Hosting.OLSManageVhosts {
		return nil
	}
	removeVhostFiles(cfg, domain)
	return nil
}

func removeVhostFiles(cfg *config.Config, domain string) {
	domain = strings.ToLower(strings.TrimSpace(domain))
	if domain == "" || strings.Contains(domain, "..") {
		return
	}
	_ = runOLSVhostHelper(cfg, "remove", domain)
	_ = os.RemoveAll(olsStagingDir(cfg, domain))
}

// RemoveVhostBestEffort site silme / tip değişimi; bayrak kapalı olsa da temizler.
func RemoveVhostBestEffort(cfg *config.Config, domain string) {
	removeVhostFiles(cfg, domain)
}
