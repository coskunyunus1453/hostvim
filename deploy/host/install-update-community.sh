#!/usr/bin/env bash
#
# Community / Freemium — güvenli güncelleme (siteler + veritabanları korunur).
#
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update-community.sh" | sudo bash
#
set -euo pipefail

export APP_PROFILE=customer
export VENDOR_ENABLED=false
export ENFORCE_ADMIN_2FA="${ENFORCE_ADMIN_2FA:-false}"
export HOSTVIM_REPO_URL="${HOSTVIM_REPO_URL:-https://github.com/coskunyunus1453/hostvim.git}"
export HOSTVIM_BRANCH="${HOSTVIM_BRANCH:-main}"

HOSTVIM_HOME="${HOSTVIM_HOME:-/var/www/hostvim}"
export HOSTVIM_UPDATE_ONLY=1 RESET_PANEL_DB=0 HOSTVIM_FRESH_INSTALL=0 CLEAN_HOSTING_STATE_ON_RESET=0
export HOSTVIM_PRESERVE_ADMIN_PASSWORD="${HOSTVIM_PRESERVE_ADMIN_PASSWORD:-1}"

if [[ -f "$HOSTVIM_HOME/deploy/host/install-update.sh" ]]; then
  exec bash "$HOSTVIM_HOME/deploy/host/install-update.sh"
fi

UPDATE_URL="${HOSTVIM_INSTALL_UPDATE_SCRIPT_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update.sh}"
_TMP="$(mktemp)"
curl -fsSL "${UPDATE_URL}?ts=$(date +%s)" -o "$_TMP"
bash "$_TMP"
rm -f "$_TMP"
