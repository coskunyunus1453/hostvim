#!/usr/bin/env bash
#
# Panelze — tek sunucuda üretim kurulumu (Debian 12 / Ubuntu 22.04+)
#
# Hedef: güvenlik (engine yalnızca loopback), hız (gzip, static cache), kolaylık (tek komut iskeleti)
#
# Kullanım (root) — sıfır sunucu:
#   git clone <repo> /var/www/panelze && cd /var/www/panelze
#   sudo bash deploy/bootstrap/install-production.sh
#
# Sadece kod/config güncellemesi (paket kurulumu atlanır):
#   cd /var/www/panelze && git pull --ff-only
#   SKIP_APT=1 sudo -E bash deploy/bootstrap/install-production.sh
#
# Ortam değişkenleri (isteğe bağlı):
#   PANELZE_HOME=/var/www/panelze   (verilmezse repo kökü = betiğin bulunduğu proje; eski: PANELSAR_HOME yedeği)
#   SERVER_NAME=_          # sadece IP ile erişim için default_server (nginx şablonunda _)
#   LETS_ENCRYPT_EMAIL=admin@ornek.com
#   SKIP_APT=1             # paket kurulumunu atla (yeniden çalıştırma)
#   SKIP_UFW=1             # UFW kurma
#   WITH_MARIADB=1         # MariaDB kur ve panel veritabanını oluştur (önerilir)
#   WITH_POSTGRES=1        # Engine için PostgreSQL (isteğe bağlı)
#   WITH_NODE_REPO=1       # NodeSource 20.x ekle (frontend build için önerilir)
#   PANELZE_GO_VERSION=1.23.4  # engine/go.mod ile uyumlu (varsayılan; go.dev'den kurulur)
#   PANELZE_PHP_VERSION=8.4    # panel/composer.lock (Ondrej/Sury); Symfony 8 için 8.4 önerilir
#   PANELZE_EXTRA_PHP_FPM_VERSIONS="8.3 8.2"  # ek FPM (boş = yalnız ana sürüm)
#   WITH_PHPMYADMIN=1           # apt phpMyAdmin + Nginx /phpmyadmin + PHPMYADMIN_URL
#   WITH_CERTBOT=1              # certbot + python3-certbot-nginx (Let's Encrypt)
#   WITH_APACHE=1               # apache2; Nginx 80 ile çakışmaz — Apache :8080 + engine apache_http_port: 8080
#   WITH_OPENLITESPEED=1        # OpenLiteSpeed backend :8088 (nginx edge 80/443)
#   WITH_LOCAL_POSTFIX=1        # Postfix + mailutils (panel giden posta: sendmail; Admin → Giden posta’dan SMTP’ye geçilebilir)
#   WITH_MAIL_STACK_WEBMAIL=1   # Tam posta + Roundcube webmail (müşteri e-posta sayfasından kullanım için; varsayılan: 1)
#   WITH_BIND_DNS=1             # BIND9 yetkili DNS — panel DNS kayıtları sunucuda yayınlanır (varsayılan: 1)
#   PANELZE_DNS_NS1=ns1.ornek.com  # İsteğe bağlı; boşsa sunucu FQDN kullanılır
#   SKIP_DB_SEED=1              # migrate sonrası db:seed atla
#   PANELZE_UPDATE_ONLY=1       # Güncelleme kilidi: RESET_PANEL_DB=0, data/www ve migrate:fresh yok (install-update*.sh)
#   RESET_PANEL_DB=1            # DİKKAT: migrate:fresh + (varsayılan) data/www vb. temizlik — üretimde yalnızca gerektiğinde
#   PANELZE_FRESH_INSTALL=1     # RESET_PANEL_DB=1 ile aynı (fabrika / boş lab sunucusu; müşteri “onarım”unda kullanmayın)
#   PANELZE_SEED_DEMO_USERS=1  # Demo reseller/user hesaplarını da seed et (varsayılan: 0)
#   (engine systemd drop-in) PANELZE_TERMINAL_NO_ROOT=1  # web terminali www-data kabuğunda (varsayılan: root sudo)
#   PANELZE_ADMIN_EMAIL=...       # ilk admin e-posta (verilirse her şeyi geçer; önerilir)
#   PANELZE_ADMIN_EMAIL_DOMAIN=…  # örn. ornek.com → admin@ornek.com (açık e-posta yoksa)
#   PANELZE_APP_URL=…             # örn. https://panel.ornek.com — .env APP_URL + e-posta türetimi için
#   PANELZE_PUBLIC_HOST=panel.ornek.com  # nginx server_name + otomatik Let's Encrypt; APP_URL bos ise http://HOST kullanilir
#   PANELZE_RUN_CERTBOT=1         # 0: certbot calistirma (DNS hazir degilken)
#   PANELZE_LICENSE_KEY=…         # İsteğe bağlı; bos birakilabilir (müşteri Admin → Lisans’tan yapistirir)
#   LETS_ENCRYPT_EMAIL=…          # ACME; PANELZE_ADMIN_EMAIL yoksa ilk admin e-postası olarak da kullanılabilir
#   PANELZE_DEFAULT_TIMEZONE=Europe/Istanbul  # kurulumda timedatectl (varsayılan UTC)
#   PANELZE_PRESERVE_ADMIN_PASSWORD=1  # DB’de kullanıcı varken şifreyi değiştirme / dosyada gösterme (otomasyon güncellemesi için)
#
set -euo pipefail

# panelze-* kaynak dosyasını /usr/local/sbin'e kurar.
install_host_tool() {
  local base="$1"
  local src="$REPO_ROOT/deploy/host/panelze-${base}"
  if [[ ! -f "$src" ]]; then
    return 0
  fi
  install -m 755 "$src" "/usr/local/sbin/panelze-${base}"
  ln -sfn "/usr/local/sbin/panelze-${base}" "/usr/local/sbin/panelsar-${base}" 2>/dev/null || true
}

_SCRIPT_DIR_BOOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
_REPO_EARLY="$(cd "$_SCRIPT_DIR_BOOT/../.." && pwd)"
_PANELZE_EARLY="${PANELZE_HOME:-${PANELSAR_HOME:-$_REPO_EARLY}}"

# Harici lib yoksa da çalışsın (sunucuda eski checkout / eksik deploy/host/lib)
if [[ -f "$_SCRIPT_DIR_BOOT/../host/lib/install-mode.sh" ]]; then
  # shellcheck source=../host/lib/install-mode.sh
  source "$_SCRIPT_DIR_BOOT/../host/lib/install-mode.sh"
elif [[ -f "$_REPO_EARLY/deploy/host/lib/install-mode.sh" ]]; then
  # shellcheck source=../host/lib/install-mode.sh
  source "$_REPO_EARLY/deploy/host/lib/install-mode.sh"
else
  panelze_resolve_install_mode() {
    local home="${PANELZE_HOME:-${PANELSAR_HOME:-/var/www/panelze}}"
    if [[ "${PANELZE_FRESH_INSTALL:-0}" == "1" ]] || [[ "${PANELZE_FRESH_INSTALL:-0}" == "yes" ]]; then echo "fresh"; return; fi
    if [[ "${RESET_PANEL_DB:-0}" == "1" ]] || [[ "${RESET_PANEL_DB:-0}" == "yes" ]]; then echo "fresh"; return; fi
    if [[ "${PANELZE_UPDATE_ONLY:-0}" == "1" ]] || [[ "${PANELZE_UPDATE_ONLY:-0}" == "yes" ]]; then echo "update"; return; fi
    if [[ -f "$home/panel/.env" ]]; then echo "update"; return; fi
    if [[ -d "$home/data/www" ]] && find "$home/data/www" -mindepth 1 -maxdepth 1 \( -type d -o -type f \) -print -quit 2>/dev/null | grep -q .; then
      echo "update"; return
    fi
    echo "fresh"
  }
  panelze_apply_update_safe_env() {
    export PANELZE_UPDATE_ONLY=1 RESET_PANEL_DB=0 PANELZE_FRESH_INSTALL=0 CLEAN_HOSTING_STATE_ON_RESET=0
    export PANELZE_PRESERVE_ADMIN_PASSWORD="${PANELZE_PRESERVE_ADMIN_PASSWORD:-1}"
    export SKIP_APT="${SKIP_APT:-${PANELZE_SKIP_APT_ON_UPDATE:-1}}"
  }
  panelze_print_install_mode_banner() {
    if [[ "$1" == "update" ]]; then
      echo "╔══════════════════════════════════════════════════════════════╗"
      echo "║  Panelze GÜNCELLEME — panel DB, siteler ve MySQL korunur       ║"
      echo "╚══════════════════════════════════════════════════════════════╝"
    else
      echo "╔══════════════════════════════════════════════════════════════╗"
      echo "║  Panelze YENİ KURULUM                                         ║"
      echo "╚══════════════════════════════════════════════════════════════╝"
    fi
  }
fi

echo "==> install-production.sh başladı ($_PANELZE_EARLY)"

PANELZE_INSTALL_MODE="$(panelze_resolve_install_mode)"
if [[ "$PANELZE_INSTALL_MODE" == "update" ]]; then
  panelze_apply_update_safe_env
  panelze_print_install_mode_banner "update"
