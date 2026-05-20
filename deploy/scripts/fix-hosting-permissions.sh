#!/usr/bin/env bash
# Müşteri site dosyaları (data/www) — Engine www-data ile yazar; SSH/root yüklemelerinde izin onarımı.
#
# Kullanım (root):
#   bash deploy/scripts/fix-hosting-permissions.sh
#   HOSTVIM_HOME=/var/www/hostvim bash deploy/scripts/fix-hosting-permissions.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ -f "$SCRIPT_DIR/lib/hostvim-deploy-common.sh" ]]; then
  # shellcheck source=lib/hostvim-deploy-common.sh
  source "$SCRIPT_DIR/lib/hostvim-deploy-common.sh"
elif [[ -f "${HOSTVIM_HOME:-/var/www/hostvim}/deploy/scripts/lib/hostvim-deploy-common.sh" ]]; then
  SCRIPT_DIR="${HOSTVIM_HOME:-/var/www/hostvim}/deploy/scripts"
  # shellcheck source=/dev/null
  source "$SCRIPT_DIR/lib/hostvim-deploy-common.sh"
else
  echo "Hata: hostvim-deploy-common.sh bulunamadi." >&2
  exit 1
fi

HOSTVIM_HOME="$(hostvim_resolve_hostvim_home "${PANEL_ROOT:-}")"
WEB_ROOT="${HOSTVIM_HOSTING_WEB_ROOT:-$HOSTVIM_HOME/data/www}"
OWNER="${RUN_USER:-www-data}"
GROUP="${RUN_GROUP:-$OWNER}"

if [[ ! -d "$WEB_ROOT" ]]; then
  mkdir -p "$WEB_ROOT"
fi

echo "==> Hosting web kökü: $WEB_ROOT"
echo "==> Sahiplik: $OWNER:$GROUP"

if [[ "$(id -u)" -eq 0 ]]; then
  chown -R "$OWNER:$GROUP" "$HOSTVIM_HOME/data"
  find "$WEB_ROOT" -type d -exec chmod 775 {} \; 2>/dev/null || true
  find "$WEB_ROOT" -type f -exec chmod 664 {} \; 2>/dev/null || true
else
  sudo chown -R "$OWNER:$GROUP" "$HOSTVIM_HOME/data"
  sudo find "$WEB_ROOT" -type d -exec chmod 775 {} \; 2>/dev/null || true
  sudo find "$WEB_ROOT" -type f -exec chmod 664 {} \; 2>/dev/null || true
fi

echo "Tamam. Panel dosya düzenleyici (Engine) site dosyalarına yazabilir."
