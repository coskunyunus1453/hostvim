#!/usr/bin/env bash
# Kurulum / güncelleme sonrası tek komut onarım (MySQL kullanıcıları + izinler + sağlık kontrolü).
#
# Kullanım (root):
#   PANEL_ROOT=/var/www/panelze/panel bash deploy/scripts/panelze-post-install.sh
#   MYSQL_ROOT_PASS='...' PANEL_ROOT=/var/www/panelze/panel bash deploy/scripts/panelze-post-install.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ -f "$SCRIPT_DIR/lib/panelze-deploy-common.sh" ]]; then
  # shellcheck source=lib/panelze-deploy-common.sh
  source "$SCRIPT_DIR/lib/panelze-deploy-common.sh"
elif [[ -f "${PANELZE_HOME:-/var/www/panelze}/deploy/scripts/lib/panelze-deploy-common.sh" ]]; then
  SCRIPT_DIR="${PANELZE_HOME:-/var/www/panelze}/deploy/scripts"
  # shellcheck source=/dev/null
  source "$SCRIPT_DIR/lib/panelze-deploy-common.sh"
else
  echo "Hata: panelze-deploy-common.sh bulunamadi." >&2
  exit 1
fi

PANEL_ROOT="${PANEL_ROOT:-$(panelze_resolve_home)/panel}"
PANELZE_HOME="$(panelze_resolve_home "$PANEL_ROOT")"
export PANEL_ROOT PANELZE_HOME

echo "==> Panelze kurulum sonrası onarım"
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
  panelze_run_artisan optimize:clear || true
  panelze_run_artisan panelze:repair-stack-installs --no-interaction || true
  panelze_run_artisan panelze:ensure-mail-stack --no-interaction || true
  panelze_run_artisan panelze:install-check --ping || true
fi

install_host_tool() {
  local base="$1"
  local src="${PANELZE_HOME}/deploy/host/panelze-${base}"
  if [[ ! -f "$src" ]]; then
    return 0
  fi
  install -m 755 "$src" "/usr/local/sbin/panelze-${base}"
  ln -sfn "/usr/local/sbin/panelze-${base}" "/usr/local/sbin/panelsar-${base}" 2>/dev/null || true
}

install_host_tool stack-install
install_host_tool mail-stack-setup.sh
install_host_tool mail-provision
install_host_tool bind-sync
install_host_tool nginx-vhost
install_host_tool security
install_host_tool terminal-root
install_host_tool php-ini
install_host_tool system-settings
install_host_tool node-pm2

if [[ "$(id -u)" -eq 0 ]]; then
  echo "==> engine sudoers (NOPASSWD)"
  bash "$SCRIPT_DIR/ensure-engine-sudoers.sh"
  if [[ "${WITH_BIND_DNS:-1}" == "1" ]] || [[ "${WITH_BIND_DNS:-1}" == "yes" ]]; then
    _BIND_SETUP="${PANELZE_HOME}/deploy/host/panelze-bind-setup.sh"
    if command -v named >/dev/null 2>&1 && { systemctl is-active named >/dev/null 2>&1 || systemctl is-active bind9 >/dev/null 2>&1; }; then
      echo "==> BIND9 DNS senkronu"
      /usr/local/sbin/panelze-bind-sync 2>/dev/null || true
    elif [[ -f "$_BIND_SETUP" ]]; then
      echo "==> BIND9 yetkili DNS kurulumu"
      PANELZE_HOME="$PANELZE_HOME" PANEL_ROOT="$PANEL_ROOT" bash "$_BIND_SETUP" || true
    fi
  fi
fi
systemctl restart panelze-engine 2>/dev/null || systemctl restart panelze-engine 2>/dev/null || true

echo ""
echo "Tamam. Sorun devam ederse:"
echo "  tail -50 $PANEL_ROOT/storage/logs/laravel.log"
echo "  MYSQL_ROOT_PASS='...' bash $SCRIPT_DIR/repair-mysql-users.sh"
