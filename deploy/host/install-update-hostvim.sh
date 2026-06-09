#!/usr/bin/env bash
# Panelze güvenli güncelleme (veri korunur)
set -euo pipefail
export PANELZE_HOME="${PANELZE_HOME:-/var/www/panelze}"
UPDATE_URL="${PANELZE_INSTALL_UPDATE_COMMUNITY_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update-community.sh}"
UPDATE_URL="${UPDATE_URL}?ts=$(date +%s)"
curl -fsSL "$UPDATE_URL" | bash
