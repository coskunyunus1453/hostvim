#!/usr/bin/env bash
# Geriye dönük uyumluluk — install-pro.sh kullanın.
set -euo pipefail
if [[ -n "${BASH_SOURCE[0]:-}" ]]; then
  _d="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
  if [[ -f "$_d/install-pro.sh" ]]; then
    exec bash "$_d/install-pro.sh"
  fi
fi
_BASE="${PANELZE_RAW_BASE:-https://raw.githubusercontent.com/coskunyunus1453/panelze/main/deploy/host}"
curl -fsSL "${_BASE}/install-pro.sh" | bash
