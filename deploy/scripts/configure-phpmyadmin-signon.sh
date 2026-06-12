#!/usr/bin/env bash
# phpMyAdmin «signon» — Panelze Pro tek tık giriş için bir kez çalıştırın.
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/resolve-paths.sh
source "$SCRIPT_DIR/lib/resolve-paths.sh"
PANEL_ENV="${PANEL_ENV:-$(resolve_panel_root)/.env}"
if [[ ! -f "$PANEL_ENV" ]] && [[ -f /var/www/panelze/panel/.env ]]; then
  PANEL_ENV="/var/www/panelze/panel/.env"
fi

if [[ ! -f "$PANEL_ENV" ]]; then
  echo "Panel .env bulunamadı: $PANEL_ENV" >&2
  exit 1
fi

read_env() {
  local key="$1"
  grep -E "^${key}=" "$PANEL_ENV" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r"'\''' || true
}

APP_URL="$(read_env APP_URL)"
APP_URL="${APP_URL%/}"
if [[ -z "$APP_URL" ]]; then
  echo "APP_URL .env içinde tanımlı değil." >&2
  exit 1
fi

if [[ ! -d /etc/phpmyadmin ]]; then
  echo "phpMyAdmin kurulu değil (/etc/phpmyadmin yok)." >&2
  exit 1
fi

INTERNAL_KEY="$(read_env PANELZE_PMA_SIGNON_INTERNAL_KEY)"
if [[ -z "$INTERNAL_KEY" ]]; then
  INTERNAL_KEY="$(openssl rand -hex 32)"
  echo "PANELZE_PMA_SIGNON_INTERNAL_KEY=${INTERNAL_KEY}" >> "$PANEL_ENV"
  echo "==> .env içine PANELZE_PMA_SIGNON_INTERNAL_KEY eklendi"
fi

SESSION_NAME="$(read_env PANELZE_PMA_SIGNON_SESSION)"
if [[ -z "$SESSION_NAME" ]]; then
  SESSION_NAME="SignonSession"
fi

PANEL_INTERNAL_URL="http://127.0.0.1"
if [[ "$APP_URL" == */index.php ]]; then
  PANEL_INTERNAL_URL="${PANEL_INTERNAL_URL}/index.php"
fi

SIGNON_PHP_SRC="$(cd "$(dirname "$0")/.." && pwd)/host/panelze-pma-signon.php"
SIGNON_PHP_DST="/usr/share/phpmyadmin/panelze-pma-signon.php"
if [[ ! -f "$SIGNON_PHP_SRC" ]]; then
  SIGNON_PHP_SRC="/usr/local/share/panelze/panelze-pma-signon.php"
fi
if [[ ! -f "$SIGNON_PHP_SRC" ]]; then
  echo "panelze-pma-signon.php bulunamadı." >&2
  exit 1
fi

install -d -m 0755 /usr/local/share/panelze
install -m 0644 "$SIGNON_PHP_SRC" /usr/local/share/panelze/panelze-pma-signon.php
install -m 0644 "$SIGNON_PHP_SRC" "$SIGNON_PHP_DST"

cat > /etc/phpmyadmin/panelze-signon.inc.php <<EOF
<?php
return [
    'panel_internal_url' => '${PANEL_INTERNAL_URL}',
    'internal_key' => '${INTERNAL_KEY}',
    'session_name' => '${SESSION_NAME}',
];
EOF
chmod 644 /etc/phpmyadmin/panelze-signon.inc.php

SIGNON_URL="${APP_URL}/phpmyadmin/panelze-pma-signon.php"
CONF="/etc/phpmyadmin/conf.d/panelze-signon.php"

cat > "$CONF" <<EOF
<?php
/**
 * Panelze — otomatik phpMyAdmin girişi (Pro lisans).
 * Üretim: deploy/scripts/configure-phpmyadmin-signon.sh
 */
\$i = 1;
if (! isset(\$cfg['Servers'][\$i])) {
    \$cfg['Servers'][\$i] = [];
}
\$cfg['Servers'][\$i]['auth_type'] = 'signon';
\$cfg['Servers'][\$i]['SignonURL'] = '${SIGNON_URL}';
\$cfg['Servers'][\$i]['SignonSession'] = '${SESSION_NAME}';
\$cfg['Servers'][\$i]['SignonCookieParams'] = [
    'lifetime' => 0,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
];
\$cfg['Servers'][\$i]['host'] = '127.0.0.1';
\$cfg['Servers'][\$i]['compress'] = false;
\$cfg['Servers'][\$i]['AllowNoPassword'] = false;
EOF
chmod 644 "$CONF"

if [[ -d "$(dirname "$PANEL_ENV")" ]]; then
  PANEL_DIR="$(dirname "$PANEL_ENV")"
  (cd "$PANEL_DIR" && php artisan config:clear >/dev/null 2>&1 || true)
fi

echo "Yazıldı: $SIGNON_PHP_DST"
echo "Yazıldı: /etc/phpmyadmin/panelze-signon.inc.php"
echo "Yazıldı: $CONF"
echo "SignonURL: $SIGNON_URL"
echo "Panel iç URL: ${PANEL_INTERNAL_URL}/api/internal/phpmyadmin-signon/consume"
echo "Panel .env içinde PHPMYADMIN_URL=${APP_URL}/phpmyadmin olduğundan emin olun."
