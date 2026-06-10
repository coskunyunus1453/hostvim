#!/usr/bin/env bash
#
# Panelze — müşteri sunucusunda çalışır (root).
#
# SİZ (Kodsar): Bu dosyayı HTTPS ile yayınlayın, aşağıdaki varsayılan repo URL’ini kendi Git adresinizle değiştirin.
# Örnek konum: https://kodsar.com/panel/install.sh
#
# Markdown listesinden kopyalarken satır başındaki "* " veya "• " İŞARETİNİ SİLİN;
# aksi halde kabuk * ile mevcut dizindeki dosya adlarını genişletir (ör. go, panelze-admin-login.txt)
# ve komut "go panelze-admin-login.txt …" gibi patlar. Güvenli: cd /tmp && curl … | bash
#
# Müşteri kurulumu (önerilen — tek komut, freemium + Pro aynı paket):
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-hostvim.sh" | bash
# İsteğe bağlı lisans: PANELZE_LICENSE_KEY="hv_..." curl … | bash
# Geriye dönük: install-community.sh / install-pro.sh → install-hostvim.sh yönlendirir.
# Bu dosya (install.sh) ortak motor; doğrudan çağrılırsa APP_PROFILE varsayılanı customer’dır.
#
# Müşteri komutu (Linux VPS — SSL doğrulaması AÇIK):
#   • Tek satır (kısa domain): curl -fsSL https://get.panelze.sh | bash
#   • Root SSH: curl -fsSL "…/install-hostvim.sh" | bash
#   • Eski adlar (yönlendirme): install-customer.sh → community, install-vendor.sh → pro
#   • İlk admin: /root/panelze-admin-login.txt
#   macOS/Windows’ta çalıştırmayın; boş Debian/Ubuntu sunucuda çalışır.
#
# Ortam ile (ör. özel branch):
#   sudo PANELZE_BRANCH=release PANELZE_REPO_URL=https://github.com/kodsar/hostvim.git bash -s <<< "$(curl -fsSL https://kodsar.com/panel/install.sh)"
#   (Eski: PANELSAR_BRANCH / PANELSAR_REPO_URL hâlâ okunur.)
#
# Plesk / cPanel benzeri izolasyon (varsayılan):
#   Mevcut kurulum algılanırsa otomatik GÜNCELLEME modu (panel DB + data/www + MySQL korunur).
#   Güvenli güncelleme betiği (önerilen, tekrar kurulumda):
#     curl -fsSL "…/install-update-community.sh" | sudo bash
# Tam sıfırlama (migrate:fresh + hosting temizliği) ancak bilinçli seçilirse:
#   PANELZE_FRESH_INSTALL=1 curl -fsSL "URL" | sudo bash
#   veya RESET_PANEL_DB=1 curl -fsSL "URL" | bash
#
# DNS (varsayılan): BIND9 kurulur; panelde eklenen kayıtlar sunucuda yayınlanır (WITH_BIND_DNS=0 ile kapatılır).
#
# Diğer varsayılanlar:
#   PANELZE_SEED_DEMO_USERS=0 — demo kullanıcı seed etme (eski: PANELSAR_SEED_DEMO_USERS)
#   İlk kurulumda kullanıcı yoksa db:seed admin üretir; PANELZE_ADMIN_PASSWORD verilmezse rastgele şifre
#   Üretim önerisi: PANELZE_ADMIN_EMAIL=yonetici@alanadin.com ve/veya PANELZE_APP_URL=https://panel.alanadin.com
#   (verilmezse sırayla LETS_ENCRYPT_EMAIL, APP_URL ana makinesi, son çare admin@sunucu-FQDN kullanılır)
#   Her çalıştırmada yeni admin şifresi üretilir ( /root/panelze-admin-login.txt ). Şifreyi elle sabitlemek: PANELZE_ADMIN_PASSWORD=...
#   Sadece kod güncellemesi (şifre dokunulmasın): PANELZE_PRESERVE_ADMIN_PASSWORD=1
#
# Zorunlu proxy/kırık sertifika (ÖNERİLMEZ): yalnızca geçici tanı veya iç ağda:
#   PANELZE_INSECURE_DOWNLOAD=1 curl -fsSL ...  → betik içinde curl -k kullanılır (eski: PANELSAR_INSECURE_DOWNLOAD)
#   Müşteri dosyayı önce indirip: curl -k -O ... && sudo bash install.sh
#
set -euo pipefail

