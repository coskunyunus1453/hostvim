#!/usr/bin/env bash
#
# Hostvim — Pro kurulum (lisanslı: tam özellik seti). Lisans ve panel müşterileri merkezi (ör. hostvim.com) üzerinden yönetilir.
#
# Satın alma sonrası verilen anahtarı kurulumdan önce veya satırda verin:
#   HOSTVIM_LICENSE_KEY="hv_...." curl -fsSL "…/install-pro.sh" | bash
#
# Markdown'dan kopyalarken satır başına "* " eklemeyin. Güvenli: cd /tmp && curl … | bash
#
# İlk kurulum:
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-pro.sh" | bash
#
# Güncelleme (veri korunur):
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update-pro.sh" | bash
#
# Community: deploy/host/install-community.sh | install-update-community.sh
#
set -euo pipefail

export APP_PROFILE=customer
export VENDOR_ENABLED=false
export ENFORCE_ADMIN_2FA="${ENFORCE_ADMIN_2FA:-false}"
export HOSTVIM_REPO_URL="${HOSTVIM_REPO_URL:-https://github.com/coskunyunus1453/hostvim.git}"
export HOSTVIM_BRANCH="${HOSTVIM_BRANCH:-main}"
export HOSTVIM_AUTO_SYNC_GIT=1
export HOSTVIM_HOME="${HOSTVIM_HOME:-/var/www/hostvim}"

if ! declare -F hostvim_source_install_mode_lib &>/dev/null; then
  for _lib_boot in \
    "$(dirname "${BASH_SOURCE[0]:-$0}")/lib/source-install-mode.sh" \
    "/var/www/hostvim/deploy/host/lib/source-install-mode.sh" \
    "${HOSTVIM_HOME}/deploy/host/lib/source-install-mode.sh"; do
    if [[ -f "$_lib_boot" ]]; then
      # shellcheck source=lib/source-install-mode.sh
      source "$_lib_boot"
      break
    fi
  done
fi
if ! declare -F hostvim_source_install_mode_lib &>/dev/null; then
  _raw_boot="https://raw.githubusercontent.com/coskunyunus1453/hostvim/${HOSTVIM_BRANCH:-main}"
  _tmp_boot="$(mktemp)"
  curl -fsSL "${_raw_boot}/deploy/host/lib/source-install-mode.sh" -o "$_tmp_boot"
  # shellcheck source=/dev/null
  source "$_tmp_boot"
  rm -f "$_tmp_boot"
fi
hostvim_source_install_mode_lib

if [[ "$(hostvim_resolve_install_mode)" == "update" ]] && [[ "${HOSTVIM_FORCE_FULL_INSTALL:-0}" != "1" ]]; then
  echo "==> Mevcut kurulum algılandı; güvenli güncelleme modu (install-update-pro.sh)"
  UPDATE_URL="${HOSTVIM_INSTALL_UPDATE_PRO_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update-pro.sh}"
  UPDATE_URL="${UPDATE_URL}?ts=$(date +%s)"
  _tmp_up="$(mktemp)"
  curl -fsSL "$UPDATE_URL" -o "$_tmp_up"
  bash "$_tmp_up"
  rm -f "$_tmp_up"
  exit 0
fi

HOSTVIM_INSTALL_SCRIPT_URL="${HOSTVIM_INSTALL_SCRIPT_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install.sh}"
HOSTVIM_INSTALL_SCRIPT_URL="${HOSTVIM_INSTALL_SCRIPT_URL}?ts=$(date +%s)"
export PANELSAR_INSTALL_SCRIPT_URL="$HOSTVIM_INSTALL_SCRIPT_URL"
export PANELSAR_REPO_URL="$HOSTVIM_REPO_URL"
export PANELSAR_BRANCH="$HOSTVIM_BRANCH"

curl -fsSL "$HOSTVIM_INSTALL_SCRIPT_URL" | bash
