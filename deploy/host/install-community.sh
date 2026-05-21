#!/usr/bin/env bash
#
# Hostvim — Community / Freemium kurulum (tek sunucu, barındırma paneli).
#
# Markdown'dan kopyalarken satır başına "* " EKLEMEYİN; kabuk * ile dosya adı genişletmesi komutu bozar.
# Güvenli: cd /tmp && curl -fsSL "…/install-community.sh" | bash
#
# Önerilen tek komut: deploy/host/install-hostvim.sh
# Geriye dönük:
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-community.sh" | bash
#
# Güncelleme (siteler + DB korunur — önerilen tekrar çalıştırma):
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update-community.sh" | bash
#
# Pro (lisanslı): deploy/host/install-pro.sh | install-update-pro.sh
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

exec bash -c 'curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-hostvim.sh" | bash'