# ─── Dağıtımcı: repo URL + bu betiğin ham (raw) HTTPS adresi aynı depoyu göstermeli (sudo yeniden çalıştırma için) ───
# Varsayılan repo adı hostvim; GitHub’da hâlâ panelsar ise PANELZE_REPO_URL ile ezin.
PANELZE_INSTALL_SCRIPT_URL="${PANELZE_INSTALL_SCRIPT_URL:-${PANELSAR_INSTALL_SCRIPT_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install.sh}}"
PANELZE_REPO_URL="${PANELZE_REPO_URL:-${PANELSAR_REPO_URL:-https://github.com/coskunyunus1453/hostvim.git}}"
PANELZE_BRANCH="${PANELZE_BRANCH:-${PANELSAR_BRANCH:-main}}"
PANELZE_HOME="${PANELZE_HOME:-${PANELSAR_HOME:-/var/www/panelze}}"
PANELZE_SEED_DEMO_USERS="${PANELZE_SEED_DEMO_USERS:-${PANELSAR_SEED_DEMO_USERS:-0}}"
PANELZE_INSECURE_DOWNLOAD="${PANELZE_INSECURE_DOWNLOAD:-${PANELSAR_INSECURE_DOWNLOAD:-0}}"
export PANELSAR_HOME="$PANELZE_HOME"
export PANELSAR_REPO_URL="$PANELZE_REPO_URL"
export PANELSAR_BRANCH="$PANELZE_BRANCH"
export PANELSAR_INSTALL_SCRIPT_URL="$PANELZE_INSTALL_SCRIPT_URL"
export PANELSAR_SEED_DEMO_USERS="$PANELZE_SEED_DEMO_USERS"
export PANELZE_SEED_DEMO_USERS="$PANELZE_SEED_DEMO_USERS"

if ! declare -F hostvim_source_install_mode_lib &>/dev/null; then
  for _lib_boot in \
    "$(dirname "${BASH_SOURCE[0]:-$0}")/lib/source-install-mode.sh" \
    "/var/www/panelze/deploy/host/lib/source-install-mode.sh" \
    "${PANELZE_HOME:-/var/www/panelze}/deploy/host/lib/source-install-mode.sh"; do
    if [[ -f "$_lib_boot" ]]; then
      # shellcheck source=lib/source-install-mode.sh
      source "$_lib_boot"
      break
    fi
  done
fi
if ! declare -F hostvim_source_install_mode_lib &>/dev/null; then
  _raw_boot="${PANELZE_RAW_BASE:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/${PANELZE_BRANCH:-main}}"
  _tmp_boot="$(mktemp)"
  curl -fsSL "${_raw_boot}/deploy/host/lib/source-install-mode.sh" -o "$_tmp_boot"
  # shellcheck source=/dev/null
  source "$_tmp_boot"
  rm -f "$_tmp_boot"
fi
hostvim_source_install_mode_lib

PANELZE_INSTALL_MODE="$(hostvim_resolve_install_mode)"
if [[ "$PANELZE_INSTALL_MODE" == "update" ]]; then
  hostvim_apply_update_safe_env
else
  hostvim_apply_fresh_env
fi
export RESET_PANEL_DB PANELZE_UPDATE_ONLY PANELZE_FRESH_INSTALL CLEAN_HOSTING_STATE_ON_RESET
export PANELZE_PRESERVE_ADMIN_PASSWORD SKIP_APT
export PANELZE_SEED_DEMO_USERS

hostvim_print_install_mode_banner "$PANELZE_INSTALL_MODE"

if [[ "$(uname -s)" != "Linux" ]]; then
  echo "Panelze kurulumu yalnızca Linux (Debian/Ubuntu) sunucu içindir." >&2
  echo "macOS veya yerel bilgisayarınızda değil; boş VPS'e SSH ile bağlanıp orada çalıştırın." >&2
  echo "Örnek: ssh root@SUNUCU_IP  ardından: curl -fsSL \"$PANELZE_INSTALL_SCRIPT_URL\" | bash" >&2
  exit 1
fi

if [[ "$(id -u)" -ne 0 ]]; then
  if command -v sudo >/dev/null 2>&1; then
    TMP="$(mktemp)"
    trap 'rm -f "$TMP"' EXIT
    if command -v curl >/dev/null 2>&1; then
      if [[ "$PANELZE_INSECURE_DOWNLOAD" == "1" ]]; then
        curl -fsSLk "$PANELZE_INSTALL_SCRIPT_URL" -o "$TMP"
      else
        curl -fsSL "$PANELZE_INSTALL_SCRIPT_URL" -o "$TMP"
      fi
    elif command -v wget >/dev/null 2>&1; then
      if [[ "$PANELZE_INSECURE_DOWNLOAD" == "1" ]]; then
        wget -qO "$TMP" "$PANELZE_INSTALL_SCRIPT_URL" --no-check-certificate
      else
        wget -qO "$TMP" "$PANELZE_INSTALL_SCRIPT_URL"
      fi
    else
      echo "Root gerekli veya curl/wget ile betik indirilemiyor. Örnek: curl -fsSL ... | sudo bash" >&2
      exit 1
    fi
    echo "Yönetici yetkisi gerekli; sudo bir kez parola sorabilir (root SSH kullanırsanız sorulmaz)." >&2
    exec sudo -E bash "$TMP"
  fi
  echo "Root veya sudo ile çalıştırın. Örnek: curl -fsSL \"$PANELZE_INSTALL_SCRIPT_URL\" | sudo bash" >&2
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq git ca-certificates curl
# Go: deploy/bootstrap/install-production.sh içinde engine/go.mod ile uyumlu sürüm (go.dev) kurulur; apt golang-go kullanılmaz.

