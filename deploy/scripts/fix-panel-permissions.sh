#!/usr/bin/env bash
# Panelze panel — storage / bootstrap / public/admin izinleri (Linux + macOS).
# Müşteri sunucuda veya yerelde tek komutla çalıştırır; OS ve web kullanıcı adını kendisi seçer.
#
# Kullanım:
#   bash deploy/scripts/fix-panel-permissions.sh /path/to/panel
#   PANEL_ROOT=/custom/panel bash deploy/scripts/fix-panel-permissions.sh
#
# Linux üretim (örnek):
#   RUN_USER=www-data RUN_GROUP=www-data sudo bash deploy/scripts/fix-panel-permissions.sh /var/www/panelze/panel
#
# macOS / XAMPP (örnek):
#   bash deploy/scripts/fix-panel-permissions.sh /Applications/XAMPP/xamppfiles/htdocs/panelze/panel
#
set -euo pipefail

PANEL_ROOT="${1:-${PANEL_ROOT:-}}"
if [[ -z "$PANEL_ROOT" ]] || [[ ! -f "$PANEL_ROOT/artisan" ]]; then
  echo "Kullanım: bash fix-panel-permissions.sh /path/to/panel" >&2
  echo "  (panel dizininde artisan dosyası olmalı)" >&2
  exit 1
fi

PANEL_ROOT="$(cd "$PANEL_ROOT" && pwd)"
OS="$(uname -s)"

# macOS XAMPP: Apache httpd.conf genelde "User daemon" / "Group daemon" — storage kullanıcıya
# verilirse Laravel view/cache yazılamaz (500: Permission denied).
if [[ "$OS" == "Darwin" ]] && [[ -d "/Applications/XAMPP/xamppfiles" ]]; then
  OWNER="${RUN_USER:-daemon}"
  GROUP="${RUN_GROUP:-daemon}"
elif [[ "$OS" == "Darwin" ]]; then
  OWNER="${RUN_USER:-$(id -un)}"
  GROUP="${RUN_GROUP:-$(id -gn)}"
else
  OWNER="${RUN_USER:-www-data}"
  GROUP="${RUN_GROUP:-$OWNER}"
fi

# Dizinleri oluştur (Laravel + panel onarımı public/admin)
mkdir -p \
  "$PANEL_ROOT/storage/app/public" \
  "$PANEL_ROOT/storage/app/private" \
  "$PANEL_ROOT/storage/framework/cache/data" \
  "$PANEL_ROOT/storage/framework/sessions" \
  "$PANEL_ROOT/storage/framework/views" \
  "$PANEL_ROOT/storage/logs" \
  "$PANEL_ROOT/bootstrap/cache" \
  "$PANEL_ROOT/public/admin"

run_priv() {
  if [[ "$(id -u)" -eq 0 ]]; then
    "$@"
  else
    sudo "$@"
  fi
}

echo "==> Panel: $PANEL_ROOT"
echo "==> Hedef sahiplik: $OWNER:$GROUP ($OS)"

run_priv chown -R "$OWNER:$GROUP" "$PANEL_ROOT/storage" "$PANEL_ROOT/bootstrap/cache" "$PANEL_ROOT/public/admin"
run_priv chmod -R ug+rwX "$PANEL_ROOT/storage" "$PANEL_ROOT/bootstrap/cache" "$PANEL_ROOT/public/admin"

# Admin SPA: /admin/assets ve /admin/index.html (panel doğrula/onar)
ADMIN_SPA_HELPER="/usr/local/sbin/panelze-fix-admin-spa"
if [[ -x "$ADMIN_SPA_HELPER" ]]; then
  echo "==> Admin SPA symlinkleri (panelze-fix-admin-spa)"
  env RUN_USER="$OWNER" RUN_GROUP="$GROUP" "$ADMIN_SPA_HELPER" "$PANEL_ROOT"
elif [[ -f "$PANEL_ROOT/../deploy/host/panelze-fix-admin-spa" ]]; then
  echo "==> Admin SPA symlinkleri (repo helper)"
  env RUN_USER="$OWNER" RUN_GROUP="$GROUP" bash "$PANEL_ROOT/../deploy/host/panelze-fix-admin-spa" "$PANEL_ROOT"
fi

# macOS XAMPP: Apache (daemon) ile terminalden `php artisan` aynı dizinlere yazsın.
# Aksi halde compiled view (storage/framework/views) 500: Permission denied üretir.
if [[ "$OS" == "Darwin" ]] && [[ -d "/Applications/XAMPP/xamppfiles" ]]; then
  if chmod -R 777 "$PANEL_ROOT/storage" "$PANEL_ROOT/bootstrap/cache" 2>/dev/null; then
    echo "==> XAMPP: storage + bootstrap/cache yazilabilir (777, yerel gelistirme)"
  else
    run_priv chmod -R 777 "$PANEL_ROOT/storage" "$PANEL_ROOT/bootstrap/cache"
    echo "==> XAMPP: storage + bootstrap/cache yazilabilir (777, sudo)"
  fi
fi

echo "Tamam."
