#!/usr/bin/env bash
# Hostvim güvenli güncelleme (veri korunur)
set -euo pipefail
export HOSTVIM_HOME="${HOSTVIM_HOME:-/var/www/hostvim}"
UPDATE_URL="${HOSTVIM_INSTALL_UPDATE_COMMUNITY_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update-community.sh}"
UPDATE_URL="${UPDATE_URL}?ts=$(date +%s)"
curl -fsSL "$UPDATE_URL" | bash
