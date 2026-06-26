#!/usr/bin/env bash
#
# Panelze landing (panelze.com) — SADECE landing/ günceller.
# Panel kurmaz, engine derlemez, tüm repoyu reset --hard yapmaz.
#
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

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/resolve-paths.sh
source "$SCRIPT_DIR/lib/resolve-paths.sh"

# Panelze landing: Laravel kökü public_html içinde olabilir
if [[ -z "${LANDING_ROOT:-}" ]]; then
  _home="$(resolve_panelze_home)"
  if [[ -f "$_home/data/www/panelze.com/public_html/artisan" ]]; then
    LANDING_ROOT="$_home/data/www/panelze.com/public_html"
  elif [[ -f "$_home/landing/artisan" ]]; then
    LANDING_ROOT="$_home/landing"
  else
    LANDING_ROOT="/var/www/panelze/data/www/panelze.com"
  fi
fi
PANELZE_HOME="${PANELZE_HOME:-$(resolve_panelze_home)}"
PANELZE_REPO_URL="${PANELZE_REPO_URL:-https://github.com/coskunyunus1453/hostvim.git}"
PANELZE_BRANCH="${PANELZE_BRANCH:-main}"
if [[ -z "${PUBLIC_HTML:-}" ]]; then
  if [[ -d "$LANDING_ROOT/public" ]]; then
    PUBLIC_HTML="$LANDING_ROOT/public"
  else
    PUBLIC_HTML="$LANDING_ROOT/public_html"
  fi
fi
RUN_USER="${RUN_USER:-www-data}"
SKIP_COMPOSER="${SKIP_COMPOSER:-0}"

export DEBIAN_FRONTEND=noninteractive
command -v git >/dev/null 2>&1 || { apt-get update -qq && apt-get install -y -qq git ca-certificates curl; }
command -v rsync >/dev/null 2>&1 || apt-get install -y -qq rsync

mkdir -p "$(dirname "$PANELZE_HOME")" "$LANDING_ROOT" "$PUBLIC_HTML"

sync_landing_from_git() {
  if [[ ! -d "$PANELZE_HOME/.git" ]]; then
    echo "==> İlk kurulum: yalnızca landing/ (sparse clone, sığ)"
    rm -rf "$PANELZE_HOME"
    git clone --depth 1 --filter=blob:none --sparse --branch "$PANELZE_BRANCH" \
      "$PANELZE_REPO_URL" "$PANELZE_HOME"
    cd "$PANELZE_HOME"
    git sparse-checkout set landing
    return
  fi

  echo "==> Git: yalnızca landing/ klasörü güncelleniyor (tüm repo reset YOK)"
  cd "$PANELZE_HOME"
  git remote set-url origin "$PANELZE_REPO_URL" 2>/dev/null || true
  git fetch origin "$PANELZE_BRANCH" --depth=1
  git checkout "origin/$PANELZE_BRANCH" -- landing/
}

sync_landing_from_git

if [[ ! -d "$PANELZE_HOME/landing" ]]; then
  echo "Hata: $PANELZE_HOME/landing yok." >&2
  exit 1
fi

if [[ ! -f "$PANELZE_HOME/landing/public/index.php" ]]; then
  echo "Hata: Git'teki landing/public boş görünüyor — rsync yapılmadı (silme riski)." >&2
  echo "Önce: cd $PANELZE_HOME && git checkout origin/$PANELZE_BRANCH -- landing/" >&2
  echo "Veya: curl -fsSL .../restore-landing.sh | bash" >&2
  exit 1
fi

STAMP="$(date +%Y%m%d-%H%M%S)"
if [[ -d "$PUBLIC_HTML" ]]; then
  echo "==> Yedek: ${PUBLIC_HTML}.bak-${STAMP}"
  cp -a "$PUBLIC_HTML" "${PUBLIC_HTML}.bak-${STAMP}" 2>/dev/null || true
fi

echo "==> Site köküne kopyala: $LANDING_ROOT"
rsync -a \
  --exclude '.env' \
  --exclude 'vendor/' \
  --exclude 'node_modules/' \
  --exclude 'storage/logs/' \
  "$PANELZE_HOME/landing/" "$LANDING_ROOT/"

echo "==> Web kökü (public): $PUBLIC_HTML — rsync --delete KAPALI"
rsync -a \
  "$PANELZE_HOME/landing/public/" "$PUBLIC_HTML/"

if [[ ! -f "$LANDING_ROOT/artisan" ]]; then
  echo "Hata: $LANDING_ROOT/artisan yok. LANDING_ROOT yanlış olabilir." >&2
  exit 1
fi

cd "$LANDING_ROOT"

echo "==> İzinler ($RUN_USER)"
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache vendor
chown -R "$RUN_USER:$RUN_USER" "$LANDING_ROOT"
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

LOCK_HASH="$(md5sum composer.lock 2>/dev/null | awk '{print $1}' || echo '')"
LOCK_STAMP="$LANDING_ROOT/storage/framework/.composer-lock-hash"
PREV_HASH=""
[[ -f "$LOCK_STAMP" ]] && PREV_HASH="$(cat "$LOCK_STAMP")"

if [[ "$SKIP_COMPOSER" != "1" ]] && command -v composer >/dev/null 2>&1; then
  if [[ "$LOCK_HASH" != "$PREV_HASH" ]] || [[ ! -d vendor ]]; then
    echo "==> composer install (lock değişti veya vendor yok)"
    sudo -u "$RUN_USER" composer install --no-dev --optimize-autoloader --no-interaction
    echo "$LOCK_HASH" > "$LOCK_STAMP"
  else
    echo "==> composer atlandı (composer.lock aynı)"
  fi
else
  echo "==> composer atlandı"
fi

echo "==> migrate + önbellek"
sudo -u "$RUN_USER" php artisan migrate --force
sudo -u "$RUN_USER" php artisan config:cache
sudo -u "$RUN_USER" php artisan route:cache
sudo -u "$RUN_USER" php artisan view:cache

echo "==> Bitti. Landing: $LANDING_ROOT"
echo "    Admin: /admin/panel-releases"
