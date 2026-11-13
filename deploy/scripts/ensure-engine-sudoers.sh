#!/usr/bin/env bash
# www-data → engine betikleri için şifresiz sudo (NOPASSWD).
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
  echo "ensure-engine-sudoers: root gerekli" >&2
  exit 1
fi

SUDOERS="/etc/sudoers.d/panelze-engine"

# Her deploy'da tam liste yazılır (eksik kural / eski marka satırları kalmasın).
cat >"$SUDOERS" <<'SUDOERS'
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-nginx-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-nginx-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-apache-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-apache-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-ols-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-ols-vhost
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-stack-install
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-stack-install
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-mail-provision
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-mail-dkim-sync
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-ftp-provision
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
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-bind-sync
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-fix-admin-spa
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-fix-hosting-perms
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelsar-fix-hosting-perms
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-site-cage
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-site-exec
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-configure-roundcube-ssl
www-data ALL=(root) NOPASSWD: /usr/local/sbin/panelze-ai-exec
SUDOERS

chmod 440 "$SUDOERS"
visudo -cf "$SUDOERS"
echo "OK engine sudoers"

# PHP-FPM ProtectSystem=full → /etc ROFS; host araçları için drop-in.
_DROPIN_LIB="$(cd "$(dirname "$0")" && pwd)/lib/install-php-fpm-hosttools-dropin.sh"
if [[ -f "$_DROPIN_LIB" ]]; then
  # shellcheck source=lib/install-php-fpm-hosttools-dropin.sh
  source "$_DROPIN_LIB"
  install_php_fpm_hosttools_dropin "$(cd "$(dirname "$0")/../.." && pwd)" || true
fi
