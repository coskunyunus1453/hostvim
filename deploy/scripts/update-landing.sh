#!/usr/bin/env bash
#
# Hostvim landing (hostvim.com) — kod güncelleme + migrate.
# Panel/engine kurmaz; data/www ve landing .env korunur.
#
# Örnek (root SSH):
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/scripts/update-landing.sh" | bash
#
set -euo pipefail

if [[ "$(uname -s)" != "Linux" ]]; then
  echo "Bu betik yalnızca Linux sunucuda çalışır." >&2
  exit 1
fi

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Root veya: curl -fsSL ... | sudo bash" >&2
  exit 1
fi

HOSTVIM_HOME="${HOSTVIM_HOME:-/var/www/hostvim}"
HOSTVIM_REPO_URL="${HOSTVIM_REPO_URL:-https://github.com/coskunyunus1453/hostvim.git}"
HOSTVIM_BRANCH="${HOSTVIM_BRANCH:-main}"
LANDING_ROOT="${LANDING_ROOT:-/var/www/hostvim/data/www/hostvim.com}"
PUBLIC_HTML="${PUBLIC_HTML:-$LANDING_ROOT/public_html}"
RUN_USER="${RUN_USER:-www-data}"

export DEBIAN_FRONTEND=noninteractive
command -v git >/dev/null 2>&1 || { apt-get update -qq && apt-get install -y -qq git ca-certificates curl; }
command -v rsync >/dev/null 2>&1 || apt-get install -y -qq rsync

mkdir -p "$(dirname "$HOSTVIM_HOME")" "$LANDING_ROOT" "$PUBLIC_HTML"

if [[ -d "$HOSTVIM_HOME/.git" ]]; then
  echo "==> Repo güncelleniyor: $HOSTVIM_HOME"
  cd "$HOSTVIM_HOME"
  git remote set-url origin "$HOSTVIM_REPO_URL" 2>/dev/null || true
  git fetch origin "$HOSTVIM_BRANCH"
  git checkout "$HOSTVIM_BRANCH"
  git reset --hard "origin/$HOSTVIM_BRANCH"
else
  echo "==> Repo klonlanıyor: $HOSTVIM_REPO_URL"
  git clone --branch "$HOSTVIM_BRANCH" "$HOSTVIM_REPO_URL" "$HOSTVIM_HOME"
  cd "$HOSTVIM_HOME"
fi

if [[ ! -d "$HOSTVIM_HOME/landing" ]]; then
  echo "Hata: $HOSTVIM_HOME/landing yok." >&2
  exit 1
fi

echo "==> Landing dosyaları: $LANDING_ROOT"
rsync -a \
  --exclude '.env' \
  --exclude 'vendor/' \
  --exclude 'node_modules/' \
  --exclude 'storage/logs/*.log' \
  "$HOSTVIM_HOME/landing/" "$LANDING_ROOT/"

echo "==> public -> $PUBLIC_HTML"
rsync -a --delete "$HOSTVIM_HOME/landing/public/" "$PUBLIC_HTML/"

if [[ ! -f "$LANDING_ROOT/artisan" ]]; then
  echo "Hata: $LANDING_ROOT/artisan bulunamadı. LANDING_ROOT doğru mu?" >&2
  exit 1
fi

cd "$LANDING_ROOT"

if [[ ! -f .env ]]; then
  echo "Uyarı: .env yok. Örnek: cp .env.example .env && php artisan key:generate" >&2
fi

echo "==> composer install"
if command -v composer >/dev/null 2>&1; then
  sudo -u "$RUN_USER" composer install --no-dev --optimize-autoloader --no-interaction
else
  echo "Uyarı: composer yok; vendor güncellenmedi." >&2
fi

echo "==> artisan migrate + cache"
sudo -u "$RUN_USER" php artisan migrate --force
sudo -u "$RUN_USER" php artisan config:cache
sudo -u "$RUN_USER" php artisan route:cache
sudo -u "$RUN_USER" php artisan view:cache

chown -R "$RUN_USER:$RUN_USER" storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo "==> Tamam. Landing: $LANDING_ROOT (web: $PUBLIC_HTML)"
echo "    Admin: /admin/panel-releases"