else
  if [[ "${PANELZE_FRESH_INSTALL:-0}" == "1" ]] || [[ "${PANELZE_FRESH_INSTALL:-0}" == "yes" ]]; then
    export RESET_PANEL_DB=1
  fi
  panelze_print_install_mode_banner "fresh"
fi
export RESET_PANEL_DB PANELZE_UPDATE_ONLY PANELZE_FRESH_INSTALL CLEAN_HOSTING_STATE_ON_RESET

trap 'echo "HATA: install-production satır \$LINENO, çıkış \$?" >&2' ERR

# Kolay kurulum: varsayılan olarak MariaDB + Node 20 kaynağı
WITH_MARIADB="${WITH_MARIADB:-1}"
WITH_NODE_REPO="${WITH_NODE_REPO:-1}"

[[ "$(id -u)" -eq 0 ]] || { echo "Root ile çalıştırın: sudo bash $0" >&2; exit 1; }

SCRIPT_DIR="$_SCRIPT_DIR_BOOT"
# shellcheck source=ensure-go-toolchain.sh
source "$SCRIPT_DIR/ensure-go-toolchain.sh"
# shellcheck source=ensure-php-packages.sh
source "$SCRIPT_DIR/ensure-php-packages.sh"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
# Varsayılan: klon/kök dizin = repo (PANELZE_HOME uyarısı olmaması için). Üretimde isterseniz /var/www/panelze verin.
PANELZE_HOME="${PANELZE_HOME:-${PANELSAR_HOME:-$REPO_ROOT}}"
PANELZE_BRANCH="${PANELZE_BRANCH:-${PANELSAR_BRANCH:-main}}"
PANELZE_AUTO_SYNC_GIT="${PANELZE_AUTO_SYNC_GIT:-1}"
SERVER_NAME="${SERVER_NAME:-_}"
LETS_ENCRYPT_EMAIL="${LETS_ENCRYPT_EMAIL:-}"
APP_PROFILE="${APP_PROFILE:-customer}"
# 2FA kurumsal politikada açılacaksa ENFORCE_ADMIN_2FA=true verin; varsayılan kapalı.
if [[ "${ENFORCE_ADMIN_2FA:-}" == "" ]]; then
  ENFORCE_ADMIN_2FA=false
fi

if [[ ! -d "$REPO_ROOT/panel" ]] || [[ ! -d "$REPO_ROOT/engine" ]]; then
  echo "Hata: panel/ veya engine/ bulunamadı. Bu betiği repo kökünden çalıştırın (PANELZE_HOME=$PANELZE_HOME)." >&2
  exit 1
fi

if [[ "$PANELZE_HOME" != "$REPO_ROOT" ]]; then
  echo "Uyarı: PANELZE_HOME ($PANELZE_HOME) ile repo ($REPO_ROOT) farklı. Aynı yapın önerilir." >&2
fi

# Tek komut güncelleme garantisi: install-production doğrudan çalıştırılsa bile önce repo güncellensin.
# Varsayılan açık (PANELZE_AUTO_SYNC_GIT=1). Kapatmak için: PANELZE_AUTO_SYNC_GIT=0
if [[ "$PANELZE_AUTO_SYNC_GIT" == "1" ]] || [[ "$PANELZE_AUTO_SYNC_GIT" == "yes" ]]; then
  if [[ -d "$REPO_ROOT/.git" ]]; then
    echo "==> Git otomatik senkron: branch=$PANELZE_BRANCH"
    git config --system --add safe.directory "$REPO_ROOT" 2>/dev/null || true
    if git -C "$REPO_ROOT" fetch origin "$PANELZE_BRANCH" --depth 1 >/dev/null 2>&1; then
      if git -C "$REPO_ROOT" show-ref --verify --quiet "refs/remotes/origin/$PANELZE_BRANCH"; then
        git -C "$REPO_ROOT" checkout "$PANELZE_BRANCH" >/dev/null 2>&1 || true
        if git -C "$REPO_ROOT" merge --ff-only "origin/$PANELZE_BRANCH" >/dev/null 2>&1; then
          echo "==> Git senkron tamam: $(git -C "$REPO_ROOT" rev-parse --short HEAD)"
        else
          echo "Uyarı: FF merge yapılamadı; mevcut checkout ile devam ediliyor." >&2
        fi
      else
        echo "Uyarı: origin/$PANELZE_BRANCH bulunamadı; mevcut checkout ile devam." >&2
      fi
    else
      echo "Uyarı: git fetch başarısız; mevcut checkout ile devam." >&2
    fi
  fi
fi

export DEBIAN_FRONTEND=noninteractive

detect_php_fpm_sock() {
  local pv="${PANELZE_PHP_VERSION:-${PANELSAR_PHP_VERSION:-8.4}}"
  local s
  for s in "/run/php/php${pv}-fpm.sock" /run/php/php8.4-fpm.sock /run/php/php8.3-fpm.sock /run/php/php8.2-fpm.sock /run/php/php-fpm.sock; do
    if [[ -S "$s" ]]; then
      echo "$s"
      return 0
    fi
  done
  echo "/run/php/php${pv}-fpm.sock"
}

ensure_engine_port_free() {
  local pids pid comm foreign
  pids="$(lsof -ti :9090 2>/dev/null || true)"
  [[ -n "$pids" ]] || return 0

  echo "==> Uyarı: 9090 portu kullanımda. Çakışan süreçler kontrol ediliyor..."
  echo "$pids" | xargs -r ps -o pid=,user=,comm= -p || true

  # Önce servisleri durdur (varsa)
  systemctl stop panelze-engine 2>/dev/null || true
  systemctl stop panelsar-engine 2>/dev/null || true
  sleep 1

  pids="$(lsof -ti :9090 2>/dev/null || true)"
  [[ -n "$pids" ]] || return 0

  foreign=0
  for pid in $pids; do
    comm="$(ps -p "$pid" -o comm= 2>/dev/null | tr -d '[:space:]')"
    if [[ "$comm" =~ ^(panelze-engine|panelsar-engine|go)$ ]]; then
      kill -TERM "$pid" 2>/dev/null || true
    else
      foreign=1
      echo "Hata: 9090 portunu Panelze dışı süreç kullanıyor (pid=$pid, comm=$comm)." >&2
    fi
  done
  sleep 1

  pids="$(lsof -ti :9090 2>/dev/null || true)"
  if [[ -n "$pids" ]]; then
    foreign=0
    for pid in $pids; do
      comm="$(ps -p "$pid" -o comm= 2>/dev/null | tr -d '[:space:]')"
      if [[ "$comm" =~ ^(panelze-engine|panelsar-engine|go)$ ]]; then
        kill -KILL "$pid" 2>/dev/null || true
      else
        foreign=1
        echo "Hata: 9090 portu hâlâ Panelze dışı süreçte (pid=$pid, comm=$comm)." >&2
      fi
    done
    sleep 1
    pids="$(lsof -ti :9090 2>/dev/null || true)"
    if [[ -n "$pids" ]] || [[ "$foreign" -eq 1 ]]; then
      echo "Kurulum durduruldu: 9090 portu boşaltılamadı. Güvenlik için dış süreçler öldürülmedi." >&2
      return 1
    fi
  fi
}

