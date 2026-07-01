#!/usr/bin/env bash
# HostVim store — storage/bootstrap izin onarımı.
# PanelKafes FPM (pk-hostvim-com) ile root/www-data artisan çakışmasını giderir.
#
# Kullanım (sunucuda root):
#   bash /var/www/hostvim/deploy/scripts/fix-store-permissions.sh
# Mac'ten:
#   bash deploy/scripts/fix-store-permissions.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/hostvim-common.sh
source "$SCRIPT_DIR/lib/hostvim-common.sh"

SSH_KEY="${HOSTVIM_SSH_KEY:-$HOME/.ssh/hostvim_aapanel}"
SSH_HOST="${HOSTVIM_SSH_HOST:-root@207.180.237.13}"

if [[ "${1:-}" == "--local" ]] || [[ "$(hostname -I 2>/dev/null)" == *"207.180.237.13"* ]]; then
  hostvim_resolve_paths
  hostvim_fix_store_permissions
  if [[ "$(id -u)" -eq 0 ]]; then
    hostvim_install_store_scheduler
    hostvim_install_store_queue
    cd "$STORE_ROOT"
    hostvim_run_as_store php artisan optimize:clear --no-interaction 2>/dev/null || true
  fi
  code="$(curl -sk -o /dev/null -w '%{http_code}' "${STORE_URL:-https://hostvim.com}/" 2>/dev/null || echo 000)"
  echo "HTTP test: $code"
  exit 0
fi

ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_HOST" \
  "bash ${PANELZE_HOME:-/var/www/hostvim}/deploy/scripts/fix-store-permissions.sh --local" \
  2>/dev/null || ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_HOST" bash -s -- --local <<'REMOTE'
set -euo pipefail
export PANELZE_HOME="${PANELZE_HOME:-/var/www/hostvim}"
export STORE_ROOT="${STORE_ROOT:-/var/www/hostvim/data/www/hostvim.com/public_html}"
source "$PANELZE_HOME/deploy/scripts/lib/hostvim-common.sh"
hostvim_fix_store_permissions
hostvim_install_store_queue
cd "$STORE_ROOT"
hostvim_run_as_store php artisan optimize:clear --no-interaction 2>/dev/null || true
code="$(curl -sk -o /dev/null -w '%{http_code}' https://hostvim.com/ 2>/dev/null || echo 000)"
echo "HTTP test: $code"
REMOTE
