#!/usr/bin/env bash
# Roundcube «panelze-signon» — Panelze tek tık webmail girişi için bir kez çalıştırın.
set -euo pipefail

PANEL_ENV="${PANEL_ENV:-/var/www/hostvim/panel/.env}"
if [[ ! -f "$PANEL_ENV" ]] && [[ -f /var/www/panelze/panel/.env ]]; then
  PANEL_ENV="/var/www/panelze/panel/.env"
fi

if [[ ! -f "$PANEL_ENV" ]]; then
  echo "Panel .env bulunamadı: $PANEL_ENV" >&2
  exit 1
fi

read_env() {
  local key="$1"
  grep -E "^${key}=" "$PANEL_ENV" | head -1 | cut -d= -f2- | tr -d '\r"'\'''
}

APP_URL="$(read_env APP_URL)"
APP_URL="${APP_URL%/}"
if [[ -z "$APP_URL" ]]; then
  echo "APP_URL .env içinde tanımlı değil." >&2
  exit 1
fi

INTERNAL_KEY="$(read_env PANELZE_WEBMAIL_SIGNON_INTERNAL_KEY)"
if [[ -z "$INTERNAL_KEY" ]]; then
  INTERNAL_KEY="$(openssl rand -hex 32)"
  echo "PANELZE_WEBMAIL_SIGNON_INTERNAL_KEY=${INTERNAL_KEY}" >> "$PANEL_ENV"
  echo "==> .env içine PANELZE_WEBMAIL_SIGNON_INTERNAL_KEY eklendi"
fi

# Yerel istek: nginx + index.php yolu (rewrite’sız kurulumlarla uyumlu)
PANEL_INTERNAL_URL="http://127.0.0.1"
if [[ "$APP_URL" == */index.php ]]; then
  PANEL_INTERNAL_URL="${PANEL_INTERNAL_URL}/index.php"
fi

SIGNON_PHP_SRC="$(cd "$(dirname "$0")/.." && pwd)/host/panelze-roundcube-signon.php"
SIGNON_PHP_DST="/usr/share/roundcube/panelze-signon.php"
if [[ ! -f "$SIGNON_PHP_SRC" ]]; then
  SIGNON_PHP_SRC="/usr/local/share/panelze/panelze-roundcube-signon.php"
fi
if [[ ! -f "$SIGNON_PHP_SRC" ]]; then
  echo "panelze-roundcube-signon.php bulunamadı." >&2
  exit 1
fi

install -d -m 0755 /usr/local/share/panelze
install -m 0644 "$SIGNON_PHP_SRC" /usr/local/share/panelze/panelze-roundcube-signon.php
install -m 0644 "$SIGNON_PHP_SRC" "$SIGNON_PHP_DST"

install -d -m 0755 /etc/roundcube
cat > /etc/roundcube/panelze-signon.inc.php <<EOF
<?php
return [
    'panel_internal_url' => '${PANEL_INTERNAL_URL}',
    'internal_key' => '${INTERNAL_KEY}',
];
EOF
chmod 644 /etc/roundcube/panelze-signon.inc.php

NGX_SNIP="/etc/nginx/snippets/panelze-roundcube-signon.conf"
if [[ ! -f "$NGX_SNIP" ]]; then
  cat > "$NGX_SNIP" <<'NGX'
location = /panelze-signon {
    include snippets/fastcgi-php.conf;
    fastcgi_param SCRIPT_FILENAME /usr/share/roundcube/panelze-signon.php;
    fastcgi_pass unix:PHP_SOCK_PLACEHOLDER;
}
NGX
  PHP_SOCK="$(readlink -f /run/php/php*-fpm.sock 2>/dev/null | head -1 || true)"
  if [[ -z "$PHP_SOCK" ]]; then
    PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
    PHP_SOCK="/run/php/php${PHP_VER}-fpm.sock"
  fi
  sed -i "s|PHP_SOCK_PLACEHOLDER|${PHP_SOCK}|g" "$NGX_SNIP"
fi

ROUNDCUBE_SITE="/etc/nginx/sites-available/panelze-roundcube"
if [[ -f "$ROUNDCUBE_SITE" ]] && ! grep -q 'panelze-roundcube-signon.conf' "$ROUNDCUBE_SITE"; then
  sed -i '/include snippets\/panelze-roundcube-php.conf;/i\  include snippets/panelze-roundcube-signon.conf;' "$ROUNDCUBE_SITE"
  nginx -t
  systemctl reload nginx
fi

if [[ -d /var/www/hostvim/panel ]] || [[ -d /var/www/panelze/panel ]]; then
  PANEL_DIR="$(dirname "$PANEL_ENV")"
  (cd "$PANEL_DIR" && php artisan config:clear >/dev/null 2>&1 || true)
fi

echo "Yazıldı: $SIGNON_PHP_DST"
echo "Yazıldı: /etc/roundcube/panelze-signon.inc.php"
echo "Panel iç URL: ${PANEL_INTERNAL_URL}/api/internal/webmail-signon/consume"
