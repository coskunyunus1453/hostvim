#!/usr/bin/env bash
# phpMyAdmin «signon» — Panelze Pro tek tık giriş için bir kez çalıştırın.
set -euo pipefail

APP_URL="${1:-${APP_URL:-}}"
if [[ -z "$APP_URL" ]]; then
  if [[ -f /var/www/hostvim/panel/.env ]]; then
    APP_URL="$(grep -E '^APP_URL=' /var/www/hostvim/panel/.env | head -1 | cut -d= -f2- | tr -d '\r"'\'')"
  fi
fi
APP_URL="${APP_URL%/}"
if [[ -z "$APP_URL" ]]; then
  echo "Kullanım: APP_URL=https://panel.ornek.com $0" >&2
  exit 1
fi

SIGNON_URL="${APP_URL}/pma-signon"
CONF="/etc/phpmyadmin/conf.d/hostvim-signon.php"

if [[ ! -d /etc/phpmyadmin ]]; then
  echo "phpMyAdmin kurulu değil (/etc/phpmyadmin yok)." >&2
  exit 1
fi

cat > "$CONF" <<EOF
<?php
/**
 * Hostvim / Panelze — otomatik phpMyAdmin girişi (Pro lisans).
 * Üretim: deploy/scripts/configure-phpmyadmin-signon.sh
 */
\$i = 1;
if (! isset(\$cfg['Servers'][\$i])) {
    \$cfg['Servers'][\$i] = [];
}
\$cfg['Servers'][\$i]['auth_type'] = 'signon';
\$cfg['Servers'][\$i]['SignonURL'] = '${SIGNON_URL}';
\$cfg['Servers'][\$i]['SignonSession'] = 'SignonSession';
\$cfg['Servers'][\$i]['host'] = '127.0.0.1';
\$cfg['Servers'][\$i]['compress'] = false;
\$cfg['Servers'][\$i]['AllowNoPassword'] = false;
EOF

chmod 644 "$CONF"
echo "Yazıldı: $CONF"
echo "SignonURL: $SIGNON_URL"
echo "Panel .env içinde PHPMYADMIN_URL=${APP_URL}/phpmyadmin olduğundan emin olun."
