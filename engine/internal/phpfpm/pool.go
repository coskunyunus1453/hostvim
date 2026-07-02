package phpfpm

import (
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"regexp"
	"strings"

	"panelze/engine/internal/fsutil"
)

// NormalizeVersion "8.2" gibi sade sürüm metni döner.
func NormalizeVersion(v string) string {
	s := strings.TrimSpace(v)
	if s == "" {
		return "8.2"
	}
	return s
}

func poolSlug(domain string) string {
	return strings.ReplaceAll(strings.ToLower(strings.TrimSpace(domain)), ".", "-")
}

// PoolName php-fpm [pool] bölümü adı.
func PoolName(domain string) string {
	return "panelze-" + poolSlug(domain)
}

// SocketPath bu domain için unix soket yolu (nginx ile aynı olmalı).
func SocketPath(listenDir, domain string) string {
	name := PoolName(domain) + ".sock"
	if listenDir == "" {
		listenDir = "/run/php"
	}
	return filepath.Join(listenDir, name)
}

func poolConfPath(h HostingPoolSettings, phpVersion, domain string) string {
	dir := h.poolDirForVersion(phpVersion)
	return filepath.Join(dir, PoolName(domain)+".conf")
}

type HostingPoolSettings struct {
	PoolDirTemplate string
	SocketListenDir string
	FPMUser         string
	FPMGroup        string
	ListenOwner     string
	ListenGroup     string
	// Helper boş değilse pool yazma/test/reload root betiğine (sudo) devredilir.
	// PanelKafes (cage) modunda engine www-data olduğu için pool.d'ye doğrudan
	// yazamaz; helper root olarak yazar, `php-fpm -t` ve reload eder, soketi doğrular.
	Helper string
}

// PoolOptions site başına PanelKafes / kaynak limiti.
type PoolOptions struct {
	SiteUser     string
	SiteGroup    string
	MaxChildren  int
	MemoryLimit  string
	BaseDir      string // open_basedir kökü (site kökü). Boşsa docRoot kullanılır.
	ExtraBasedir string // örn. site/tmp — session/upload/tmp buraya yönlenir
}

func (h HostingPoolSettings) poolUser(opts PoolOptions) string {
	if strings.TrimSpace(opts.SiteUser) != "" {
		return strings.TrimSpace(opts.SiteUser)
	}
	return h.poolUserDefault()
}

func (h HostingPoolSettings) poolGroup(opts PoolOptions) string {
	if strings.TrimSpace(opts.SiteGroup) != "" {
		return strings.TrimSpace(opts.SiteGroup)
	}
	if strings.TrimSpace(opts.SiteUser) != "" && strings.TrimSpace(h.FPMGroup) != "" {
		return h.FPMGroup
	}
	return h.poolGroupDefault()
}

func (h HostingPoolSettings) poolUserDefault() string {
	if strings.TrimSpace(h.FPMUser) == "" {
		return "www-data"
	}
	return h.FPMUser
}

func (h HostingPoolSettings) poolGroupDefault() string {
	if strings.TrimSpace(h.FPMGroup) == "" {
		return "www-data"
	}
	return h.FPMGroup
}

func (h HostingPoolSettings) listenOwner() string {
	if strings.TrimSpace(h.ListenOwner) != "" {
		return h.ListenOwner
	}
	return "www-data"
}

func (h HostingPoolSettings) listenGroup() string {
	if strings.TrimSpace(h.ListenGroup) != "" {
		return h.ListenGroup
	}
	return "www-data"
}

func (h HostingPoolSettings) poolDirForVersion(phpVersion string) string {
	v := NormalizeVersion(phpVersion)
	tpl := strings.TrimSpace(h.PoolDirTemplate)
	if tpl == "" {
		return filepath.Join("/etc/php", v, "fpm", "pool.d")
	}
	return strings.NewReplacer("{{version}}", v, "{{Version}}", v).Replace(tpl)
}

func (h HostingPoolSettings) listenDir() string {
	if strings.TrimSpace(h.SocketListenDir) == "" {
		return "/run/php"
	}
	return h.SocketListenDir
}

// SocketForDomain bu ayarlarla yazılmış pool’un unix soket yolu (nginx/apache ile aynı olmalı).
func (h HostingPoolSettings) SocketForDomain(domain string) string {
	return SocketPath(h.listenDir(), domain)
}

