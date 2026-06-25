#!/usr/bin/env bash
# BIND'in panelze zone dosyalarini kullandigini dogrular (eski hostvim include devre disi).
set -euo pipefail

LOCAL_CONF="/etc/bind/named.conf.local"
ZONES_DIR="/var/lib/bind/panelze/zones"
PANELZE_SNIPPET="${PANELZE_BIND_CONF_PATH:-/var/lib/bind/panelze/named.conf.panelze-zones}"
LEGACY_SNIPPET="/etc/bind/named.conf.panelze-zones"

if [[ ! -f "$LOCAL_CONF" ]]; then
  echo "ensure-bind-config: $LOCAL_CONF yok, atlaniyor"
  exit 0
fi

if ! command -v named-checkconf >/dev/null 2>&1; then
  exit 0
fi

mkdir -p "$(dirname "$PANELZE_SNIPPET")" "$ZONES_DIR"
chown bind:bind "$ZONES_DIR" 2>/dev/null || true
chmod 775 "$ZONES_DIR" 2>/dev/null || true

if [[ ! -f "$PANELZE_SNIPPET" && -f "$LEGACY_SNIPPET" && -r "$LEGACY_SNIPPET" ]]; then
  cp -a "$LEGACY_SNIPPET" "$PANELZE_SNIPPET" 2>/dev/null || true
fi

if ! touch "$PANELZE_SNIPPET" 2>/dev/null; then
  echo "ensure-bind-config: snippet yazilamadi: $PANELZE_SNIPPET" >&2
  exit 1
fi
chmod 644 "$PANELZE_SNIPPET" 2>/dev/null || true
chown bind:bind "$PANELZE_SNIPPET" 2>/dev/null || true

changed=0

# Eski /etc/bind snippet include → yazilabilir /var/lib yolu
if grep -q 'named.conf.hostvim-zones' "$LOCAL_CONF" 2>/dev/null; then
  sed -i 's|include "/etc/bind/named.conf.hostvim-zones";|// eski hostvim zones (devre disi)\ninclude "'"${PANELZE_SNIPPET}"'";|' \
    "$LOCAL_CONF"
  changed=1
  echo "ensure-bind-config: hostvim -> panelze include guncellendi"
elif grep -q 'named.conf.panelze-zones' "$LOCAL_CONF" 2>/dev/null && ! grep -qF "$PANELZE_SNIPPET" "$LOCAL_CONF" 2>/dev/null; then
  sed -i 's|include "/etc/bind/named.conf.panelze-zones";|include "'"${PANELZE_SNIPPET}"'";|' "$LOCAL_CONF"
  changed=1
  echo "ensure-bind-config: panelze snippet yolu /var/lib olarak guncellendi"
fi

if ! grep -qF "$PANELZE_SNIPPET" "$LOCAL_CONF" 2>/dev/null; then
  cat >>"$LOCAL_CONF" <<EOF

// Panelze panel DNS
include "${PANELZE_SNIPPET}";
EOF
  changed=1
  echo "ensure-bind-config: panelze include eklendi"
fi

# Eski hostvim snippet dosyası kalsa bile named.conf.local'dan kaldırılmış olmalı
if [[ -f /etc/bind/named.conf.hostvim-zones ]] && ! grep -q 'named.conf.hostvim-zones' "$LOCAL_CONF" 2>/dev/null; then
  if [[ ! -f /etc/bind/named.conf.hostvim-zones.disabled ]]; then
    cp -a /etc/bind/named.conf.hostvim-zones /etc/bind/named.conf.hostvim-zones.bak 2>/dev/null || true
  fi
fi

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
