#!/usr/bin/env bash
# Panelze landing (panelze.com) — PanelKafes FPM kullanıcısının logo/favicon yazabilmesi.
# open_basedir + ACL: cage user storage/bootstrap okur; landing-assets'e yazmalı.
#
#   PANELZE_HOME=/var/www/hostvim bash deploy/scripts/ensure-landing-upload-acl.sh
set -euo pipefail

resolve_home() {
  if [[ -n "${PANELZE_HOME:-}" && -d "${PANELZE_HOME}" ]]; then
    echo "$PANELZE_HOME"
    return
  fi
  for cand in /var/www/hostvim /var/www/panelze /opt/panelze; do
    if [[ -d "$cand/landing" ]] || [[ -d "$cand/panel" ]]; then
      echo "$cand"
      return
    fi
  done
  echo "/var/www/hostvim"
}

PANELZE_HOME="$(resolve_home)"
LAND="${LANDING_ROOT:-$PANELZE_HOME/landing}"
# Bazı kurulumlarda Laravel kökü public_html
if [[ ! -f "$LAND/artisan" && -f "$PANELZE_HOME/data/www/panelze.com/public_html/artisan" ]]; then
  LAND="$PANELZE_HOME/data/www/panelze.com/public_html"
fi

CAGE_USER="${LANDING_CAGE_USER:-pk-panelze-com}"
if ! id "$CAGE_USER" >/dev/null 2>&1; then
  # Alternatif isimler
  for u in pk-panelze-com pk_panelze_com www-data; do
    if id "$u" >/dev/null 2>&1; then
      CAGE_USER="$u"
      break
    fi
  done
fi

ASSETS="$LAND/public/landing-assets"
mkdir -p "$ASSETS/landing" \
  "$LAND/storage/framework/cache/data" \
  "$LAND/storage/framework/sessions" \
  "$LAND/storage/framework/views" \
  "$LAND/storage/logs" \
  "$LAND/bootstrap/cache"

chown -R www-data:www-data "$ASSETS" 2>/dev/null || true
chmod -R ug+rwX "$ASSETS" "$LAND/storage" "$LAND/bootstrap/cache" 2>/dev/null || true

if command -v setfacl >/dev/null 2>&1 && id "$CAGE_USER" >/dev/null 2>&1; then
  echo "==> ACL: $CAGE_USER → landing (rX) + landing-assets/storage/bootstrap (rwX)"
  setfacl -R -m "u:${CAGE_USER}:rX" "$LAND" 2>/dev/null || true
  setfacl -R -m "u:${CAGE_USER}:rwX" "$ASSETS" "$LAND/storage" "$LAND/bootstrap/cache" 2>/dev/null || true
  setfacl -R -d -m "u:${CAGE_USER}:rwX" "$ASSETS" "$LAND/storage" "$LAND/bootstrap/cache" 2>/dev/null || true
  if [[ -f "$LAND/.env" ]]; then
    setfacl -m "u:${CAGE_USER}:r" "$LAND/.env" 2>/dev/null || true
  fi
else
  echo "==> setfacl yok veya cage user yok; chown www-data ile devam"
fi

# Hızlı yazma testi
if id "$CAGE_USER" >/dev/null 2>&1; then
  if sudo -u "$CAGE_USER" touch "$ASSETS/landing/.acl-ok" 2>/dev/null; then
    rm -f "$ASSETS/landing/.acl-ok"
    echo "==> Yazma OK: $CAGE_USER → $ASSETS/landing"
  else
    echo "UYARI: $CAGE_USER hâlâ yazamıyor: $ASSETS/landing" >&2
    exit 1
  fi
fi
