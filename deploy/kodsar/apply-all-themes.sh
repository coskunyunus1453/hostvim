#!/usr/bin/env bash
set -euo pipefail

SSH_KEY="${HOSTVIM_SSH_KEY:-$HOME/.ssh/hostvim_aapanel}"
SSH_HOST="${HOSTVIM_SSH_HOST:-root@207.180.237.13}"
KODSAR="/var/www/hostvim/data/www/kodsar.com/public_html"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PATCH="$REPO_ROOT/deploy/kodsar"
SSH_CMD=(ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_HOST")
RSYNC_SSH="ssh -i $SSH_KEY -o StrictHostKeyChecking=no"

echo "==> Kodsar yasal sayfalar + tüm temalar deploy"

rsync -az -e "$RSYNC_SSH" "$PATCH/app/Http/Middleware/HandleInertiaRequests.php" "$SSH_HOST:$KODSAR/app/Http/Middleware/HandleInertiaRequests.php"
rsync -az -e "$RSYNC_SSH" "$PATCH/resources/js/Layouts/FrontendLayout.vue" "$SSH_HOST:$KODSAR/resources/js/Layouts/FrontendLayout.vue"
rsync -az -e "$RSYNC_SSH" "$PATCH/resources/js/Composables/" "$SSH_HOST:$KODSAR/resources/js/Composables/"
rsync -az -e "$RSYNC_SSH" "$PATCH/resources/js/Components/Frontend/Themes/" "$SSH_HOST:$KODSAR/resources/js/Components/Frontend/Themes/"

# Önceki legal pages dosyaları (varsa)
rsync -az -e "$RSYNC_SSH" "$PATCH/app/Http/Controllers/PageController.php" "$SSH_HOST:$KODSAR/app/Http/Controllers/PageController.php" 2>/dev/null || true
rsync -az -e "$RSYNC_SSH" "$PATCH/resources/js/Pages/Page/" "$SSH_HOST:$KODSAR/resources/js/Pages/Page/" 2>/dev/null || true

"${SSH_CMD[@]}" "bash -s" <<'REMOTE'
set -euo pipefail
KODSAR=/var/www/hostvim/data/www/kodsar.com/public_html
cd "$KODSAR"

php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
npm run build
chown -R www-data:www-data storage bootstrap/cache public/build 2>/dev/null || true

echo "==> Tema doğrulama (Renkli aktif)"
for slug in gizlilik-politikasi kvkk hesap-silme; do
  code=$(curl -sk -o /dev/null -w "%{http_code}" "https://kodsar.com/sayfa/$slug")
  echo "  /sayfa/$slug -> HTTP $code"
done
REMOTE

echo "Tamam."
