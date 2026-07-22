#!/usr/bin/env bash
#
# Panelze - official one-line installer (https://get.panelze.com)
# On the server (Debian/Ubuntu, root/sudo):
#   curl -fsSL https://get.panelze.com | bash
#
set -euo pipefail

INSTALL_SCRIPT="${PANELZE_INSTALL_COMMUNITY_SCRIPT:-${PANELZE_INSTALL_SCRIPT_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-community.sh}}"

if [[ "$(uname -s)" != "Linux" ]]; then
  echo "Panelze install is for Linux (Debian/Ubuntu) servers only." >&2
  echo "SSH into your VPS, then run: curl -fsSL https://get.panelze.com | bash" >&2
  exit 1
fi

echo "==> Installing Panelze..."
curl -fsSL "$INSTALL_SCRIPT" | bash
