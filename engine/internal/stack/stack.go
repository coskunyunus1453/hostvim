// Package stack — panelden tetiklenen beyaz listeli sunucu paketleri (apt).
// Tam apt/dpkg geri alması bu katmanda yok; betik hata verirse çıktı API’ye döner.
// Betik yolu engine.yaml → hosting.stack_install_script ile değiştirilebilir.
package stack

import (
	"errors"
	"os/exec"
	"strings"

	"panelze/engine/internal/config"
)

const defaultInstallScript = "/usr/local/sbin/panelze-stack-install"

func stackInstallScriptPath(cfg *config.Config) string {
	if cfg == nil {
		return defaultInstallScript
	}
	s := strings.TrimSpace(cfg.Hosting.StackInstallScript)
	if s != "" {
		return s
	}
	return defaultInstallScript
}

// Module — admin arayüzünde listelenen demet.
type Module struct {
	ID          string `json:"id"`
	Category    string `json:"category"`
	Title       string `json:"title"`
	Description string `json:"description"`
	CheckPkg    string `json:"check_package"`
	Installed   bool   `json:"installed"`
}

// Catalog sabit liste (Go ile senkron; betikteki case ile aynı ID’ler).
func Catalog() []Module {
	return []Module{
		{
			ID: "nodejs-lts-pm2", Category: "runtime",
			Title: "Node.js LTS + PM2", Description: "Node.js, npm ve global PM2 — site başına Node uygulama yöneticisi",
			CheckPkg: "nodejs",
		},
		{
			ID: "php-8-3-fpm-extra", Category: "php",
			Title: "PHP 8.3 FPM + uzantılar", Description: "FPM, MySQL, mbstring, xml, zip, curl, intl, bcmath, sqlite",
			CheckPkg: "php8.3-fpm",
		},
		{
			ID: "php-8-2-fpm-extra", Category: "php",
			Title: "PHP 8.2 FPM + uzantılar", Description: "FPM, MySQL, mbstring, xml, zip, curl, intl, bcmath, sqlite",
			CheckPkg: "php8.2-fpm",
		},
		{
			ID: "mail-postfix-relay", Category: "mail",
			Title: "Postfix (SMTP gönderim)", Description: "İnternet sitesi tipi; panel/Laravel .env ile SMTP kullanımına uygun temel MTA",
			CheckPkg: "postfix",
		},
		{
			ID: "mail-dovecot-imap", Category: "mail",
			Title: "Dovecot (IMAP)", Description: "Gelen posta kutusu sunucusu — DNS/MX ve kullanıcı yapılandırması ayrıca gerekir",
			CheckPkg: "dovecot-core",
		},
		{
			ID: "mail-opendkim", Category: "mail",
			Title: "OpenDKIM", Description: "DKIM imzalama (DNS TXT kayıtları ve postfix entegrasyonu elle tamamlanır)",
			CheckPkg: "opendkim",
		},
		{
			ID: "mail-stack-webmail", Category: "mail",
			Title: "Tam posta + Roundcube webmail", Description: "Postfix (25/587/465 TLS) + Dovecot (IMAP) + OpenDKIM + Nginx + Roundcube (SQLite); müşteri webmail.* üzerinden",
			CheckPkg: "roundcube-core",
		},
	}
}

func dpkgInstalled(pkg string) bool {
	if strings.TrimSpace(pkg) == "" {
		return false
	}
	out, err := exec.Command("dpkg-query", "-W", "-f=${Status}", pkg).CombinedOutput()
	if err != nil {
		return false
	}
	s := strings.TrimSpace(string(out))
	return strings.HasPrefix(s, "install ok")
}

func unixUserExists(name string) bool {
	err := exec.Command("id", "-u", strings.TrimSpace(name)).Run()
	return err == nil
}

func mailStackWebmailReady() bool {
	if !dpkgInstalled("roundcube-core") {
		return false
	}
	if !unixUserExists("vmail") {
		return false
	}
	if !dpkgInstalled("dovecot-core") || !dpkgInstalled("postfix") {
		return false
	}
	return true
}

func moduleInstalled(m Module) bool {
	if m.ID == "mail-stack-webmail" {
		return mailStackWebmailReady()
	}
	return dpkgInstalled(m.CheckPkg)
}

// ModulesWithStatus catalog + kurulu mu bilgisi.
func ModulesWithStatus() []Module {
	list := Catalog()
	for i := range list {
		list[i].Installed = moduleInstalled(list[i])
	}
	return list
}

// ValidBundle ID beyaz listede mi.
func ValidBundle(id string) bool {
	id = strings.TrimSpace(id)
	for _, m := range Catalog() {
		if m.ID == id {
			return true
		}
	}
	return false
}

// ErrUnknownBundle — ID beyaz listede değil.
var ErrUnknownBundle = errors.New("bilinmeyen paket demeti")

// InstallBundle sudo ile yapılandırılmış stack betiğini çalıştırır (engine www-data).
func InstallBundle(cfg *config.Config, id string) (string, error) {
	id = strings.TrimSpace(id)
	if !ValidBundle(id) {
		return "", ErrUnknownBundle
	}
	script := stackInstallScriptPath(cfg)
	cmd := exec.Command("sudo", "-n", script, id)
	out, err := cmd.CombinedOutput()
	return strings.TrimSpace(string(out)), err
}