const poolTemplate = `; Panelze PanelKafes — %s — PHP %s
[%s]
user = %s
group = %s
listen = %s
listen.owner = %s
listen.group = %s
listen.mode = 0660
; Performans: kalıcı sıcak worker'lar — soğuk başlatma gecikmesini önler (her zaman hızlı ilk yanıt)
pm = dynamic
pm.max_children = %d
pm.start_servers = %d
pm.min_spare_servers = %d
pm.max_spare_servers = %d
pm.max_requests = 500
pm.process_idle_timeout = 60s
; Güvenlik/izolasyon: asılı kalan (sonsuz döngü, dış I/O beklemesi) istekleri öldür —
; tek bir sitenin worker'ları ve CPU'yu süresiz tüketip komşu siteleri etkilemesini önler.
request_terminate_timeout = 300s
chdir = %s
php_admin_value[open_basedir] = %s
php_admin_value[memory_limit] = %s
php_admin_value[session.save_path] = %s
php_admin_value[upload_tmp_dir] = %s
php_value[sys_temp_dir] = %s
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,pcntl_fork
; Performans: OPcache (PHP uygulamaları için interned/dosya/revalidate ayarı)
php_admin_value[opcache.interned_strings_buffer] = 16
php_admin_value[opcache.max_accelerated_files] = 20000
php_admin_value[opcache.revalidate_freq] = 60
`

// ReadPoolSnapshot mevcut pool dosyası varsa içeriğini döner (geri alma / sürüm değişimi yedeği).
func ReadPoolSnapshot(h HostingPoolSettings, domain, phpVersion string) (data []byte, ok bool) {
	domain = strings.TrimSpace(domain)
	if domain == "" || strings.Contains(domain, "..") {
		return nil, false
	}
	p := poolConfPath(h, phpVersion, domain)
	b, err := os.ReadFile(p)
	if err != nil {
		return nil, false
	}
	return b, true
}

func fpmTestBinary(phpVersion string) string {
	v := NormalizeVersion(phpVersion)
	name := "php-fpm" + v
	if p, err := exec.LookPath(name); err == nil {
		return p
	}
	alt := filepath.Join("/usr/sbin", name)
	if st, err := os.Stat(alt); err == nil && !st.IsDir() {
		return alt
	}
	return ""
}

// TestFPMConfig php-fpm sürüm ikilisini bulursa `php-fpmX.Y -t` çalıştırır. İkili yoksa hata dönmez (ör. geliştirme ortamı).
func TestFPMConfig(phpVersion string) error {
	bin := fpmTestBinary(phpVersion)
	if bin == "" {
		return nil
	}
	out, err := exec.Command(bin, "-t").CombinedOutput()
	if err != nil {
		return fmt.Errorf("php-fpm -t: %w — %s", err, strings.TrimSpace(string(out)))
	}
	return nil
}

// RestorePoolConf başarısız reload veya vhost sonrası pool dosyasını önceki duruma getirir; hadPrevious false ise dosyayı siler.
func RestorePoolConf(h HostingPoolSettings, domain, phpVersion string, previous []byte, hadPrevious bool) error {
	domain = strings.TrimSpace(domain)
	if domain == "" || strings.Contains(domain, "..") {
		return fmt.Errorf("invalid domain")
	}
	p := poolConfPath(h, phpVersion, domain)
	if hadPrevious {
		return fsutil.AtomicWrite(p, previous, 0o644)
	}
	return os.Remove(p)
}

