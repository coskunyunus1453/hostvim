#!/usr/bin/env bash
#
# Panelze — tek satır kurulum girişi (https://get.panelze.sh)
# Sunucuda: curl -fsSL https://get.panelze.sh | bash
#
# Bu dosyayı get.panelze.sh kökünde "index" veya default olarak sunun (nginx örnek):
#   root /var/www/panelze-get;
#   location = / { default_type application/x-sh; try_files /get.panelze.sh =404; }
# veya: return 302 https://raw.githubusercontent.com/.../install-community.sh;
#
set -euo pipefail

INSTALL_SCRIPT="${PANELZE_INSTALL_COMMUNITY_SCRIPT:-${PANELZE_INSTALL_SCRIPT_URL:-https://raw.githubusercontent.com/coskunyunus1453/panelze/main/deploy/host/install-community.sh}}"

if [[ "$(uname -s)" != "Linux" ]]; then
  echo "Panelze kurulumu yalnızca Linux (Debian/Ubuntu) sunucu içindir." >&2
  echo "SSH ile VPS'e bağlanıp orada çalıştırın: curl -fsSL https://get.panelze.sh | bash" >&2
  exit 1
fi

curl -fsSL "$INSTALL_SCRIPT" | bash
