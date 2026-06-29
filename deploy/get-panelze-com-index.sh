#!/usr/bin/env bash
#
# Panelze — tek satır kurulum girişi (https://get.panelze.com)
# Sunucuda (Debian/Ubuntu, root/sudo): curl -fsSL https://get.panelze.com | bash
#
set -euo pipefail

INSTALL_SCRIPT="${PANELZE_INSTALL_COMMUNITY_SCRIPT:-${PANELZE_INSTALL_SCRIPT_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-community.sh}}"

if [[ "$(uname -s)" != "Linux" ]]; then
  echo "Panelze kurulumu yalnizca Linux (Debian/Ubuntu) sunucu icindir." >&2
  echo "SSH ile VPS'e baglanip orada calistirin: curl -fsSL https://get.panelze.com | bash" >&2
  exit 1
fi

curl -fsSL "$INSTALL_SCRIPT" | bash
