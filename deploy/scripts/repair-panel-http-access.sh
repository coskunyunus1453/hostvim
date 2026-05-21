#!/usr/bin/env bash
# Panel yalnızca IP ile HTTP açılsın (SSL yok / certbot yarım kaldıysa).
# Kullanım: sudo bash deploy/scripts/repair-panel-http-access.sh
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PANEL_ROOT="${PANEL_ROOT:-$REPO_ROOT/panel}"
ENV_FILE="$PANEL_ROOT/.env"
NGX_DST="/etc/nginx/sites-available/hostvim.conf"
PHP_FPM_SOCK="${PHP_FPM_SOCK:-/run/php/php8.4-fpm.sock}"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Hata: $ENV_FILE yok" >&2
  exit 1
fi

if [[ ! -S "$PHP_FPM_SOCK" ]]; then
  for s in /run/php/php8.3-fpm.sock /run/php/php8.2-fpm.sock /run/php/php-fpm.sock; do
    if [[ -S "$s" ]]; then
      PHP_FPM_SOCK="$s"
      break
    fi
  done
fi

PRIMARY_IP="$(hostname -I 2>/dev/null | awk '{print $1}' || true)"
[[ -n "$PRIMARY_IP" ]] || PRIMARY_IP="127.0.0.1"

APP_URL_CURRENT="$(grep -E '^APP_URL=' "$ENV_FILE" | head -1 | cut -d= -f2- | tr -d '\r"'"'"' ')"
APP_URL_CURRENT="${APP_URL_CURRENT%/}"

if [[ "$APP_URL_CURRENT" == https://* ]]; then
  NEW_URL="http://${APP_URL_CURRENT#https://}"
  echo "==> APP_URL HTTPS -> HTTP: $NEW_URL"
  if grep -q '^APP_URL=' "$ENV_FILE"; then
    sed -i "s|^APP_URL=.*|APP_URL=$NEW_URL|" "$ENV_FILE"
  else
    echo "APP_URL=$NEW_URL" >>"$ENV_FILE"
  fi
elif [[ -z "$APP_URL_CURRENT" ]]; then
  NEW_URL="http://$PRIMARY_IP"
  echo "==> APP_URL ayarlanıyor: $NEW_URL"
  echo "APP_URL=$NEW_URL" >>"$ENV_FILE"
fi

NEW_URL="$(grep -E '^APP_URL=' "$ENV_FILE" | head -1 | cut -d= -f2- | tr -d '\r"'"'"' ')"
NEW_URL="${NEW_URL%/}"
if [[ -n "$NEW_URL" ]]; then
  if grep -q '^PHPMYADMIN_URL=' "$ENV_FILE"; then
    sed -i "s|^PHPMYADMIN_URL=.*|PHPMYADMIN_URL=${NEW_URL}/phpmyadmin|" "$ENV_FILE"
  fi
fi

echo "==> Nginx panel şablonu (yalnızca HTTP :80, HTTPS yönlendirmesi yok)"
sed \
  -e 's|__SERVER_NAME__|_|g' \
  -e "s|__PANEL_PUBLIC__|$PANEL_ROOT/public|g" \
  -e "s|__PHP_FPM_SOCK__|$PHP_FPM_SOCK|g" \
  "$REPO_ROOT/deploy/nginx/hostvim.conf" >"$NGX_DST"
sed -i 's/listen 80;/listen 80 default_server;/' "$NGX_DST" || true
sed -i 's/listen \[::\]:80;/listen [::]:80 default_server;/' "$NGX_DST" || true

rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-enabled/panelsar.conf 2>/dev/null || true
ln -sf "$NGX_DST" /etc/nginx/sites-enabled/hostvim.conf

nginx -t
systemctl reload nginx

PHP_VER=""
if [[ "$PHP_FPM_SOCK" =~ php([0-9]+\.[0-9]+)-fpm ]]; then
  PHP_VER="${BASH_REMATCH[1]}"
fi
if [[ -n "$PHP_VER" && -d "/etc/php/$PHP_VER/fpm/pool.d" ]]; then
  echo "==> PHP-FPM uzun istek limiti (zip/unzip)"
  cp "$REPO_ROOT/deploy/php-fpm/hostvim-long-requests.conf" "/etc/php/$PHP_VER/fpm/pool.d/zz-hostvim-long.conf"
  systemctl reload "php${PHP_VER}-fpm" 2>/dev/null || systemctl reload php-fpm 2>/dev/null || true
fi

echo "==> Laravel config önbelleği"
cd "$PANEL_ROOT"
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan config:cache

echo ""
echo "Tamam. Panel: http://$PRIMARY_IP/"
echo "Not: Tarayıcıda eski HSTS varsa gizli pencerede deneyin veya site verilerini temizleyin."
