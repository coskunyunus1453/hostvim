#!/usr/bin/env bash
# Geriye dönük uyumluluk → install-hostvim.sh (HOSTVIM_LICENSE_KEY ile)
set -euo pipefail
curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-hostvim.sh" | bash