# http(s)://host[:port]/yol -> host (FQDN). IP / localhost ise bos cikis (hata kodu 1).
panelze_url_hostname() {
  local raw="${1:-}"
  [[ -n "$raw" ]] || return 1
  raw="${raw#http://}"
  raw="${raw#https://}"
  raw="${raw%%/*}"
  raw="${raw%%:*}"
  raw="${raw%%\?*}"
  [[ -n "$raw" ]] || return 1
  if [[ "$raw" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    return 1
  fi
  case "$raw" in
    localhost | 127.0.0.1) return 1 ;;
  esac
  echo "$raw"
}

yaml_value_from_block() {
  local file="$1" block="$2" key="$3"
  [[ -f "$file" ]] || return 1
  awk -v block="$block" -v key="$key" '
    function ltrim(s){ sub(/^[[:space:]]+/, "", s); return s }
    function rtrim(s){ sub(/[[:space:]]+$/, "", s); return s }
    function trim(s){ return rtrim(ltrim(s)) }
    BEGIN { inblock=0 }
    {
      line=$0
      if (match(line, "^[[:space:]]*" block ":[[:space:]]*$")) {
        inblock=1
        next
      }
      if (inblock && match(line, "^[[:space:]]*[A-Za-z0-9_]+:[[:space:]]*$")) {
        inblock=0
      }
      if (!inblock) next
      if (match(line, "^[[:space:]]*" key ":[[:space:]]*")) {
        sub("^[[:space:]]*" key ":[[:space:]]*", "", line)
        gsub(/^"|"$/, "", line)
        gsub(/^'\''|'\''$/, "", line)
        print trim(line)
        exit
      }
    }
  ' "$file"
}

panelze_git_safe_directory() {
  local d="$1"
  [[ -d "$d/.git" ]] || return 0
  if ! git config --system --get-all safe.directory 2>/dev/null | grep -qxF "$d"; then
    git config --system --add safe.directory "$d"
  fi
}

# Nginx panel 80/443 kullanır; Apache yalnızca HTTP 8080 (engine hosting.apache_http_port ile uyumlu)
panelze_apache_bind_8080() {
  local pc=/etc/apache2/ports.conf
  [[ -f "$pc" ]] || return 1
  sed -i \
    -e 's/^Listen 80$/Listen 8080/' \
    -e 's/^Listen \[::\]:80$/Listen [::]:8080/' \
    "$pc"
  # Varsayılan SSL sitesi + 443 dinleyicisi Nginx ile çakışır
  sed -i \
    -e 's/^Listen 443$/#Listen 443/' \
    -e 's/^Listen \[::\]:443$/#Listen [::]:443/' \
    "$pc" 2>/dev/null || true
  a2dissite default-ssl 2>/dev/null || true
  a2dissite default-ssl.conf 2>/dev/null || true
  local f
  for f in /etc/apache2/sites-available/*.conf; do
    [[ -f "$f" ]] || continue
    sed -i \
      -e 's/<VirtualHost \*:80>/<VirtualHost *:8080>/g' \
      -e 's/<VirtualHost \*:80 >/<VirtualHost *:8080>/g' \
      "$f"
  done
  apache2ctl configtest
}

# apt kurulumu
if [[ "${SKIP_APT:-}" != "1" ]]; then
  apt-get update -qq
  apt-get install -y -qq \
    nginx \
    curl \
    ca-certificates \
    sudo \
    git \
    rsync \
    unzip \
    sqlite3 \
    acl \
    software-properties-common \
    lsb-release \
    gnupg

  ensure_php_fpm_packages

  if [[ "${WITH_NODE_REPO}" == "1" ]] || [[ "${WITH_NODE_REPO}" == "yes" ]]; then
    if [[ ! -f /etc/apt/sources.list.d/nodesource.list ]]; then
      curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    fi
    apt-get install -y -qq nodejs
  else
    apt-get install -y -qq nodejs npm || true
  fi
  if ! command -v npm >/dev/null 2>&1; then
    echo "Hata: npm bulunamadı (frontend derlemesi zorunlu). WITH_NODE_REPO=1 ile NodeSource kurun veya nodejs/npm kurun." >&2
    exit 1
  fi

  if ! command -v composer >/dev/null 2>&1; then
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
  fi

  if [[ "${WITH_MARIADB}" == "1" ]] || [[ "${WITH_MARIADB}" == "yes" ]]; then
    apt-get install -y -qq mariadb-server mariadb-client
    systemctl enable --now mariadb
  fi

  if [[ "${WITH_CERTBOT:-1}" == "1" ]] || [[ "${WITH_CERTBOT:-1}" == "yes" ]]; then
    apt-get install -y -qq certbot python3-certbot-nginx
  fi

  if [[ "${WITH_APACHE:-1}" == "1" ]] || [[ "${WITH_APACHE:-1}" == "yes" ]]; then
    apt-get install -y -qq apache2 libapache2-mod-fcgid
    a2enmod proxy_fcgi rewrite headers 2>/dev/null || true
    panelze_apache_bind_8080
    systemctl enable apache2
    systemctl restart apache2
    echo "==> Apache etkin: HTTP :8080 (Nginx edge :80/:443). Engine apache_http_port=8080 ile uyumlu."
  fi

  if [[ "${WITH_OPENLITESPEED:-1}" == "1" ]] || [[ "${WITH_OPENLITESPEED:-1}" == "yes" ]]; then
    if [[ -x "$REPO_ROOT/deploy/host/panelze-openlitespeed-setup.sh" ]]; then
      chmod +x "$REPO_ROOT/deploy/host/panelze-openlitespeed-setup.sh"
      PANELZE_OLS_HTTP_PORT="${PANELZE_OLS_HTTP_PORT:-8088}" bash "$REPO_ROOT/deploy/host/panelze-openlitespeed-setup.sh"
      install -m 755 "$REPO_ROOT/deploy/host/panelze-openlitespeed-setup.sh" /usr/local/sbin/panelze-openlitespeed-setup
    fi
  fi

  if [[ "${WITH_PHPMYADMIN:-1}" == "1" ]] || [[ "${WITH_PHPMYADMIN:-1}" == "yes" ]]; then
    echo "phpmyadmin phpmyadmin/reconfigure-webserver multiselect none" | debconf-set-selections
    echo "phpmyadmin phpmyadmin/dbconfig-install boolean false" | debconf-set-selections
    apt-get install -y -qq phpmyadmin
  fi

  if [[ "${WITH_LOCAL_POSTFIX:-1}" == "1" ]] || [[ "${WITH_LOCAL_POSTFIX:-1}" == "yes" ]]; then
    echo "postfix postfix/main_mailer_type select Internet Site" | debconf-set-selections
    echo "postfix postfix/mailname string $(hostname -f 2>/dev/null || hostname)" | debconf-set-selections
    apt-get install -y -qq postfix mailutils
    systemctl enable postfix
    systemctl restart postfix
  fi

  if [[ "${WITH_POSTGRES:-}" == "1" ]] || [[ "${WITH_POSTGRES:-}" == "yes" ]]; then
    apt-get install -y -qq postgresql postgresql-client
    systemctl enable --now postgresql
  fi
else
  require_php_for_composer
fi

# Sunucu saat dilimi (panel engine www-data ile timedatectl kullanır)
PANELZE_DEFAULT_TIMEZONE="${PANELZE_DEFAULT_TIMEZONE:-UTC}"
if command -v timedatectl >/dev/null 2>&1; then
  echo "==> Sistem saat dilimi: $PANELZE_DEFAULT_TIMEZONE"
  timedatectl set-timezone "$PANELZE_DEFAULT_TIMEZONE" 2>/dev/null || true
elif [[ -f "/usr/share/zoneinfo/${PANELZE_DEFAULT_TIMEZONE}" ]]; then
  echo "$PANELZE_DEFAULT_TIMEZONE" >/etc/timezone
  ln -sf "/usr/share/zoneinfo/${PANELZE_DEFAULT_TIMEZONE}" /etc/localtime
fi

# PHP-FPM soketi (apt sonrası)
PHP_FPM_SOCK="$(detect_php_fpm_sock)"

mkdir -p "$PANELZE_HOME/data"/{www,tmp,ssl,backups,logs,vhosts,apache-vhosts,ols-staging}
mkdir -p /etc/panelze
chown -R www-data:www-data "$PANELZE_HOME/data"

# RESET modunda eski hosting kalıntılarını da temizle (plesk benzeri "silince anında düşsün" davranışı).
if [[ "${RESET_PANEL_DB:-0}" == "1" ]] || [[ "${RESET_PANEL_DB:-0}" == "yes" ]]; then
  CLEAN_HOSTING_STATE_ON_RESET="${CLEAN_HOSTING_STATE_ON_RESET:-1}"
  if [[ "$CLEAN_HOSTING_STATE_ON_RESET" == "1" ]] || [[ "$CLEAN_HOSTING_STATE_ON_RESET" == "yes" ]]; then
    echo "==> RESET_PANEL_DB=1: eski hosting state temizleniyor (webroot/vhost/ssl/backup)."
    rm -rf "$PANELZE_HOME/data/www/"* 2>/dev/null || true
    rm -rf "$PANELZE_HOME/data/ssl/"* 2>/dev/null || true
    rm -rf "$PANELZE_HOME/data/backups/"* 2>/dev/null || true
    rm -rf /var/backups/panelze/* /var/backups/panelsar/* 2>/dev/null || true
    rm -f /etc/nginx/sites-enabled/panelze-*.conf /etc/nginx/sites-enabled/panelsar-*.conf 2>/dev/null || true
    rm -f /etc/apache2/sites-enabled/panelze-*.conf /etc/apache2/sites-enabled/panelsar-*.conf 2>/dev/null || true
    rm -f /etc/apache2/sites-available/panelze-*.conf /etc/apache2/sites-available/panelsar-*.conf 2>/dev/null || true
    nginx -t >/dev/null 2>&1 && systemctl reload nginx || true
    if command -v apache2ctl >/dev/null 2>&1; then
      apache2ctl configtest >/dev/null 2>&1 && systemctl reload apache2 || true
    fi
  fi
fi

# Kimlik anahtarları:
# - İlk kurulumda güvenli rastgele üretilir.
# - Sonraki kurulum/güncellemelerde mevcut /etc/panelze/engine.yaml (veya eski /etc/panelsar/engine.yaml) içinden okunup korunur.
#   Böylece panel↔engine auth kopmaz.
ENGINE_DST="/etc/panelze/engine.yaml"
ENGINE_LEGACY_DST="/etc/panelsar/engine.yaml"
FORCE_ROTATE_ENGINE_KEYS="${FORCE_ROTATE_ENGINE_KEYS:-0}"

ENGINE_KEY_SRC=""
if [[ -f "$ENGINE_DST" ]] && [[ "$FORCE_ROTATE_ENGINE_KEYS" != "1" ]]; then
  ENGINE_KEY_SRC="$ENGINE_DST"
elif [[ -f "$ENGINE_LEGACY_DST" ]] && [[ "$FORCE_ROTATE_ENGINE_KEYS" != "1" ]]; then
  ENGINE_KEY_SRC="$ENGINE_LEGACY_DST"
fi
if [[ -n "$ENGINE_KEY_SRC" ]]; then
  EXISTING_INTERNAL_KEY="$(yaml_value_from_block "$ENGINE_KEY_SRC" "security" "internal_api_key" || true)"
  EXISTING_ENGINE_JWT="$(yaml_value_from_block "$ENGINE_KEY_SRC" "security" "jwt_secret" || true)"
  EXISTING_ENGINE_SECRET="$(yaml_value_from_block "$ENGINE_KEY_SRC" "server" "secret_key" || true)"
else
  EXISTING_INTERNAL_KEY=""
  EXISTING_ENGINE_JWT=""
  EXISTING_ENGINE_SECRET=""
fi

INTERNAL_KEY="${EXISTING_INTERNAL_KEY:-$(openssl rand -hex 32)}"
ENGINE_SECRET="${EXISTING_ENGINE_SECRET:-$(openssl rand -hex 32)}"
ENGINE_JWT="${EXISTING_ENGINE_JWT:-$(openssl rand -hex 32)}"
# Boş string atanmışsa :- genişlemesi yeni değer üretmez; engine panel auth kırılır.
[[ -n "$INTERNAL_KEY" ]] || INTERNAL_KEY="$(openssl rand -hex 32)"
[[ -n "$ENGINE_SECRET" ]] || ENGINE_SECRET="$(openssl rand -hex 32)"
[[ -n "$ENGINE_JWT" ]] || ENGINE_JWT="$(openssl rand -hex 32)"
ENGINE_DB_PASS="$(openssl rand -hex 24)"
PANEL_ORIGINS="${PANEL_ORIGINS:-http://localhost,http://127.0.0.1}"
if [[ "$SERVER_NAME" != "_" ]]; then
  PANEL_ORIGINS="$PANEL_ORIGINS,http://$SERVER_NAME,https://$SERVER_NAME"
fi

# Önceki kurulumlardan kalan zayıf/placeholder anahtarlar yayın güvenliği için döndürülür.
if [[ "$INTERNAL_KEY" == "panelze-engine-internal-dev" ]] || [[ "$INTERNAL_KEY" == "panelsar-engine-internal-dev" ]] || [[ "$INTERNAL_KEY" == *"change"* ]]; then
  INTERNAL_KEY="$(openssl rand -hex 32)"
fi
if [[ "$ENGINE_SECRET" == *"change"* ]]; then
  ENGINE_SECRET="$(openssl rand -hex 32)"
fi
if [[ "$ENGINE_JWT" == *"change"* ]] || [[ "$ENGINE_JWT" == *"dev"* ]]; then
  ENGINE_JWT="$(openssl rand -hex 32)"
fi

# Engine yaml
ENGINE_TMPL="$REPO_ROOT/deploy/configs/engine.production.yaml"
sed \
  -e "s|__INTERNAL_KEY__|$INTERNAL_KEY|g" \
  -e "s|__ENGINE_SECRET_KEY__|$ENGINE_SECRET|g" \
  -e "s|__ENGINE_JWT_SECRET__|$ENGINE_JWT|g" \
  -e "s|__ENGINE_DB_PASSWORD__|$ENGINE_DB_PASS|g" \
  -e "s|__PANELZE_HOME__|$PANELZE_HOME|g" \
  -e "s|__LETS_ENCRYPT_EMAIL__|$LETS_ENCRYPT_EMAIL|g" \
  -e "s|__PHP_FPM_SOCKET__|$PHP_FPM_SOCK|g" \
  -e "s|__PANEL_ORIGINS__|$PANEL_ORIGINS|g" \
  "$ENGINE_TMPL" > "$ENGINE_DST"
chmod 640 "$ENGINE_DST"
chown root:www-data "$ENGINE_DST"

# PostgreSQL engine kullanıcısı (isteğe bağlı)
if [[ "${WITH_POSTGRES:-}" == "1" ]] || [[ "${WITH_POSTGRES:-}" == "yes" ]]; then
  sudo -u postgres psql -tc "SELECT 1 FROM pg_roles WHERE rolname='panelze'" | grep -q 1 || \
    sudo -u postgres psql -c "CREATE USER panelze WITH PASSWORD '$ENGINE_DB_PASS';"
  sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname='panelze'" | grep -q 1 || \
    sudo -u postgres psql -c "CREATE DATABASE panelze OWNER panelze;"
fi

# Go engine derle (apt'teki golang-go genelde esiktir; ensure-go-toolchain.sh go.dev sürümünü kurar)
ensure_go_toolchain
(cd "$REPO_ROOT/engine" && go build -buildvcs=false -o /usr/local/bin/panelze-engine ./cmd/panelze-engine)
chmod 755 /usr/local/bin/panelze-engine

# systemd
sed \
  -e "s|__PANELZE_HOME__|$PANELZE_HOME|g" \
  -e "s|__ENGINE_BINARY__|/usr/local/bin/panelze-engine|g" \
  "$REPO_ROOT/deploy/systemd/panelze-engine.service" > /etc/systemd/system/panelze-engine.service
systemctl daemon-reload
if [[ -x /usr/local/bin/panelze-engine ]]; then
  ensure_engine_port_free
  systemctl enable panelze-engine
  if ! systemctl restart panelze-engine; then
    echo "Hata: panelze-engine başlatılamadı. Son loglar:" >&2
    journalctl -u panelze-engine -n 80 --no-pager >&2 || true
  fi
fi

# Engine www-data iken nginx sites-enabled'a yazamaz; sudo ile izinli betikler
install_host_tool nginx-vhost
install_host_tool apache-vhost
install_host_tool ols-vhost
install_host_tool stack-install
install_host_tool mail-stack-setup.sh
install_host_tool mail-provision
install_host_tool bind-sync
install_host_tool terminal-root
install_host_tool php-ini
install_host_tool security
install_host_tool cleaner
install_host_tool node-pm2
if [[ -f "$REPO_ROOT/deploy/host/panelze-panel-update" ]]; then
  install -m 755 "$REPO_ROOT/deploy/host/panelze-panel-update" /usr/local/sbin/panelze-panel-update
fi
install_host_tool system-settings
if [[ -f "$REPO_ROOT/deploy/scripts/panelze-post-install.sh" ]]; then
  install -m 755 "$REPO_ROOT/deploy/scripts/panelze-post-install.sh" /usr/local/sbin/panelze-post-install
  ln -sfn /usr/local/sbin/panelze-post-install /usr/local/sbin/panelsar-post-install
fi
if [[ -f "$REPO_ROOT/deploy/scripts/repair-mysql-users.sh" ]]; then
  install -m 755 "$REPO_ROOT/deploy/scripts/repair-mysql-users.sh" /usr/local/sbin/panelze-repair-mysql
  ln -sfn /usr/local/sbin/panelze-repair-mysql /usr/local/sbin/panelsar-repair-mysql
fi
if [[ -f "$REPO_ROOT/deploy/scripts/fix-hosting-permissions.sh" ]]; then
  install -m 755 "$REPO_ROOT/deploy/scripts/fix-hosting-permissions.sh" /usr/local/sbin/panelze-fix-hosting-perms
  ln -sfn /usr/local/sbin/panelze-fix-hosting-perms /usr/local/sbin/panelsar-fix-hosting-perms
fi
# PM2 global (Node uygulamaları)
if command -v npm >/dev/null 2>&1 && ! command -v pm2 >/dev/null 2>&1; then
  npm install -g pm2 2>/dev/null || true
fi
# Sunucu açılışında PM2 resurrect (www-data)
PM2_HOME_DIR="${PANELZE_PM2_HOME:-${PANELZE_HOME:-/var/www/panelze}/data/pm2}"
mkdir -p "$PM2_HOME_DIR"
chown -R www-data:www-data "$PM2_HOME_DIR" 2>/dev/null || true
if [[ -f "$REPO_ROOT/deploy/systemd/panelze-pm2.service" ]]; then
  sed "s|@PM2_HOME@|${PM2_HOME_DIR}|g" "$REPO_ROOT/deploy/systemd/panelze-pm2.service" > /etc/systemd/system/panelze-pm2.service
  systemctl daemon-reload
  systemctl enable panelze-pm2.service 2>/dev/null || true
fi
cat > /etc/sudoers.d/panelze-engine <<'SUDOERS'
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-nginx-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-nginx-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-apache-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-apache-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-ols-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-ols-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-stack-install
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-stack-install
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-mail-provision
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-terminal-root
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-terminal-root
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-php-ini
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-php-ini
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-security
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-security
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-system-settings
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-system-settings
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-panel-update
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-node-pm2
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-node-pm2
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-bind-sync
SUDOERS
chmod 440 /etc/sudoers.d/panelze-engine
visudo -cf /etc/sudoers.d/panelze-engine

# Tam posta + Roundcube webmail (müşteri e-posta sayfası)
if [[ "${WITH_MAIL_STACK_WEBMAIL:-1}" == "1" ]] || [[ "${WITH_MAIL_STACK_WEBMAIL:-1}" == "yes" ]]; then
  if command -v dpkg-query >/dev/null 2>&1 && ! dpkg-query -W -f='${Status}' roundcube-core 2>/dev/null | grep -q 'install ok'; then
    if [[ "${SKIP_APT:-}" != "1" ]] && command -v add-apt-repository >/dev/null 2>&1 && ! apt-cache show roundcube-core &>/dev/null 2>&1; then
      add-apt-repository -y universe 2>/dev/null || true
      apt-get update -qq 2>/dev/null || true
    fi
    if [[ -x /usr/local/sbin/panelze-stack-install ]]; then
      echo "==> Tam posta + Roundcube webmail kuruluyor (mail-stack-webmail)..."
      /usr/local/sbin/panelze-stack-install mail-stack-webmail || echo "UYARI: mail-stack-webmail kurulumu başarısız — kurulum sonrası: php artisan panelze:ensure-mail-stack" >&2
    fi
  fi
fi

# Panel .env
PANEL_ROOT="$REPO_ROOT/panel"
export PANEL_ROOT PANELZE_HOME
DEPLOY_SCRIPTS="$REPO_ROOT/deploy/scripts"
# shellcheck source=../scripts/lib/panelze-deploy-common.sh
source "$DEPLOY_SCRIPTS/lib/panelze-deploy-common.sh"
ENV_EXAMPLE="$PANEL_ROOT/.env.production.example"
ENV_FILE="$PANEL_ROOT/.env"
if [[ ! -f "$ENV_FILE" ]]; then
  if [[ -f "$ENV_EXAMPLE" ]]; then
    cp "$ENV_EXAMPLE" "$ENV_FILE"
  else
    cp "$PANEL_ROOT/.env.example" "$ENV_FILE"
  fi
fi

# APP_KEY: composer install + vendor/autoload sonrası üretilir (aşağıda). Erken key:generate
# vendor yokken sessizce başarısız olup APP_KEY boş kalıyordu → db:seed "No application encryption key".

# .env üretim ayarları (sed ile idempotent değil; basit grep ile atla)
update_env() {
  local key="$1" val="$2"
  if grep -q "^${key}=" "$ENV_FILE" 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
  else
    echo "${key}=${val}" >> "$ENV_FILE"
  fi
}

# İlk yönetici e-postası (Plesk benzeri: mümkünse gerçek alan / iletişim adresi).
# Sıra: PANELZE_ADMIN_EMAIL > PANELSAR_… > PANELZE_ADMIN_EMAIL_DOMAIN > LETS_ENCRYPT_EMAIL >
#       APP_URL ana makinesi (IP/localhost değilse → admin@host) > admin@<hostname -f>
panelze_resolve_admin_email() {
  local explicit domain le app_url host fqdn
  explicit="${PANELZE_ADMIN_EMAIL:-${PANELSAR_ADMIN_EMAIL:-}}"
  explicit="${explicit//[[:space:]]/}"
  if [[ -n "$explicit" ]]; then
    echo "$explicit"
    return 0
  fi
  domain="${PANELZE_ADMIN_EMAIL_DOMAIN:-${PANELSAR_ADMIN_EMAIL_DOMAIN:-}}"
  domain="${domain//[[:space:]]/}"
  if [[ -n "$domain" && "$domain" == *.* && "$domain" != *"@"* ]]; then
    echo "admin@${domain}"
    return 0
  fi
  le="${LETS_ENCRYPT_EMAIL:-}"
  le="${le//[[:space:]]/}"
  if [[ -n "$le" && "$le" == *"@"* ]]; then
    local le_dom
    le_dom="${le##*@}"
    if [[ "$le_dom" == *.* ]] && [[ "$le_dom" != "localhost" ]] && [[ "$le_dom" != "localdomain" ]]; then
      echo "$le"
      return 0
    fi
  fi
  if [[ -n "$le" && "$le" == *"@"* ]]; then
    echo "Uyarı: LETS_ENCRYPT_EMAIL geçersiz/geliştirme değeri olduğu için admin e-posta türetiminde kullanılmadı: $le" >&2
  fi
  app_url="$(grep -E '^APP_URL=' "$ENV_FILE" 2>/dev/null | cut -d= -f2- | tr -d '\r')"
  app_url="${app_url#\"}"
  app_url="${app_url%\"}"
  app_url="${app_url//[[:space:]]/}"
  host="${app_url#*://}"
  host="${host%%/*}"
  host="${host%%\?*}"
  if [[ "$host" == \[*\]* ]]; then
    host=""
  elif [[ "$host" =~ ^([^:]+):[0-9]+$ ]]; then
    host="${BASH_REMATCH[1]}"
  fi
  if [[ -n "$host" && "$host" != "localhost" && "$host" != "127.0.0.1" ]]; then
    if [[ "$host" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
      :
    elif [[ "$host" == *:* ]]; then
      :
    else
      echo "admin@${host}"
      return 0
    fi
  fi
  fqdn="$(hostname -f 2>/dev/null || hostname || echo panelze.local)"
  fqdn="${fqdn// /}"
  echo "admin@${fqdn}"
}

update_env "APP_ENV" "production"
update_env "APP_DEBUG" "false"
update_env "APP_PROFILE" "$APP_PROFILE"
# Çok kiracılı vendor kontrol düzlemi bu kurulumda kullanılmaz; lisans/müşteri merkezi sitede.
update_env "VENDOR_ENABLED" "false"
update_env "ENFORCE_ADMIN_2FA" "$ENFORCE_ADMIN_2FA"
if [[ -n "${PANELZE_LICENSE_KEY:-}" ]]; then
  update_env "LICENSE_KEY" "$PANELZE_LICENSE_KEY"
fi
_PANEL_APP_URL="${PANELZE_APP_URL:-${PANEL_APP_URL:-}}"
if [[ -z "$_PANEL_APP_URL" && -n "${PANELZE_PUBLIC_HOST:-}" ]]; then
  _PANEL_APP_URL="http://${PANELZE_PUBLIC_HOST}"
fi
if [[ -z "$_PANEL_APP_URL" ]]; then
  _PANEL_APP_URL="http://$(hostname -I 2>/dev/null | awk '{print $1}' || echo localhost)"
fi
update_env "APP_URL" "$_PANEL_APP_URL"
update_env "ENGINE_API_URL" "http://127.0.0.1:9090"
update_env "ENGINE_INTERNAL_KEY" "$INTERNAL_KEY"
update_env "ENGINE_API_SECRET" "$ENGINE_JWT"
update_env "LOG_LEVEL" "error"
if [[ "${WITH_BIND_DNS:-1}" == "1" ]] || [[ "${WITH_BIND_DNS:-1}" == "yes" ]]; then
  update_env "PANELZE_DNS_BIND" "true"
  _PANELZE_SERVER_IP="$(hostname -I 2>/dev/null | awk '{print $1}' || true)"
  if [[ -n "${PANELZE_DNS_SERVER_IP:-}" ]]; then
    update_env "PANELZE_DNS_SERVER_IP" "${PANELZE_DNS_SERVER_IP}"
  elif [[ -n "$_PANELZE_SERVER_IP" ]]; then
    update_env "PANELZE_DNS_SERVER_IP" "$_PANELZE_SERVER_IP"
  fi
  if [[ -n "${PANELZE_DNS_NS1:-${PANELZE_DNS_NS1:-}}" ]]; then
    update_env "PANELZE_DNS_NS1" "${PANELZE_DNS_NS1:-${PANELZE_DNS_NS1}}"
  fi
  if [[ -n "${PANELZE_DNS_NS2:-${PANELZE_DNS_NS2:-}}" ]]; then
    update_env "PANELZE_DNS_NS2" "${PANELZE_DNS_NS2:-${PANELZE_DNS_NS2}}"
  fi
fi

# Yerel Postfix: Laravel sendmail ile gönderir; SMTP’yi panelden (Admin → Giden posta) tanımlarsınız
if [[ "${WITH_LOCAL_POSTFIX:-1}" == "1" ]] || [[ "${WITH_LOCAL_POSTFIX:-1}" == "yes" ]]; then
  _MAIL_FROM="noreply@$(hostname -f 2>/dev/null || hostname)"
  update_env "MAIL_MAILER" "sendmail"
  update_env "MAIL_FROM_ADDRESS" "\"${_MAIL_FROM}\""
  update_env "MAIL_FROM_NAME" "\"Panelze\""
fi

# phpMyAdmin (kuruluysa panelde otomatik link)
if [[ -d /usr/share/phpmyadmin ]]; then
  _APP_URL_VAL="$(grep '^APP_URL=' "$ENV_FILE" 2>/dev/null | cut -d= -f2- | tr -d '\r' | tr -d ' ')"
  [[ -n "$_APP_URL_VAL" ]] && update_env "PHPMYADMIN_URL" "${_APP_URL_VAL%/}/phpmyadmin"
fi

# MariaDB panel DB + provision kullanıcıları (.env, secret, @localhost + @127.0.0.1)
if [[ "${WITH_MARIADB}" == "1" ]] || [[ "${WITH_MARIADB}" == "yes" ]]; then
  bash "$DEPLOY_SCRIPTS/repair-mysql-users.sh"
  echo "Panel MySQL şifresi: /root/panelze-panel-mysql.secret"
  echo "MySQL provision şifresi: /root/panelze-mysql-provision.secret"
fi

# Composer www-data ile çalışır; panel/ yalnızca storage/cache www-data ise vendor/ oluşturulamaz
mkdir -p "$PANEL_ROOT/vendor"
chown -R www-data:www-data "$PANEL_ROOT"
chmod -R ug+rwx "$PANEL_ROOT/storage" "$PANEL_ROOT/bootstrap/cache"

panelze_git_safe_directory "$REPO_ROOT"

sudo -u www-data composer --working-dir="$PANEL_ROOT" install --no-dev --optimize-autoloader --no-interaction

if ! grep -qE '^APP_KEY=base64:.+' "$ENV_FILE" 2>/dev/null; then
  echo "==> Laravel APP_KEY üretiliyor (.env)…"
  panelze_run_artisan key:generate --force --no-interaction || {
    echo "Hata: php artisan key:generate başarısız; .env veya composer kurulumunu kontrol edin." >&2
    exit 1
  }
fi

if grep -q '^DB_CONNECTION=sqlite' "$ENV_FILE" 2>/dev/null; then
  install -d -o www-data -g www-data -m 775 "$PANEL_ROOT/database"
  if [[ ! -f "$PANEL_ROOT/database/database.sqlite" ]]; then
    sudo -u www-data touch "$PANEL_ROOT/database/database.sqlite"
  fi
fi

# Frontend → public/ (sessiz atlama yok: panel arayüzü dist olmadan çalışmaz)
FRONTEND_ROOT="$REPO_ROOT/frontend"
if [[ -f "$FRONTEND_ROOT/package.json" ]]; then
  if ! command -v npm >/dev/null 2>&1; then
    echo "Hata: frontend/ için npm gerekli. SKIP_APT=1 kullandıysanız önce Node.js + npm kurun." >&2
    exit 1
  fi
  if [[ -f "$FRONTEND_ROOT/package-lock.json" ]]; then
    (cd "$FRONTEND_ROOT" && npm ci && VITE_BASE_URL="${VITE_BASE_URL:-}" VITE_APP_PROFILE="$APP_PROFILE" npm run build)
  else
    (cd "$FRONTEND_ROOT" && npm install && VITE_BASE_URL="${VITE_BASE_URL:-}" VITE_APP_PROFILE="$APP_PROFILE" npm run build)
  fi
  rsync -a --delete \
    --exclude index.php \
    --exclude .htaccess \
    "$FRONTEND_ROOT/dist/" "$PANEL_ROOT/public/"
  # Nginx’te panel `location /admin/ { alias .../public/; }` ise derlemede: VITE_BASE_URL=/admin/ (aksi halde /admin/assets/*.js 404).
fi

if [[ "${RESET_PANEL_DB:-0}" == "1" ]] || [[ "${RESET_PANEL_DB:-0}" == "yes" ]]; then
  echo "==> RESET_PANEL_DB=1: Panel veritabanı sıfırlanıyor (migrate:fresh)."
  panelze_run_artisan migrate:fresh --force
else
  echo "==> Panel veritabanı korunuyor: migrate --force (yeniden kurulum / güncelleme; kullanıcı ve site kayıtları silinmez)."
  panelze_run_artisan migrate --force
fi
panelze_run_artisan panelze:init-outbound-mail --no-interaction 2>/dev/null || panelze_run_artisan panelze:init-outbound-mail --no-interaction 2>/dev/null || true
panelze_run_artisan panelze:repair-stack-installs --no-interaction 2>/dev/null || true
if [[ "${WITH_MAIL_STACK_WEBMAIL:-1}" == "1" ]] || [[ "${WITH_MAIL_STACK_WEBMAIL:-1}" == "yes" ]]; then
  panelze_run_artisan panelze:ensure-mail-stack --no-interaction 2>/dev/null || true
fi

if [[ "${SKIP_DB_SEED:-}" != "1" ]]; then
  RESET_DB_MODE=0
  if [[ "${RESET_PANEL_DB:-0}" == "1" ]] || [[ "${RESET_PANEL_DB:-0}" == "yes" ]]; then
    RESET_DB_MODE=1
  fi

  HOST_FQDN="$(hostname -f 2>/dev/null || hostname || echo panelze.local)"
  HOST_FQDN="${HOST_FQDN// /}"
  ADMIN_EMAIL="$(panelze_resolve_admin_email)"
  SEED_DEMO_USERS="${PANELZE_SEED_DEMO_USERS:-${PANELSAR_SEED_DEMO_USERS:-0}}"
  USER_COUNT=""
  if [[ "$RESET_DB_MODE" == "0" ]] && { [[ "${WITH_MARIADB}" == "1" ]] || [[ "${WITH_MARIADB}" == "yes" ]]; }; then
    DB_PW=$(grep '^DB_PASSWORD=' "$ENV_FILE" | cut -d= -f2- | tr -d '\r')
    MARIADB_CMD=(mariadb)
    command -v mariadb >/dev/null 2>&1 || MARIADB_CMD=(mysql)
    if [[ -n "$DB_PW" ]]; then
      DB_USER_Q="$(grep '^DB_USERNAME=' "$ENV_FILE" 2>/dev/null | cut -d= -f2- | tr -d '\r' | tr -d ' ')"
      DB_NAME_Q="$(grep '^DB_DATABASE=' "$ENV_FILE" 2>/dev/null | cut -d= -f2- | tr -d '\r' | tr -d ' ')"
      [[ -n "$DB_USER_Q" ]] || DB_USER_Q="panelze"
      [[ -n "$DB_NAME_Q" ]] || DB_NAME_Q="panelze"
      USER_COUNT=$(MYSQL_PWD="$DB_PW" "${MARIADB_CMD[@]}" -u "$DB_USER_Q" -h 127.0.0.1 "$DB_NAME_Q" -Nse "SELECT COUNT(*)" 2>/dev/null || echo "")
    fi
  elif [[ "$RESET_DB_MODE" == "0" ]] && grep -q '^DB_CONNECTION=sqlite' "$ENV_FILE" 2>/dev/null && [[ -f "$PANEL_ROOT/database/database.sqlite" ]]; then
    USER_COUNT=$(sqlite3 "$PANEL_ROOT/database/database.sqlite" "SELECT COUNT(*) FROM users;" 2>/dev/null || echo "")
  fi
  [[ -n "$USER_COUNT" ]] || USER_COUNT=0

  # Varsayılan: her kurulum/güncellemede yeni şifre (müşteri öncekini unuttuğunda sorun olmasın). Sabit için PANELZE_ADMIN_PASSWORD=...
  # Otomasyon: PANELZE_PRESERVE_ADMIN_PASSWORD=1 → mevcut kullanıcı varken şifre dokunulmaz / dosyada gösterilmez.
  ADMIN_PASSWORD=""
  if [[ -n "${PANELZE_ADMIN_PASSWORD:-}" ]]; then
    ADMIN_PASSWORD="$PANELZE_ADMIN_PASSWORD"
  elif [[ "${PANELZE_PRESERVE_ADMIN_PASSWORD:-0}" == "1" ]] || [[ "${PANELZE_PRESERVE_ADMIN_PASSWORD:-0}" == "yes" ]]; then
    if [[ "$RESET_DB_MODE" == "1" ]]; then
      ADMIN_PASSWORD="$(openssl rand -hex 12)"
    elif [[ "$USER_COUNT" == "0" ]]; then
      ADMIN_PASSWORD="$(openssl rand -hex 12)"
    fi
  else
    ADMIN_PASSWORD="$(openssl rand -hex 12)"
  fi

  LOGIN_FILE="/root/panelze-admin-login.txt"
  PANEL_URL_HINT="$(grep -E '^APP_URL=' "$ENV_FILE" 2>/dev/null | cut -d= -f2- | tr -d '\r' || true)"
  [[ -n "$PANEL_URL_HINT" ]] || PANEL_URL_HINT="http://$(hostname -I 2>/dev/null | awk '{print $1}' || echo localhost)"

  {
    echo "Panelze — panel giriş bilgisi ($(date -u +%Y-%m-%dT%H:%MZ 2>/dev/null || date))"
    echo "Panel URL: ${PANEL_URL_HINT}"
    echo "E-posta:   ${ADMIN_EMAIL}"
    if [[ -n "$ADMIN_PASSWORD" ]]; then
      echo "Şifre:     ${ADMIN_PASSWORD}"
      echo "İlk girişten sonra şifreyi değiştirin."
    else
      echo "Şifre:     (korundu — PANELZE_PRESERVE_ADMIN_PASSWORD=1; mevcut admin şifresi değişmedi)"
      echo "Not: Şifreyi bilmiyorsanız panelden sıfırlayın veya bir kez PANELZE_PRESERVE_ADMIN_PASSWORD vermeden kurulumu çalıştırın."
    fi
  } > "$LOGIN_FILE"
  chmod 600 "$LOGIN_FILE"

  SHOW_CREDS_IN_TERMINAL="${PANELZE_SHOW_ADMIN_CREDENTIALS:-1}"
  if [[ "$SHOW_CREDS_IN_TERMINAL" == "1" ]] || [[ "$SHOW_CREDS_IN_TERMINAL" == "yes" ]]; then
    echo ""
    echo "################################################################"
    echo "#  PANEL GİRİŞ BİLGİSİ (ilk kurulum)"
    echo "################################################################"
    echo "  URL:     ${PANEL_URL_HINT}"
    echo "  E-posta: ${ADMIN_EMAIL}"
    if [[ -n "$ADMIN_PASSWORD" ]]; then
      echo "  Şifre:   ${ADMIN_PASSWORD}"
    else
      echo "  Şifre:   (korundu; mevcut şifre değişmedi)"
    fi
    echo "  Not: İlk girişten sonra şifreyi değiştirin."
    echo "################################################################"
    echo ""
  fi

  if [[ -n "$ADMIN_PASSWORD" ]]; then
    sudo -u www-data env \
      HOME="$(panelze_www_data_home "$PANEL_ROOT")" \
      XDG_CONFIG_HOME="$(panelze_www_data_home "$PANEL_ROOT")/storage/framework/.config" \
      PANELZE_ADMIN_EMAIL="$ADMIN_EMAIL" \
      PANELZE_ADMIN_PASSWORD="$ADMIN_PASSWORD" \
      PANELZE_SEED_DEMO_USERS="$SEED_DEMO_USERS" \
      php "$PANEL_ROOT/artisan" db:seed --force
  else
    sudo -u www-data env \
      HOME="$(panelze_www_data_home "$PANEL_ROOT")" \
      XDG_CONFIG_HOME="$(panelze_www_data_home "$PANEL_ROOT")/storage/framework/.config" \
      PANELZE_ADMIN_EMAIL="$ADMIN_EMAIL" \
      PANELZE_SEED_DEMO_USERS="$SEED_DEMO_USERS" \
      php "$PANEL_ROOT/artisan" db:seed --force
  fi
fi

panelze_run_artisan config:cache
panelze_run_artisan route:cache
panelze_run_artisan view:cache
panelze_run_artisan panelze:ensure-system-cron || true

# BIND9 yetkili DNS — panel dns_records → named (kurulumda varsayılan açık)
if [[ "${WITH_BIND_DNS:-1}" == "1" ]] || [[ "${WITH_BIND_DNS:-1}" == "yes" ]]; then
  _BIND_SETUP="$REPO_ROOT/deploy/host/panelze-bind-setup.sh"
  if command -v named >/dev/null 2>&1 && { systemctl is-active named >/dev/null 2>&1 || systemctl is-active bind9 >/dev/null 2>&1; }; then
    echo "==> BIND9 DNS senkronu (named zaten kurulu)"
    if [[ -x /usr/local/sbin/panelze-bind-sync ]]; then
      /usr/local/sbin/panelze-bind-sync || echo "UYARI: BIND sync başarısız — php artisan panelze:sync-bind-dns" >&2
    fi
  elif [[ -f "$_BIND_SETUP" ]] && [[ "${SKIP_APT:-}" != "1" ]]; then
    echo "==> BIND9 yetkili DNS kurulumu (panel DNS kayıtları canlı yayın)"
    PANELZE_HOME="$PANELZE_HOME" PANEL_ROOT="$PANEL_ROOT" bash "$_BIND_SETUP" \
      || echo "UYARI: BIND9 kurulumu başarısız — kurulum sonrası: sudo bash $_BIND_SETUP" >&2
  elif [[ -f "$_BIND_SETUP" ]]; then
    echo "Uyarı: SKIP_APT=1 — BIND9 paketi kurulmadı. DNS için: sudo bash $_BIND_SETUP" >&2
  fi
fi

# OS-level scheduler: Laravel schedule:run her dakika tetiklensin.
rm -f /etc/cron.d/panelsar-panel-scheduler 2>/dev/null || true
cat > /etc/cron.d/panelze-panel-scheduler <<EOF
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
* * * * * www-data cd "$PANEL_ROOT" && env HOME="$PANEL_ROOT" XDG_CONFIG_HOME="$PANEL_ROOT/storage/framework/.config" /usr/bin/php artisan schedule:run >> /dev/null 2>&1
EOF
chmod 644 /etc/cron.d/panelze-panel-scheduler
systemctl enable --now cron 2>/dev/null || systemctl enable --now crond 2>/dev/null || true

# Geçici .tmp_* dizinleri (yarım unzip/copy): günlük temizlik
rm -f /etc/cron.d/panelsar-cleaner 2>/dev/null || true
if [[ -x /usr/local/sbin/panelze-cleaner ]]; then
  PANELZE_CLEANER_WEB_ROOT="${PANELZE_HOSTING_WEB_ROOT:-${PANELZE_HOME}/data/www}"
  cat > /etc/cron.d/panelze-cleaner <<CRON
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# Her gün 04:17 — 2 saatten eski .tmp_* ( /tmp + web_root )
17 4 * * * root PANELZE_HOSTING_WEB_ROOT=${PANELZE_CLEANER_WEB_ROOT} /usr/local/sbin/panelze-cleaner 2>&1 | logger -t panelze-cleaner
CRON
  chmod 644 /etc/cron.d/panelze-cleaner
fi

# Queue worker: uzun süren işleri request dışına alır (installer/deploy/stack vb.).
systemctl disable --now panelsar-panel-queue.service 2>/dev/null || true
cat > /etc/systemd/system/panelze-panel-queue.service <<EOF
[Unit]
Description=Panelze Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=$PANEL_ROOT
Environment=HOME=$PANEL_ROOT
Environment=XDG_CONFIG_HOME=$PANEL_ROOT/storage/framework/.config
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=1 --timeout=1900
Restart=always
RestartSec=5
KillSignal=SIGTERM
TimeoutStopSec=30

[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload
systemctl enable --now panelze-panel-queue.service

# Nginx — eski panelsar.conf site dosyası default_server ile çakışmasın (duplicate default server hatası)
rm -f /etc/nginx/sites-enabled/panelsar.conf /etc/nginx/sites-enabled/panelsar 2>/dev/null || true
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true

# Acik alan adi: nginx server_name + (asagida) otomatik Let's Encrypt
PANELZE_EFFECTIVE_PUBLIC_HOST=""
if [[ -n "${PANELZE_PUBLIC_HOST:-}" ]]; then
  PANELZE_EFFECTIVE_PUBLIC_HOST="${PANELZE_PUBLIC_HOST}"
elif [[ -n "${PANELZE_APP_URL:-}" ]]; then
  PANELZE_EFFECTIVE_PUBLIC_HOST="$(panelze_url_hostname "${PANELZE_APP_URL}" || true)"
fi
if [[ -n "$PANELZE_EFFECTIVE_PUBLIC_HOST" ]]; then
  SERVER_NAME="$PANELZE_EFFECTIVE_PUBLIC_HOST"
fi

# Nginx
NGX_DST="/etc/nginx/sites-available/panelze.conf"
sed \
  -e "s|__SERVER_NAME__|$SERVER_NAME|g" \
  -e "s|__PANEL_PUBLIC__|$PANEL_ROOT/public|g" \
  -e "s|__PHP_FPM_SOCK__|$PHP_FPM_SOCK|g" \
  "$REPO_ROOT/deploy/nginx/panelze.conf" > "$NGX_DST"

if [[ "$SERVER_NAME" == "_" ]]; then
  sed -i 's/listen 80;/listen 80 default_server;/' "$NGX_DST" || true
  sed -i 's/listen \[::\]:80;/listen [::]:80 default_server;/' "$NGX_DST" || true
fi

ln -sf "$NGX_DST" /etc/nginx/sites-enabled/panelze.conf
nginx -t
systemctl reload nginx
systemctl enable nginx

# UFW
if [[ "${SKIP_UFW:-}" != "1" ]] && command -v ufw >/dev/null 2>&1; then
  ufw allow OpenSSH >/dev/null 2>&1 || ufw allow 22/tcp
  ufw allow 'Nginx Full' >/dev/null 2>&1 || { ufw allow 80/tcp; ufw allow 443/tcp; }
  if [[ "${WITH_APACHE:-1}" == "1" ]] || [[ "${WITH_APACHE:-1}" == "yes" ]]; then
    ufw allow 8080/tcp comment 'Apache backend' >/dev/null 2>&1 || true
  fi
  # OLS backend yalnızca loopback/nginx proxy — dışarıya açmayın (8088 UFW'de yok)
  if [[ "${WITH_BIND_DNS:-1}" == "1" ]] || [[ "${WITH_BIND_DNS:-1}" == "yes" ]]; then
    ufw allow 53/tcp comment 'BIND DNS' >/dev/null 2>&1 || ufw allow 53/tcp
    ufw allow 53/udp comment 'BIND DNS' >/dev/null 2>&1 || ufw allow 53/udp
  fi
  ufw --force enable || true
fi

# --- Sonlandirma: PHPMYADMIN_URL senkronu, Let's Encrypt (alan adi verildiyse), Laravel onbellek + saglik kontrolu ---
PANELZE_RUN_CERTBOT="${PANELZE_RUN_CERTBOT:-1}"
refresh_phpmysql_url_in_env() {
  if [[ ! -f "$ENV_FILE" ]]; then
    return 0
  fi
  if [[ ! -d /usr/share/phpmyadmin ]]; then
    return 0
  fi
  local _au
  _au="$(grep -E '^APP_URL=' "$ENV_FILE" 2>/dev/null | cut -d= -f2- | tr -d '\r')"
  _au="${_au#\"}"
  _au="${_au%\"}"
  _au="${_au//[[:space:]]/}"
  [[ -n "$_au" ]] || return 0
  update_env "PHPMYADMIN_URL" "${_au%/}/phpmyadmin"
}

panelze_is_ip_address() {
  [[ "${1:-}" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]
}

run_certbot_if_configured() {
  if [[ "${PANELZE_RUN_CERTBOT:-1}" != "1" ]] && [[ "${PANELZE_RUN_CERTBOT:-1}" != "yes" ]]; then
    return 0
  fi
  if ! command -v certbot >/dev/null 2>&1; then
    echo "==> SSL: certbot yok (WITH_CERTBOT=1 ile kurulur); HTTP ile devam."
    return 0
  fi
  [[ -n "${PANELZE_EFFECTIVE_PUBLIC_HOST:-}" ]] || return 0
  if panelze_is_ip_address "${PANELZE_EFFECTIVE_PUBLIC_HOST}"; then
    echo "==> SSL: IP adresi için otomatik Let's Encrypt atlanıyor; panel HTTP ile kalır."
    return 0
  fi

  local dom="$PANELZE_EFFECTIVE_PUBLIC_HOST"
  local em="${LETS_ENCRYPT_EMAIL:-}"
  [[ "$em" == *"@"* ]] || em="admin@${dom}"

  echo "==> Let's Encrypt deneniyor: ${dom} (DNS bu sunucuyu gostermeli, 80/tcp acik)"
  if certbot --nginx -d "$dom" --email "$em" --agree-tos --non-interactive --redirect --no-eff-email; then
    update_env "APP_URL" "https://${dom}"
    refresh_phpmysql_url_in_env
    nginx -t && systemctl reload nginx
    echo "==> SSL tamam: https://${dom}"
  else
    echo "==> Let's Encrypt tamamlanamadi (DNS veya 80 kapali / rate limit). Panel HTTP ile calisir."
    echo "    DNS hazir oldugunda: ayni betigi tekrar calistirin veya: certbot --nginx -d ${dom} --email ${em} --agree-tos --non-interactive --redirect"
    echo "==> Nginx panel HTTP şablonu yeniden yaziliyor (yarim HTTPS yonlendirmesi kalmasin)"
    sed \
      -e "s|__SERVER_NAME__|${SERVER_NAME}|g" \
      -e "s|__PANEL_PUBLIC__|$PANEL_ROOT/public|g" \
      -e "s|__PHP_FPM_SOCK__|$PHP_FPM_SOCK|g" \
      "$REPO_ROOT/deploy/nginx/panelze.conf" > "$NGX_DST"
    if [[ "$SERVER_NAME" == "_" ]]; then
      sed -i 's/listen 80;/listen 80 default_server;/' "$NGX_DST" || true
      sed -i 's/listen \[::\]:80;/listen [::]:80 default_server;/' "$NGX_DST" || true
    fi
    ln -sf "$NGX_DST" /etc/nginx/sites-enabled/panelze.conf
    nginx -t && systemctl reload nginx
  fi
}

refresh_phpmysql_url_in_env
run_certbot_if_configured

if [[ -x /usr/local/sbin/panelze-security ]]; then
  echo "==> Security helper self-test"
  if ! /usr/local/sbin/panelze-security self-test; then
    echo "Hata: panelze-security self-test basarisiz. Geri alma icin son iyi yapiyi tekrar calistirin." >&2
    exit 1
  fi
  echo "==> Varsayilan guvenlik: guvenlik duvari, Fail2ban, ModSecurity (WAF)"
  /usr/local/sbin/panelze-security security-bootstrap-defaults || echo "Uyari: security-bootstrap-defaults tamamlanamadi (manuel: panelze-security security-bootstrap-defaults)"
fi

echo "==> Laravel onbellek + kurulum kontrolu (musterinin manuel komut calistirmasi gerekmez)"
if [[ "${PANELZE_UPDATE_ONLY:-0}" == "1" ]]; then
  export PANELZE_QUICK_PERM_FIX=1
fi
bash "$DEPLOY_SCRIPTS/fix-hosting-permissions.sh"
panelze_run_artisan config:cache
panelze_run_artisan route:cache
panelze_run_artisan view:cache || true
if panelze_run_artisan panelze:install-check --ping; then
  echo "==> panelze:install-check: tamam"
else
  echo "==> panelze:install-check: uyari — yukaridaki ciktiyi inceleyin (kurulum tamamlandi)."
fi

echo ""
echo "=== Panelze kurulum özeti ==="
echo "  Panel kökü:     $PANELZE_HOME"
if [[ "${SKIP_DB_SEED:-}" != "1" ]] && [[ -n "${ADMIN_EMAIL:-}" ]]; then
  case "$ADMIN_EMAIL" in
    admin@*contaboserver*|admin@vmi*)
      echo "  İpucu: İlk admin e-postası sunucu FQDN. Üretimde: PANELZE_ADMIN_EMAIL=... veya PANELZE_APP_URL=https://panel.alanadin.com"
      ;;
  esac
fi
echo "  Engine API:     http://127.0.0.1:9090 (yalnızca sunucu içi — dışarıya açmayın)"
echo "  ENGINE_INTERNAL_KEY panel .env ile eşleşiyor."
echo "  Nginx site:     $NGX_DST"
if [[ "${RESET_PANEL_DB:-0}" == "1" ]] || [[ "${RESET_PANEL_DB:-0}" == "yes" ]]; then
  echo "  Fresh mode:     ON (RESET_PANEL_DB=1)"
fi
if [[ "${WITH_APACHE:-1}" == "1" ]] || [[ "${WITH_APACHE:-1}" == "yes" ]]; then
  echo "  Apache backend: :8080 (nginx edge 80/443 — panelden Apache seçilen siteler standart URL ile açılır)"
fi
if [[ "${WITH_OPENLITESPEED:-1}" == "1" ]] || [[ "${WITH_OPENLITESPEED:-1}" == "yes" ]]; then
  echo "  OpenLiteSpeed:  :8088 backend (nginx edge 80/443)"
fi
if [[ "${WITH_BIND_DNS:-1}" == "1" ]] || [[ "${WITH_BIND_DNS:-1}" == "yes" ]]; then
  if systemctl is-active named >/dev/null 2>&1 || systemctl is-active bind9 >/dev/null 2>&1; then
    echo "  BIND9 DNS:      aktif (panel DNS → named; port 53)"
  else
    echo "  BIND9 DNS:      kurulmadı veya pasif — sudo bash deploy/host/panelze-bind-setup.sh"
  fi
fi
echo ""
if [[ "${SKIP_DB_SEED:-}" != "1" ]]; then
  echo "################################################################"
  echo "#  PANEL GİRİŞİ"
  echo "################################################################"
  if [[ -f /root/panelze-admin-login.txt ]]; then
    sed 's/^/#  /' /root/panelze-admin-login.txt
    echo "#"
    echo "#  İlk girişten sonra şifreyi değiştirin; dosyayı silin veya:"
    echo "#    sudo shred -u /root/panelze-admin-login.txt 2>/dev/null || sudo rm -f /root/panelze-admin-login.txt"
  elif [[ -f /root/panelsar-admin-login.txt ]]; then
    echo "#  UYARI: Eski panelsar-admin-login.txt — güvenilir olmayabilir."
    echo "#    sudo cat /root/panelsar-admin-login.txt"
  else
    echo "#  Giriş dosyası yok. Bilinen admin ile girin veya şifre sıfırlayın."
  fi
  echo "################################################################"
  echo ""
fi
echo "Sonraki adımlar:"
if [[ "${WITH_BIND_DNS:-1}" == "1" ]] || [[ "${WITH_BIND_DNS:-1}" == "yes" ]]; then
  _BIND_IP="$(grep '^PANELZE_DNS_SERVER_IP=' "$ENV_FILE" 2>/dev/null | cut -d= -f2- | tr -d '\r' || hostname -I 2>/dev/null | awk '{print $1}')"
  _BIND_NS="$(hostname -f 2>/dev/null || hostname)"
  echo "  1) Müşteri siteleri için DNS: registrar'da nameserver'ları bu sunucuya yönlendirin"
  echo "     (glue: ns1/ns2 → ${_BIND_IP:-sunucu-IP}). Panel DNS sayfasındaki kayıtlar BIND9 ile yayınlanır."
  echo "  2) Panel alan adı için A kaydı: bu sunucunun IP'sine yönlendirin."
else
  echo "  1) Alan adınızı bu sunucunun IP adresine yönlendirin (A kaydı). Sağlayıcı panelinden yapılır."
fi
if [[ -n "${PANELZE_EFFECTIVE_PUBLIC_HOST:-}" ]]; then
  echo "     Bu kurulumda panel alan adı: ${PANELZE_EFFECTIVE_PUBLIC_HOST} — DNS yayıldıktan sonra SSL için betiği tekrar çalıştırabilir veya certbot çıktısındaki komutu kullanabilirsiniz."
else
  echo "     Ücretsiz SSL ve dogru APP_URL icin yeniden kurulum/guncellemede verin:"
  echo "       PANELZE_PUBLIC_HOST=panel.ornek.com LETS_ENCRYPT_EMAIL=size@ornek.com sudo -E bash deploy/bootstrap/install-production.sh"
  echo "     (veya PANELZE_APP_URL=https://panel.ornek.com — FQDN otomatik algilanir.)"
fi
echo "  3) Otomatik yapildi: MySQL, izinler, BIND9 (WITH_BIND_DNS), mail stack, APP_URL, panelze:install-check."
echo "     Sorun olursa (1045, permission denied): sudo panelze-post-install"
echo ""
