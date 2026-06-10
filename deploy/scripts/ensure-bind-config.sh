#!/usr/bin/env bash
# BIND'in panelze zone dosyalarini kullandigini dogrular (eski hostvim include devre disi).
set -euo pipefail

LOCAL_CONF="/etc/bind/named.conf.local"
PANELZE_SNIPPET="/etc/bind/named.conf.panelze-zones"

if [[ ! -f "$LOCAL_CONF" ]]; then
  echo "ensure-bind-config: $LOCAL_CONF yok, atlaniyor"
  exit 0
fi

if ! command -v named-checkconf >/dev/null 2>&1; then
  exit 0
fi

changed=0
if grep -q 'named.conf.hostvim-zones' "$LOCAL_CONF" 2>/dev/null; then
  sed -i 's|include "/etc/bind/named.conf.hostvim-zones";|// eski hostvim zones (devre disi)\ninclude "/etc/bind/named.conf.panelze-zones";|' \
    "$LOCAL_CONF"
  changed=1
  echo "ensure-bind-config: hostvim -> panelze include guncellendi"
fi

if ! grep -q 'named.conf.panelze-zones' "$LOCAL_CONF" 2>/dev/null; then
  cat >>"$LOCAL_CONF" <<EOF

// Panelze panel DNS
include "${PANELZE_SNIPPET}";
EOF
  changed=1
  echo "ensure-bind-config: panelze include eklendi"
fi

touch "$PANELZE_SNIPPET"
chmod 644 "$PANELZE_SNIPPET"

if named-checkconf >/dev/null 2>&1; then
  if command -v rndc >/dev/null 2>&1; then
    rndc reconfig >/dev/null 2>&1 || true
    rndc reload >/dev/null 2>&1 || systemctl reload named 2>/dev/null || systemctl reload bind9 2>/dev/null || true
  fi
fi

if [[ "$changed" -eq 1 ]]; then
  echo "ensure-bind-config: BIND yapilandirmasi guncellendi"
fi

exit 0
