#!/usr/bin/env bash
# Kurulum / güncelleme sonrası tek komut onarım (MySQL kullanıcıları + izinler + sağlık kontrolü).
#
# Kullanım (root):
#   PANEL_ROOT=/var/www/hostvim/panel bash deploy/scripts/hostvim-post-install.sh
#   MYSQL_ROOT_PASS='...' PANEL_ROOT=/var/www/hostvim/panel bash deploy/scripts/hostvim-post-install.sh
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

PANEL_ROOT="${PANEL_ROOT:-$(hostvim_resolve_hostvim_home)/panel}"
HOSTVIM_HOME="$(hostvim_resolve_hostvim_home "$PANEL_ROOT")"
export PANEL_ROOT HOSTVIM_HOME

echo "==> Hostvim kurulum sonrası onarım"
echo "    Panel: $PANEL_ROOT"

if grep -q '^DB_CONNECTION=mysql' "$PANEL_ROOT/.env" 2>/dev/null || \
   grep -q '^DB_CONNECTION=mariadb' "$PANEL_ROOT/.env" 2>/dev/null; then
  bash "$SCRIPT_DIR/repair-mysql-users.sh"
else
  echo "==> MySQL atlandı (sqlite veya DB_CONNECTION farklı)"
fi

bash "$SCRIPT_DIR/fix-panel-permissions.sh" "$PANEL_ROOT"
bash "$SCRIPT_DIR/fix-hosting-permissions.sh"

if [[ -f "$PANEL_ROOT/artisan" ]]; then
  hostvim_run_artisan optimize:clear || true
  hostvim_run_artisan hostvim:install-check --ping || true
fi

echo ""
echo "Tamam. Sorun devam ederse:"
echo "  tail -50 $PANEL_ROOT/storage/logs/laravel.log"
echo "  MYSQL_ROOT_PASS='...' bash $SCRIPT_DIR/repair-mysql-users.sh"
