#!/usr/bin/env bash
#
# HostVim — tek komut: Panelze panel + satış sitesi (store) + entegrasyon
#
# Rehber: deploy/HOSTVIM-OPERATIONS.md
#
# Mac'ten (önerilen):
#   bash deploy/scripts/install-hostvim-full.sh
#
# Sıfır sunucu — panel de kurulsun:
#   HOSTVIM_INSTALL_PANEL=1 bash deploy/scripts/install-hostvim-full.sh
#
# Sunucuda (kod zaten sunucuda):
#   bash deploy/scripts/install-hostvim-full.sh --local
#
# Ortam değişkenleri:
#   HOSTVIM_SSH_KEY      SSH anahtarı (varsayılan: ~/.ssh/hostvim_aapanel)
#   HOSTVIM_SSH_HOST     root@207.180.237.13
#   PANELZE_HOME         /var/www/hostvim
#   PANEL_ROOT           $PANELZE_HOME/panel
#   STORE_ROOT           /var/www/hostvim/data/www/hostvim.com/public_html
#   STORE_DOMAIN         hostvim.com
#   PANEL_URL            https://207.180.237.13  (panel erişim URL)
#   HOSTVIM_INSTALL_PANEL=1   Panel yoksa install-production.sh çalıştır
#   HOSTVIM_STORE_SEED=1      Store db:seed zorla
#   HOSTVIM_SKIP_PANEL=1      Yalnızca store
#   HOSTVIM_SKIP_STORE=1      Yalnızca panel entegrasyonu
#   HOSTVIM_SKIP_APT=1        Panel kurulumunda apt atla (güncelleme)
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
# shellcheck source=lib/hostvim-common.sh
source "$SCRIPT_DIR/lib/hostvim-common.sh"

SSH_KEY="${HOSTVIM_SSH_KEY:-$HOME/.ssh/hostvim_aapanel}"
SSH_HOST="${HOSTVIM_SSH_HOST:-root@207.180.237.13}"
LOCAL_MODE=0

for arg in "$@"; do
  case "$arg" in
    --local) LOCAL_MODE=1 ;;
    --install-panel) HOSTVIM_INSTALL_PANEL=1 ;;
    --seed) HOSTVIM_STORE_SEED=1 ;;
    --help|-h)
      sed -n '2,30p' "$0"
      exit 0
      ;;
  esac
done

export HOSTVIM_REPO_ROOT="$REPO_ROOT"
hostvim_resolve_paths

run_remote() {
  if [[ "$LOCAL_MODE" == "1" ]]; then
    bash -c "$1"
  else
    ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_HOST" "$1"
  fi
}

panel_exists_remote() {
  run_remote "[[ -f '${PANEL_ROOT}/artisan' ]]"
}

if [[ "$LOCAL_MODE" != "1" ]]; then
  SSH_E='ssh -i '"$SSH_KEY"' -o StrictHostKeyChecking=no'

  if [[ "${HOSTVIM_INSTALL_PANEL:-0}" == "1" ]] && ! panel_exists_remote; then
    echo "==> Sıfır kurulum: tam repo sunucuya kopyalanıyor"
    hostvim_rsync_repo_for_panel "$REPO_ROOT" "${SSH_HOST}:${PANELZE_HOME}/" "$SSH_E"
    run_remote "
set -euo pipefail
export PANELZE_HOME='${PANELZE_HOME}'
export PANELZE_PUBLIC_HOST='${PANEL_PUBLIC_HOST}'
export PANELZE_APP_URL='${PANEL_URL}'
export WITH_MARIADB=1
export WITH_NODE_REPO=1
export SKIP_APT=\${HOSTVIM_SKIP_APT:-0}
cd '${PANELZE_HOME}'
bash deploy/bootstrap/install-production.sh
"
  elif [[ "${HOSTVIM_INSTALL_PANEL:-0}" == "1" ]]; then
    echo "==> Panel zaten kurulu; kod güncelleniyor"
    hostvim_rsync_repo_for_panel "$REPO_ROOT" "${SSH_HOST}:${PANELZE_HOME}/" "$SSH_E"
    run_remote "
set -euo pipefail
export PANELZE_HOME='${PANELZE_HOME}'
export PANELZE_UPDATE_ONLY=1
export SKIP_APT=1
cd '${PANELZE_HOME}'
bash deploy/bootstrap/install-production.sh
"
  fi

  hostvim_rsync_deploy_helpers "$REPO_ROOT" "$PANELZE_HOME" "$SSH_HOST" "$SSH_KEY"

  if [[ "${HOSTVIM_SKIP_STORE:-0}" != "1" ]]; then
    hostvim_rsync_store "$REPO_ROOT" "${SSH_HOST}:${STORE_ROOT}/" "$SSH_E"
  fi

  if [[ "${HOSTVIM_SKIP_PANEL:-0}" != "1" ]]; then
    hostvim_rsync_panel_integration "$REPO_ROOT" "$PANEL_ROOT" "$SSH_HOST" "$SSH_KEY"
  fi

  echo "==> Sunucuda kurulum adımları"
  run_remote "
set -euo pipefail
export HOSTVIM_REPO_ROOT='${PANELZE_HOME}'
export PANELZE_HOME='${PANELZE_HOME}'
export PANEL_ROOT='${PANEL_ROOT}'
export STORE_ROOT='${STORE_ROOT}'
export STORE_DOMAIN='${STORE_DOMAIN}'
export STORE_URL='${STORE_URL}'
export PANEL_URL='${PANEL_URL}'
export PANEL_PUBLIC_HOST='${PANEL_PUBLIC_HOST}'
export HOSTVIM_STORE_SEED='${HOSTVIM_STORE_SEED:-0}'
export HOSTVIM_SKIP_PANEL='${HOSTVIM_SKIP_PANEL:-0}'
export HOSTVIM_SKIP_STORE='${HOSTVIM_SKIP_STORE:-0}'
export HOSTVIM_SKIP_QUEUE='${HOSTVIM_SKIP_QUEUE:-0}'
source '${PANELZE_HOME}/deploy/scripts/lib/hostvim-common.sh'
hostvim_full_setup
"
else
  export HOSTVIM_REPO_ROOT="${HOSTVIM_REPO_ROOT:-$REPO_ROOT}"
  hostvim_full_setup
fi
