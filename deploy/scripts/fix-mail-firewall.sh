#!/usr/bin/env bash
# Gelen/giden posta için SMTP ve IMAP portlarını açar (UFW + PANELZE-FW).
set -euo pipefail

PORTS=(25 143 465 587 993)
SEC="/usr/local/sbin/panelze-security"

for port in "${PORTS[@]}"; do
  if command -v ufw >/dev/null 2>&1; then
    ufw allow "${port}/tcp" comment 'Panelze mail' >/dev/null 2>&1 || true
  fi
  if [[ -x "$SEC" ]]; then
    "$SEC" firewall-rule-apply allow tcp "$port" any >/dev/null 2>&1 || true
  fi
done

if command -v ufw >/dev/null 2>&1; then
  ufw reload >/dev/null 2>&1 || true
fi

echo "OK mail-firewall (${PORTS[*]})"
