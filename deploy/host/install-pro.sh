#!/usr/bin/env bash
# Geriye dönük uyumluluk → install-panelze.sh (PANELZE_LICENSE_KEY ile)
set -euo pipefail
curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/panelze/main/deploy/host/install-panelze.sh" | bash
