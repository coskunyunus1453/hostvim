#!/usr/bin/env bash
# OpenLiteSpeed — Panelze backend (:8088, nginx edge 80/443)
set -euo pipefail

OLS_ROOT="${PANELZE_OLS_ROOT:-/usr/local/lsws}"
OLS_HTTP_PORT="${PANELZE_OLS_HTTP_PORT:-8088}"
MARK_BEGIN="# >>> panelze-ols-backend >>>"
MARK_END="# <<< panelze-ols-backend <<<"

panelze_ols_install() {
  if [[ -x "$OLS_ROOT/bin/lswsctrl" ]] || command -v openlitespeed >/dev/null 2>&1; then
    return 0
  fi
  echo "==> OpenLiteSpeed kuruluyor (LiteSpeed deposu)..."
  if ! wget -qO - https://repo.litespeed.sh | bash; then
    echo "Hata: OpenLiteSpeed kurulumu başarısız." >&2
    return 1
  fi
  apt-get install -y -qq openlitespeed openlitespeed-php83 2>/dev/null || apt-get install -y -qq openlitespeed || true
}

panelze_ols_bind_backend() {
  local cfg="$OLS_ROOT/conf/httpd_config.conf"
  [[ -f "$cfg" ]] || { echo "Uyarı: $cfg yok; OLS yapılandırması atlandı." >&2; return 0; }

  cp -a "$cfg" "${cfg}.panelze-bak.$(date +%Y%m%d%H%M%S)"

  # Varsayılan HTTP :80 → nginx ile çakışmasın (yönetim 7080). Satır sonu eşleşmesi :7080'ı bozmasın.
  sed -i \
    -e 's/address[[:space:]]\+\*:80[[:space:]]*$/address                  *:7080/' \
    -e 's/address[[:space:]]\+\[::\]:80[[:space:]]*$/address                  [::]:7080/' \
    -e 's/\*:708088/*:7080/' \
    "$cfg" 2>/dev/null || true

  if grep -q "$MARK_BEGIN" "$cfg"; then
    sed -i "/$MARK_BEGIN/,/$MARK_END/d" "$cfg"
  fi

  cat >>"$cfg" <<EOF

$MARK_BEGIN
listener panelzeBackend {
  address                 *:${OLS_HTTP_PORT}
  secure                  0
}
$MARK_END
EOF

  if [[ -x "$OLS_ROOT/bin/lswsctrl" ]]; then
    "$OLS_ROOT/bin/lswsctrl" restart || systemctl restart lshttpd 2>/dev/null || systemctl restart openlitespeed 2>/dev/null || true
  fi
  echo "==> OpenLiteSpeed backend :${OLS_HTTP_PORT} (nginx edge 80/443)"
}

panelze_ols_install
panelze_ols_bind_backend
