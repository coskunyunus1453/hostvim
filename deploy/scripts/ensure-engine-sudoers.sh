#!/usr/bin/env bash
# www-data → engine betikleri için şifresiz sudo (NOPASSWD).
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
  echo "ensure-engine-sudoers: root gerekli" >&2
  exit 1
fi

SUDOERS="/etc/sudoers.d/panelze-engine"

ensure_line() {
  local line="$1"
  if [[ ! -f "$SUDOERS" ]] || ! grep -qF "$line" "$SUDOERS" 2>/dev/null; then
    echo "$line" >>"$SUDOERS"
  fi
}

if [[ ! -f "$SUDOERS" ]]; then
  cat >"$SUDOERS" <<'SUDOERS'
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-nginx-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-nginx-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/hostvim-stack-install
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-stack-install
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-stack-install
www-data ALL=(root) NOPASSWD: /usr/local/sbin/hostvim-mail-provision
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-mail-provision
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-terminal-root
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-terminal-root
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-php-ini
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-php-ini
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-security
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-security
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-system-settings
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-system-settings
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-panel-update
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-node-pm2
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-node-pm2
SUDOERS
else
  ensure_line 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/hostvim-stack-install'
  ensure_line 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-stack-install'
  ensure_line 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-stack-install'
  ensure_line 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/hostvim-mail-provision'
  ensure_line 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-mail-provision'
  ensure_line 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-system-settings'
  ensure_line 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-system-settings'
  ensure_line 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-node-pm2'
  ensure_line 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-node-pm2'
fi

chmod 440 "$SUDOERS"
visudo -cf "$SUDOERS"
echo "OK engine sudoers"
