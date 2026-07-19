#!/usr/bin/env bash
# HostVim store — storage izinleri, cron, queue ve HTTP doğrulama.
# PanelKafes FPM (pk-hostvim-com) ile root/www-data artisan çakışmasını giderir.
#
# Mac'ten:
#   bash deploy/scripts/fix-store-permissions.sh
# Sunucuda (root):
#   bash /var/www/hostvim/deploy/scripts/fix-store-permissions.sh --local
#
# Ayrıntılı rehber: deploy/HOSTVIM-OPERATIONS.md
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/hostvim-common.sh
source "$SCRIPT_DIR/lib/hostvim-common.sh"

SSH_KEY="${HOSTVIM_SSH_KEY:-$HOME/.ssh/hostvim_aapanel}"
SSH_HOST="${HOSTVIM_SSH_HOST:-root@207.180.237.13}"

_run_local() {
  hostvim_resolve_paths
  if [[ "${1:-}" == "--guard-only" ]]; then
    hostvim_store_guard
    exit $?
  fi
  hostvim_finalize_store
}

if [[ "${1:-}" == "--local" ]]; then
  _run_local "${2:-}"
  exit $?
fi

if [[ "${1:-}" == "--guard-only" ]]; then
  ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_HOST" \
    "bash ${PANELZE_HOME:-/var/www/hostvim}/deploy/scripts/fix-store-permissions.sh --local --guard-only"
  exit $?
fi

ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_HOST" \
  "bash ${PANELZE_HOME:-/var/www/hostvim}/deploy/scripts/fix-store-permissions.sh --local"