// RenderPool pool dosyası içeriğini ve hedef soketi üretir (dosya yazmaz).
// Helper (root) modunda içerik bu fonksiyonla üretilip betiğe stdin ile verilir.
func RenderPool(h HostingPoolSettings, domain, phpVersion, docRoot string, opts ...PoolOptions) (body, socket string, err error) {
	var o PoolOptions
	if len(opts) > 0 {
		o = opts[0]
	}
	domain = strings.TrimSpace(domain)
	if domain == "" || strings.Contains(domain, "..") {
		return "", "", fmt.Errorf("invalid domain")
	}
	docRoot = filepath.Clean(docRoot)
	if strings.Contains(docRoot, "..") {
		return "", "", fmt.Errorf("invalid document root")
	}
	socket = SocketPath(h.listenDir(), domain)
	maxCh := o.MaxChildren
	if maxCh <= 0 {
		maxCh = 30
	}
	// pm=dynamic için sıcak worker hesabı: site başına en az 1 idle worker hep
	// hazır kalır (soğuk başlatma yok), yük altında max_spare'e kadar açılır.
	startServers := 2
	if startServers > maxCh {
		startServers = maxCh
	}
	minSpare := 1
	maxSpare := maxCh / 2
	if maxSpare < 2 {
		maxSpare = 2
	}
	if maxSpare > maxCh {
		maxSpare = maxCh
	}
	if maxSpare < startServers {
		maxSpare = startServers
	}
	memLim := strings.TrimSpace(o.MemoryLimit)
	if memLim == "" {
		memLim = "256M"
	}
	// open_basedir kökü: site kökü (BaseDir) — Laravel gibi docroot=public/ olan
	// projelerde vendor/storage/.env üst dizinde olduğundan docRoot yetmez.
	base := strings.TrimSpace(o.BaseDir)
	if base == "" {
		base = docRoot
	} else {
		base = filepath.Clean(base)
		if strings.Contains(base, "..") {
			return "", "", fmt.Errorf("invalid base dir")
		}
	}
	// session/upload/tmp dizini: site'ye özel tmp (izolasyon korunur).
	tmpDir := strings.TrimSpace(o.ExtraBasedir)
	if tmpDir == "" {
		tmpDir = "/tmp"
	}
	basedir := base
	if tmpDir != "" && !strings.HasPrefix(tmpDir, base+string(filepath.Separator)) && tmpDir != base {
		basedir += ":" + tmpDir
	}
	basedir += ":/tmp:/var/tmp"
	body = fmt.Sprintf(
		poolTemplate,
		domain,
		NormalizeVersion(phpVersion),
		PoolName(domain),
		h.poolUser(o),
		h.poolGroup(o),
		socket,
		h.listenOwner(),
		h.listenGroup(),
		maxCh,
		startServers,
		minSpare,
		maxSpare,
		docRoot,
		basedir,
		memLim,
		tmpDir,
		tmpDir,
		tmpDir,
	)
	return body, socket, nil
}

// writePoolViaHelper pool içeriğini root betiğe (sudo) stdin ile verir; betik
// yazar, `php-fpm -t` test eder, reload eder ve soketin oluştuğunu doğrular.
func writePoolViaHelper(helper, phpVersion, domain, body string) (socket string, err error) {
	cmd := exec.Command("sudo", "-n", helper, "write-pool", NormalizeVersion(phpVersion), domain)
	cmd.Stdin = strings.NewReader(body)
	out, e := cmd.CombinedOutput()
	msg := strings.TrimSpace(string(out))
	if e != nil {
		if msg == "" {
			msg = e.Error()
		}
		return "", fmt.Errorf("panelkafes write-pool: %s", msg)
	}
	// Betik son satırda "OK <socket>" döner.
	last := msg
	if i := strings.LastIndex(msg, "\n"); i >= 0 {
		last = strings.TrimSpace(msg[i+1:])
	}
	socket = strings.TrimSpace(strings.TrimPrefix(last, "OK "))
	if socket == "" {
		return "", fmt.Errorf("panelkafes write-pool: soket alınamadı (%s)", msg)
	}
	return socket, nil
}

// WritePool pool dosyasını yazar; önceki içerik varsa geri alma için döner.
func WritePool(h HostingPoolSettings, domain, phpVersion, docRoot string, opts ...PoolOptions) (socket string, previous []byte, hadPrevious bool, err error) {
	var o PoolOptions
	if len(opts) > 0 {
		o = opts[0]
	}
	domain = strings.TrimSpace(domain)
	if domain == "" || strings.Contains(domain, "..") {
		return "", nil, false, fmt.Errorf("invalid domain")
	}
	docRoot = filepath.Clean(docRoot)
	if strings.Contains(docRoot, "..") {
		return "", nil, false, fmt.Errorf("invalid document root")
	}

	// PanelKafes / helper modu: yazma+test+reload+soket doğrulama root betikte.
	if strings.TrimSpace(h.Helper) != "" {
		body, sock, rerr := RenderPool(h, domain, phpVersion, docRoot, o)
		if rerr != nil {
			return "", nil, false, rerr
		}
		sockOut, herr := writePoolViaHelper(h.Helper, phpVersion, domain, body)
		if herr != nil {
			return "", nil, false, herr
		}
		if strings.TrimSpace(sockOut) != "" {
			sock = sockOut
		}
		return sock, nil, false, nil
	}

	socket = SocketPath(h.listenDir(), domain)
	confPath := poolConfPath(h, phpVersion, domain)
	dir := filepath.Dir(confPath)

	if err := os.MkdirAll(dir, 0o755); err != nil {
		return "", nil, false, fmt.Errorf("pool.d mkdir: %w", err)
	}
	if err := os.MkdirAll(filepath.Dir(socket), 0o755); err != nil {
		return "", nil, false, fmt.Errorf("run dir: %w", err)
	}

	if b, rerr := os.ReadFile(confPath); rerr == nil {
		previous = append([]byte(nil), b...)
		hadPrevious = true
	}

	body, _, rerr := RenderPool(h, domain, phpVersion, docRoot, o)
	if rerr != nil {
		return "", previous, hadPrevious, rerr
	}

	if err := fsutil.AtomicWrite(confPath, []byte(body), 0o644); err != nil {
		return "", previous, hadPrevious, fmt.Errorf("write pool: %w", err)
	}

	if terr := TestFPMConfig(phpVersion); terr != nil {
		_ = RestorePoolConf(h, domain, phpVersion, previous, hadPrevious)
		return "", nil, false, terr
	}

	return socket, previous, hadPrevious, nil
}

