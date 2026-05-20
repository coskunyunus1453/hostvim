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

UPDATE_URL="${HOSTVIM_INSTALL_UPDATE_SCRIPT_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update.sh}"
UPDATE_URL="${UPDATE_URL}?ts=$(date +%s)"

curl -fsSL "$UPDATE_URL" | bash
