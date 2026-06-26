#!/usr/bin/env bash
# HostVim satış sitesi (store) — sunucuya rsync + kurulum.
# Mac'ten: bash deploy/scripts/deploy-store.sh
# Sunucuda: bash deploy/scripts/deploy-store.sh --local
#
# Tam kurulum (panel + store): bash deploy/scripts/install-hostvim-full.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
# shellcheck source=lib/hostvim-common.sh
source "$SCRIPT_DIR/lib/hostvim-common.sh"

SSH_KEY="${HOSTVIM_SSH_KEY:-$HOME/.ssh/hostvim_aapanel}"
SSH_HOST="${HOSTVIM_SSH_HOST:-root@207.180.237.13}"
LOCAL_MODE=0

if [[ "${1:-}" == "--local" ]]; then
  LOCAL_MODE=1
fi

export HOSTVIM_REPO_ROOT="$REPO_ROOT"
hostvim_resolve_paths

if [[ "$LOCAL_MODE" != "1" ]]; then
  SSH_E='ssh -i '"$SSH_KEY"' -o StrictHostKeyChecking=no'
  hostvim_rsync_deploy_helpers "$REPO_ROOT" "$PANELZE_HOME" "$SSH_HOST" "$SSH_KEY"
  hostvim_rsync_store "$REPO_ROOT" "${SSH_HOST}:${STORE_ROOT}/" "$SSH_E"
  hostvim_rsync_panel_integration "$REPO_ROOT" "$PANEL_ROOT" "$SSH_HOST" "$SSH_KEY"
fi

if [[ "$LOCAL_MODE" == "1" ]]; then
  export HOSTVIM_SKIP_PANEL=0
  export HOSTVIM_SKIP_STORE=0
  hostvim_full_setup
else
  ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_HOST" "
set -euo pipefail
export HOSTVIM_REPO_ROOT='${PANELZE_HOME}'
export PANELZE_HOME='${PANELZE_HOME}'
export PANEL_ROOT='${PANEL_ROOT}'
export STORE_ROOT='${STORE_ROOT}'
export STORE_DOMAIN='${STORE_DOMAIN}'
export STORE_URL='${STORE_URL}'
export PANEL_URL='${PANEL_URL}'
export PANEL_PUBLIC_HOST='${PANEL_PUBLIC_HOST}'
source '${PANELZE_HOME}/deploy/scripts/lib/hostvim-common.sh' 2>/dev/null || source '${REPO_ROOT}/deploy/scripts/lib/hostvim-common.sh'
hostvim_full_setup
"
fi

echo ""
echo "Tamam. ${STORE_URL}/ — admin: ${STORE_URL}/admin"