PARENT="$(dirname "$PANELZE_HOME")"
mkdir -p "$PARENT"

if [[ -d "$PANELZE_HOME/.git" ]]; then
  echo "==> Güncelleniyor: $PANELZE_HOME ($PANELZE_INSTALL_MODE)"
  cd "$PANELZE_HOME"
  git remote set-url origin "$PANELZE_REPO_URL" 2>/dev/null || true
  git fetch origin "$PANELZE_BRANCH" --depth 1
  git checkout "$PANELZE_BRANCH"
  if [[ "$PANELZE_INSTALL_MODE" == "update" ]]; then
    git merge --ff-only "origin/$PANELZE_BRANCH" 2>/dev/null || git reset --hard "origin/$PANELZE_BRANCH"
    # Güncellemede git clean ÇALIŞTIRILMAZ (repo kökündeki izlenmeyen dosya/klasörler silinmesin).
  else
    git reset --hard "origin/$PANELZE_BRANCH"
    git clean -fd
  fi
else
  echo "==> Klonlanıyor: $PANELZE_REPO_URL ($PANELZE_BRANCH)"
  rm -rf "$PANELZE_HOME"
  git clone --depth 1 --branch "$PANELZE_BRANCH" "$PANELZE_REPO_URL" "$PANELZE_HOME"
fi

cd "$PANELZE_HOME"
if [[ ! -f deploy/bootstrap/install-production.sh ]]; then
  echo "Hata: deploy/bootstrap/install-production.sh yok. Repo/branch kontrol edin." >&2
  exit 1
fi

# Kritik helper dosyasını kurulumdan önce senkronla (install-production yine doğrulayacak).
if [[ -f deploy/host/panelze-security ]]; then
  install -m 755 deploy/host/panelze-security /usr/local/sbin/panelze-security
  ln -sfn /usr/local/sbin/panelze-security /usr/local/sbin/panelsar-security
fi
if [[ -f deploy/host/panelze-nginx-vhost ]]; then
  install -m 755 deploy/host/panelze-nginx-vhost /usr/local/sbin/panelze-nginx-vhost
  ln -sfn /usr/local/sbin/panelze-nginx-vhost /usr/local/sbin/panelsar-nginx-vhost
fi
if [[ -f deploy/host/panelze-stack-install ]]; then
  install -m 755 deploy/host/panelze-stack-install /usr/local/sbin/panelze-stack-install
  ln -sfn /usr/local/sbin/panelze-stack-install /usr/local/sbin/panelsar-stack-install
fi
if [[ -f deploy/host/panelze-terminal-root ]]; then
  install -m 755 deploy/host/panelze-terminal-root /usr/local/sbin/panelze-terminal-root
  ln -sfn /usr/local/sbin/panelze-terminal-root /usr/local/sbin/panelsar-terminal-root
fi
if [[ -f deploy/host/panelze-php-ini ]]; then
  install -m 755 deploy/host/panelze-php-ini /usr/local/sbin/panelze-php-ini
  ln -sfn /usr/local/sbin/panelze-php-ini /usr/local/sbin/panelsar-php-ini
fi
if [[ -f deploy/host/panelze-cleaner ]]; then
  install -m 755 deploy/host/panelze-cleaner /usr/local/sbin/panelze-cleaner
  ln -sfn /usr/local/sbin/panelze-cleaner /usr/local/sbin/panelsar-cleaner
fi
if [[ -f deploy/host/panelze-node-pm2 ]]; then
  install -m 755 deploy/host/panelze-node-pm2 /usr/local/sbin/panelze-node-pm2
  ln -sfn /usr/local/sbin/panelze-node-pm2 /usr/local/sbin/panelsar-node-pm2
fi
if [[ -f deploy/host/panelze-mail-stack-setup.sh ]]; then
  install -m 755 deploy/host/panelze-mail-stack-setup.sh /usr/local/sbin/panelze-mail-stack-setup.sh
  ln -sfn /usr/local/sbin/panelze-mail-stack-setup.sh /usr/local/sbin/panelsar-mail-stack-setup.sh
fi

# Panel/engine özellik güncellemeleri için bu dosyayı değiştirmeniz gerekmez: aynı komut repo’yu çeker;
# install-production.sh PHP, ön yüz, Go engine derlemesi ve systemd yeniden başlatmayı yapar.
echo "==> Kurulum (install-production.sh) başlıyor..."
exec bash deploy/bootstrap/install-production.sh
