#!/usr/bin/env bash
# Geriye dönük uyumluluk → tek komut install-hostvim.sh
set -euo pipefail
curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-hostvim.sh" | bash
