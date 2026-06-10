#!/usr/bin/env bash
#
# Panelze — tek komut kurulum (Community + Pro aynı paket; lisans panelden).
#
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/panelze/main/deploy/host/install-panelze.sh" | bash
#
# İsteğe bağlı lisans:
#   PANELZE_LICENSE_KEY="hv_...." curl -fsSL "…/install-panelze.sh" | bash
#
set -euo pipefail

export APP_PROFILE=customer
export VENDOR_ENABLED=false
export ENFORCE_ADMIN_2FA="${ENFORCE_ADMIN_2FA:-false}"
export PANELZE_REPO_URL="${PANELZE_REPO_URL:-https://github.com/coskunyunus1453/panelze.git}"
export PANELZE_BRANCH="${PANELZE_BRANCH:-main}"
export PANELZE_AUTO_SYNC_GIT=1
export PANELZE_HOME="${PANELZE_HOME:-/var/www/panelze}"
export WITH_BIND_DNS="${WITH_BIND_DNS:-1}"

if ! declare -F panelze_source_install_mode_lib &>/dev/null; then
  for _lib_boot in \
    "$(dirname "${BASH_SOURCE[0]:-$0}")/lib/source-install-mode.sh" \
    "/var/www/panelze/deploy/host/lib/source-install-mode.sh" \
    "${PANELZE_HOME}/deploy/host/lib/source-install-mode.sh"; do
    if [[ -f "$_lib_boot" ]]; then
      # shellcheck source=lib/source-install-mode.sh
      source "$_lib_boot"
      break
    fi
  done
fi
if ! declare -F panelze_source_install_mode_lib &>/dev/null; then
  _raw_boot="https://raw.githubusercontent.com/coskunyunus1453/panelze/${PANELZE_BRANCH:-main}"
  _tmp_boot="$(mktemp)"
  curl -fsSL "${_raw_boot}/deploy/host/lib/source-install-mode.sh" -o "$_tmp_boot"
  # shellcheck source=/dev/null
  source "$_tmp_boot"
  rm -f "$_tmp_boot"
fi
panelze_source_install_mode_lib

if [[ "$(panelze_resolve_install_mode)" == "update" ]] && [[ "${PANELZE_FORCE_FULL_INSTALL:-0}" != "1" ]]; then
  echo "==> Mevcut kurulum algılandı; güvenli güncelleme modu"
  UPDATE_URL="${PANELZE_INSTALL_UPDATE_URL:-https://raw.githubusercontent.com/coskunyunus1453/panelze/main/deploy/host/install-update-panelze.sh}"
  UPDATE_URL="${UPDATE_URL}?ts=$(date +%s)"
  _tmp_up="$(mktemp)"
  curl -fsSL "$UPDATE_URL" -o "$_tmp_up"
  bash "$_tmp_up"
  rm -f "$_tmp_up"
  exit 0
fi

PANELZE_INSTALL_SCRIPT_URL="${PANELZE_INSTALL_SCRIPT_URL:-https://raw.githubusercontent.com/coskunyunus1453/panelze/main/deploy/host/install.sh}"
PANELZE_INSTALL_SCRIPT_URL="${PANELZE_INSTALL_SCRIPT_URL}?ts=$(date +%s)"
export PANELSAR_INSTALL_SCRIPT_URL="$PANELZE_INSTALL_SCRIPT_URL"
export PANELSAR_REPO_URL="$PANELZE_REPO_URL"
export PANELSAR_BRANCH="$PANELZE_BRANCH"

curl -fsSL "$PANELZE_INSTALL_SCRIPT_URL" | bash
