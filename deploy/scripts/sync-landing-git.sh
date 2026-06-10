#!/usr/bin/env bash
# landing/ → panelze.com (Git pull + kopyala). Root SSH:
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/panelze/main/deploy/scripts/sync-landing-git.sh" | bash
set -euo pipefail
[[ "$(id -u)" -eq 0 ]] || { echo "root gerekli"; exit 1; }

H=/var/www/panelze
SITE=/var/www/panelze/data/www/panelze.com
WEB=$SITE/public_html
U=www-data
B=${PANELZE_BRANCH:-main}

apt-get install -y -qq git rsync composer php-cli php-mbstring php-xml php-curl php-zip php-mysql unzip 2>/dev/null || true

if [[ ! -d $H/.git ]]; then
  git clone --branch "$B" https://github.com/coskunyunus1453/panelze.git "$H"
fi
cd "$H"
git remote set-url origin https://github.com/coskunyunus1453/panelze.git 2>/dev/null || true
git fetch origin "$B"
git checkout "$B" 2>/dev/null || git checkout -b "$B" "origin/$B"
git reset --hard "origin/$B"

[[ -f "$H/landing/artisan" ]] || { echo "landing/ yok"; exit 1; }

cp -a "$WEB" "${WEB}.bak.$(date +%s)" 2>/dev/null || true
[[ -f "$SITE/.env" ]] && cp -a "$SITE/.env" /tmp/panelze-landing-env.bak

mkdir -p "$SITE" "$WEB"
rsync -a --exclude .env --exclude vendor --exclude node_modules "$H/landing/" "$SITE/"
[[ -f /tmp/panelze-landing-env.bak ]] && cp -a /tmp/panelze-landing-env.bak "$SITE/.env"
rsync -a "$H/landing/public/" "$WEB/"

mkdir -p "$SITE/vendor" "$SITE/storage" "$SITE/bootstrap/cache"
chown -R $U:$U "$SITE"
chmod -R ug+rwx "$SITE/storage" "$SITE/bootstrap/cache"

cd "$SITE"
sudo -u $U composer install --no-dev -o -q
sudo -u $U php artisan migrate --force
sudo -u $U php artisan storage:link 2>/dev/null || true
sudo -u $U php artisan config:cache
sudo -u $U php artisan route:cache
sudo -u $U php artisan view:cache

echo "OK: $WEB/index.php + build=$(test -f $WEB/build/manifest.json && echo var || echo YOK)"