// RemovePool belirtilen PHP sürüm dizinindeki pool dosyasını siler.
func RemovePool(h HostingPoolSettings, domain, phpVersion string) error {
	if domain == "" || strings.Contains(domain, "..") {
		return fmt.Errorf("invalid domain")
	}
	p := poolConfPath(h, phpVersion, domain)
	return os.Remove(p)
}

var debianPHPVersionDir = regexp.MustCompile(`^[0-9]+\.[0-9]+$`)

// RemovePoolBestEffortAllVersions /etc/php altındaki X.Y sürüm dizinlerinde panelze (ve eski panelsar) pool dosyasını arar, varsa siler.
// meta eksik site silinirken soket çöplüğünü önlemek için kullanılır. Silinen sürümler (örn. reload için) döner.
func RemovePoolBestEffortAllVersions(h HostingPoolSettings, domain string) []string {
	if domain == "" || strings.Contains(domain, "..") {
		return nil
	}
	entries, err := os.ReadDir("/etc/php")
	if err != nil {
		return nil
	}
	var removed []string
	for _, e := range entries {
		if !e.IsDir() || !debianPHPVersionDir.MatchString(e.Name()) {
			continue
		}
		ver := e.Name()
		p := poolConfPath(h, ver, domain)
		if _, err := os.Stat(p); err == nil {
			_ = os.Remove(p)
			removed = append(removed, ver)
			continue
		}
		leg := filepath.Join(filepath.Dir(p), "panelsar-"+poolSlug(domain)+".conf")
		if _, err := os.Stat(leg); err == nil {
			_ = os.Remove(leg)
			removed = append(removed, ver)
		}
	}
	return removed
}

// ReloadWith helper (root) modunda php-fpm reload'unu betiğe devreder; aksi halde
// doğrudan systemctl/service kullanır. Engine www-data ise reload yetkisi yoktur,
// bu yüzden PanelKafes modunda helper şarttır.
func ReloadWith(h HostingPoolSettings, phpVersion string) error {
	if strings.TrimSpace(h.Helper) != "" {
		v := NormalizeVersion(phpVersion)
		out, err := exec.Command("sudo", "-n", h.Helper, "reload-fpm", v).CombinedOutput()
		if err != nil {
			return fmt.Errorf("panelkafes reload-fpm %s: %w — %s", v, err, strings.TrimSpace(string(out)))
		}
		return nil
	}
	return Reload(phpVersion)
}

// DefaultHelper PanelKafes (cage) modunda set edilir. Boş değilse Reload, engine
// www-data olduğundan systemctl yerine root betiğe (sudo) devreder.
var DefaultHelper string

// Reload debian/ubuntu: systemctl reload php8.2-fpm
func Reload(phpVersion string) error {
	v := NormalizeVersion(phpVersion)
	svc := "php" + v + "-fpm"
	if h := strings.TrimSpace(DefaultHelper); h != "" {
		out, err := exec.Command("sudo", "-n", h, "reload-fpm", v).CombinedOutput()
		if err != nil {
			return fmt.Errorf("panelkafes reload-fpm %s: %w — %s", v, err, strings.TrimSpace(string(out)))
		}
		return nil
	}
	if _, err := exec.LookPath("systemctl"); err == nil {
		out, err := exec.Command("systemctl", "reload", svc).CombinedOutput()
		if err != nil {
			return fmt.Errorf("systemctl reload %s: %w — %s", svc, err, strings.TrimSpace(string(out)))
		}
		return nil
	}
	if _, err := exec.LookPath("service"); err == nil {
		out, err := exec.Command("service", svc, "reload").CombinedOutput()
		if err != nil {
			return fmt.Errorf("service %s reload: %w — %s", svc, err, strings.TrimSpace(string(out)))
		}
		return nil
	}
	return nil
}
