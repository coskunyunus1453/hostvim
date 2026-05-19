#!/usr/bin/env bash
#
# hostvim.com — SIFIRDAN tam landing kurulumu (yalnızca landing/, panel/engine YOK).
#
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/scripts/install-landing-site.sh" | bash
#
set -euo pipefail

[[ "$(id -u)" -eq 0 ]] || { echo "Root: curl -fsSL ... | sudo bash" >&2; exit 1; }

HOSTVIM_REPO_URL="${HOSTVIM_REPO_URL:-https://github.com/coskunyunus1453/hostvim.git}"
HOSTVIM_BRANCH="${HOSTVIM_BRANCH:-main}"
LANDING_ROOT="${LANDING_ROOT:-/var/www/hostvim/data/www/hostvim.com}"
PUBLIC_HTML="${PUBLIC_HTML:-$LANDING_ROOT/public_html}"
RUN_USER="${RUN_USER:-www-data}"
APP_URL="${APP_URL:-http://194.163.131.213}"
WORK="$(mktemp -d)"
ENV_BACKUP=""
trap 'rm -rf "$WORK"; [[ -n "$ENV_BACKUP" && -f "$ENV_BACKUP" && ! -f "$LANDING_ROOT/.env" ]] && cp -a "$ENV_BACKUP" "$LANDING_ROOT/.env"' EXIT

export DEBIAN_FRONTEND=noninteractive
for cmd in git rsync curl; do
  command -v "$cmd" >/dev/null 2>&1 || apt-get install -y -qq "$cmd"
done
command -v composer >/dev/null 2>&1 || apt-get install -y -qq composer php-cli php-mbstring php-xml php-curl php-zip php-mysql php-sqlite3 unzip

echo "=============================================="
echo " Hostvim LANDING — sıfırdan kurulum"
echo " Site kökü:    $LANDING_ROOT"
echo " Web kökü:     $PUBLIC_HTML"
echo "=============================================="

if [[ -f "$LANDING_ROOT/.env" ]]; then
  ENV_BACKUP="$(mktemp)"
  cp -a "$LANDING_ROOT/.env" "$ENV_BACKUP"
  echo "==> Mevcut .env yedeklendi"
fi

echo "==> GitHub'dan landing indiriliyor..."
git clone --depth 1 --branch "$HOSTVIM_BRANCH" "$HOSTVIM_REPO_URL" "$WORK/repo"
[[ -f "$WORK/repo/landing/artisan" ]] || { echo "Hata: repo/landing/artisan yok" >&2; exit 1; }
[[ -f "$WORK/repo/landing/public/index.php" ]] || { echo "Hata: landing/public/index.php yok" >&2; exit 1; }

mkdir -p "$LANDING_ROOT" "$PUBLIC_HTML"

echo "==> Landing dosyaları (tüm uygulama, vendor hariç)"
rsync -a --delete \
  --exclude '.env' \
  --exclude 'vendor/' \
  --exclude 'node_modules/' \
  --exclude 'storage/logs/*.log' \
  "$WORK/repo/landing/" "$LANDING_ROOT/"

echo "==> public_html (web kökü)"
rsync -a --delete \
  "$WORK/repo/landing/public/" "$PUBLIC_HTML/"

if [[ -n "$ENV_BACKUP" && -f "$ENV_BACKUP" ]]; then
  cp -a "$ENV_BACKUP" "$LANDING_ROOT/.env"
  echo "==> .env geri yüklendi"
elif [[ ! -f "$LANDING_ROOT/.env" ]]; then
  if [[ -f "$LANDING_ROOT/.env.example" ]]; then
    cp -a "$LANDING_ROOT/.env.example" "$LANDING_ROOT/.env"
    echo "==> .env.example -> .env (DB bilgilerini düzenleyin)"
  fi
fi

mkdir -p \
  "$LANDING_ROOT/storage/framework/cache/data" \
  "$LANDING_ROOT/storage/framework/sessions" \
  "$LANDING_ROOT/storage/framework/views" \
  "$LANDING_ROOT/storage/logs" \
  "$LANDING_ROOT/bootstrap/cache" \
  "$LANDING_ROOT/vendor"

chown -R "$RUN_USER:$RUN_USER" "$LANDING_ROOT"
chmod -R ug+rwx "$LANDING_ROOT/storage" "$LANDING_ROOT/bootstrap/cache"

cd "$LANDING_ROOT"

# Üretim temel ayarları (.env içinde yoksa ekle / güncelle)
if [[ -f .env ]]; then
  grep -q '^APP_ENV=' .env && sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env || echo 'APP_ENV=production' >> .env
  grep -q '^APP_DEBUG=' .env && sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env || echo 'APP_DEBUG=false' >> .env
  grep -q '^APP_URL=' .env && sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env || echo "APP_URL=${APP_URL}" >> .env
  grep -q '^ASSET_URL=' .env || echo 'ASSET_URL=' >> .env
  sed -i 's|^ASSET_URL=.*|ASSET_URL=|' .env 2>/dev/null || true
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  echo "==> APP_KEY üretiliyor"
  sudo -u "$RUN_USER" php artisan key:generate --force
fi

echo "==> composer install"
sudo -u "$RUN_USER" composer install --no-dev --optimize-autoloader --no-interaction

if [[ -f "$WORK/repo/landing/database/exports/hostvim_landing_full_2026-04-10.sql" ]] && [[ "${IMPORT_LANDING_SQL:-0}" == "1" ]]; then
  echo "==> SQL import (IMPORT_LANDING_SQL=1)"
  # Kullanıcı DB adı/şifreyi .env'den ayarlamalı
fi

echo "==> storage:link"
sudo -u "$RUN_USER" php artisan storage:link 2>/dev/null || true

echo "==> migrate"
sudo -u "$RUN_USER" php artisan migrate --force

if [[ "${RUN_SEED:-0}" == "1" ]]; then
  echo "==> seed (admin kullanıcı)"
  sudo -u "$RUN_USER" php artisan db:seed --force
fi

sudo -u "$RUN_USER" php artisan config:cache
sudo -u "$RUN_USER" php artisan route:cache
sudo -u "$RUN_USER" php artisan view:cache

chown -R "$RUN_USER:$RUN_USER" "$LANDING_ROOT"

echo ""
echo "=============================================="
echo " KURULUM BİTTİ"
echo "=============================================="
echo " Web:        $APP_URL"
echo " Admin:      ${APP_URL}/admin/login"
echo " Sürümler:   ${APP_URL}/admin/panel-releases"
echo ""
echo " Kontrol:"
ls -la "$PUBLIC_HTML/index.php"
echo ""
echo " .env içinde DB_* doğru mu kontrol edin."
echo " Admin yoksa: RUN_SEED=1 ile betiği tekrar çalıştırın veya kullanıcı oluşturun."
echo " Nginx document root = $PUBLIC_HTML olmalı."
echo "=============================================="
