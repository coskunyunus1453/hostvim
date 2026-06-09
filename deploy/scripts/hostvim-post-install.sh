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

PANEL_ROOT="${PANEL_ROOT:-$(hostvim_resolve_hostvim_home)/panel}"
PANELZE_HOME="$(hostvim_resolve_hostvim_home "$PANEL_ROOT")"
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
  hostvim_run_artisan optimize:clear || true
  hostvim_run_artisan panelze:repair-stack-installs --no-interaction || true
  hostvim_run_artisan panelze:ensure-mail-stack --no-interaction || true
  hostvim_run_artisan panelze:install-check --ping || true
fi

install_host_tool() {
  local base="$1"
  local src=""
  if [[ -f "${PANELZE_HOME}/deploy/host/hostvim-${base}" ]]; then
    src="${PANELZE_HOME}/deploy/host/hostvim-${base}"
  elif [[ -f "${PANELZE_HOME}/deploy/host/panelze-${base}" ]]; then
    src="${PANELZE_HOME}/deploy/host/panelze-${base}"
  else
    return 0
  fi
  install -m 755 "$src" "/usr/local/sbin/hostvim-${base}"
  ln -sfn "/usr/local/sbin/hostvim-${base}" "/usr/local/sbin/panelze-${base}"
  ln -sfn "/usr/local/sbin/hostvim-${base}" "/usr/local/sbin/panelsar-${base}" 2>/dev/null || true
}

install_host_tool stack-install
install_host_tool mail-stack-setup.sh
install_host_tool mail-provision
install_host_tool system-settings
install_host_tool node-pm2

# Sudoers (mail provision + stack)
if [[ -f /etc/sudoers.d/panelze-engine ]]; then
  if ! grep -q 'hostvim-mail-provision' /etc/sudoers.d/panelze-engine 2>/dev/null; then
    echo 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/hostvim-mail-provision' >>/etc/sudoers.d/panelze-engine
    echo 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-mail-provision' >>/etc/sudoers.d/panelze-engine
    echo 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/hostvim-stack-install' >>/etc/sudoers.d/panelze-engine
  fi
if [[ -f /etc/sudoers.d/panelze-engine ]]; then
  if ! grep -q 'panelze-system-settings' /etc/sudoers.d/panelze-engine 2>/dev/null; then
    echo 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-system-settings' >>/etc/sudoers.d/panelze-engine
    echo 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-system-settings' >>/etc/sudoers.d/panelze-engine
  fi
  if ! grep -q 'panelze-node-pm2' /etc/sudoers.d/panelze-engine 2>/dev/null; then
    echo 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-node-pm2' >>/etc/sudoers.d/panelze-engine
    echo 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-node-pm2' >>/etc/sudoers.d/panelze-engine
  fi
  chmod 440 /etc/sudoers.d/panelze-engine
  visudo -cf /etc/sudoers.d/panelze-engine >/dev/null 2>&1 || true
  echo "==> sudoers: panelze-system-settings / node-pm2 güncellendi"
fi
systemctl restart panelze-engine 2>/dev/null || true

echo ""
echo "Tamam. Sorun devam ederse:"
echo "  tail -50 $PANEL_ROOT/storage/logs/laravel.log"
echo "  MYSQL_ROOT_PASS='...' bash $SCRIPT_DIR/repair-mysql-users.sh"
