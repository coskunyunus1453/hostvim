#!/usr/bin/env bash
#
# hostvim.com landing dosyalarını GitHub'dan geri yükler (rsync --delete hasarı sonrası).
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/scripts/restore-landing.sh" | bash
#
set -euo pipefail

[[ "$(id -u)" -eq 0 ]] || { echo "Root gerekli." >&2; exit 1; }

HOSTVIM_REPO_URL="${HOSTVIM_REPO_URL:-https://github.com/coskunyunus1453/hostvim.git}"
HOSTVIM_BRANCH="${HOSTVIM_BRANCH:-main}"
LANDING_ROOT="${LANDING_ROOT:-/var/www/hostvim/data/www/hostvim.com}"
PUBLIC_HTML="${PUBLIC_HTML:-$LANDING_ROOT/public_html}"
RUN_USER="${RUN_USER:-www-data}"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

echo "==> Geçici klon: $WORK"
git clone --depth 1 --branch "$HOSTVIM_BRANCH" "$HOSTVIM_REPO_URL" "$WORK/repo"
[[ -f "$WORK/repo/landing/public/index.php" ]] || { echo "Hata: landing/public/index.php yok." >&2; exit 1; }

STAMP="$(date +%Y%m%d-%H%M%S)"
if [[ -d "$PUBLIC_HTML" ]] && [[ "$(ls -A "$PUBLIC_HTML" 2>/dev/null | head -1)" != "" ]]; then
  echo "==> Yedek: ${PUBLIC_HTML}.bak-${STAMP}"
  cp -a "$PUBLIC_HTML" "${PUBLIC_HTML}.bak-${STAMP}"
fi

mkdir -p "$LANDING_ROOT" "$PUBLIC_HTML"

echo "==> Landing geri yükleme -> $LANDING_ROOT"
rsync -a \
  --exclude '.env' \
  --exclude 'vendor/' \
  --exclude 'node_modules/' \
  "$WORK/repo/landing/" "$LANDING_ROOT/"

echo "==> public_html (DELETE YOK)"
rsync -a "$WORK/repo/landing/public/" "$PUBLIC_HTML/"

mkdir -p "$LANDING_ROOT/vendor" "$LANDING_ROOT/storage" "$LANDING_ROOT/bootstrap/cache"
chown -R "$RUN_USER:$RUN_USER" "$LANDING_ROOT"
chmod -R ug+rwx "$LANDING_ROOT/storage" "$LANDING_ROOT/bootstrap/cache" 2>/dev/null || true

cd "$LANDING_ROOT"
if [[ -f .env ]] && command -v composer >/dev/null 2>&1; then
  echo "==> composer install"
  sudo -u "$RUN_USER" composer install --no-dev --optimize-autoloader --no-interaction
fi
sudo -u "$RUN_USER" php artisan migrate --force 2>/dev/null || true
sudo -u "$RUN_USER" php artisan config:cache 2>/dev/null || true
sudo -u "$RUN_USER" php artisan route:cache 2>/dev/null || true
sudo -u "$RUN_USER" php artisan view:cache 2>/dev/null || true

echo "==> Tamam. Kontrol:"
ls -la "$PUBLIC_HTML/index.php"
echo "    Site: $LANDING_ROOT"
