#!/usr/bin/env bash
set -euo pipefail

SSH_KEY="${HOSTVIM_SSH_KEY:-$HOME/.ssh/hostvim_aapanel}"
SSH_HOST="${HOSTVIM_SSH_HOST:-root@207.180.237.13}"
KODSAR="/var/www/hostvim/data/www/kodsar.com/public_html"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PATCH="$REPO_ROOT/deploy/kodsar"
SSH_CMD=(ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_HOST")
RSYNC_SSH="ssh -i $SSH_KEY -o StrictHostKeyChecking=no"

echo "==> Dosyalar sunucuya kopyalanıyor"
rsync -az -e "$RSYNC_SSH" \
  "$PATCH/app/Http/Controllers/PageController.php" \
  "$SSH_HOST:$KODSAR/app/Http/Controllers/PageController.php"
rsync -az -e "$RSYNC_SSH" \
  "$PATCH/app/Http/Controllers/Concerns/" \
  "$SSH_HOST:$KODSAR/app/Http/Controllers/Concerns/"
rsync -az -e "$RSYNC_SSH" \
  "$PATCH/resources/js/Pages/Page/" \
  "$SSH_HOST:$KODSAR/resources/js/Pages/Page/"
rsync -az -e "$RSYNC_SSH" \
  "$PATCH/resources/js/Components/Frontend/Themes/Renkli/Footer.vue" \
  "$SSH_HOST:$KODSAR/resources/js/Components/Frontend/Themes/Renkli/Footer.vue"
rsync -az -e "$RSYNC_SSH" \
  "$PATCH/database/seeders/KodsarLegalPagesSeeder.php" \
  "$SSH_HOST:$KODSAR/database/seeders/KodsarLegalPagesSeeder.php"
"${SSH_CMD[@]}" "mkdir -p $KODSAR/deploy/patches"
rsync -az -e "$RSYNC_SSH" \
  "$PATCH/patches/apply-php-patches.php" \
  "$SSH_HOST:$KODSAR/deploy/patches/apply-php-patches.php"

echo "==> Patch + seed + build"
"${SSH_CMD[@]}" "bash -s" <<'REMOTE'
set -euo pipefail
KODSAR=/var/www/hostvim/data/www/kodsar.com/public_html
cd "$KODSAR"
php deploy/patches/apply-php-patches.php
php artisan db:seed --class=KodsarLegalPagesSeeder --force
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
npm run build
chown -R www-data:www-data storage bootstrap/cache public/build 2>/dev/null || true
echo "==> HTTP test"
for slug in hakkimizda gizlilik-politikasi kullanim-sartlari kvkk hesap-silme iletisim; do
  code=$(curl -sk -o /dev/null -w "%{http_code}" "https://kodsar.com/sayfa/$slug")
  echo "  /sayfa/$slug -> HTTP $code"
done
REMOTE

echo ""
echo "Tamam: https://kodsar.com/sayfa/gizlilik-politikasi"
