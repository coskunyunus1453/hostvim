#!/usr/bin/env bash
# Panelze — BIND9 yetkili DNS (panel DNS kayıtları ile senkron).
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

if [[ "$(id -u)" -ne 0 ]]; then
  echo "panelze-bind-setup: root ile çalıştırılmalı" >&2
  exit 1
fi

PANELZE_HOME="${PANELZE_HOME:-/var/www/panelze}"
PANEL_ROOT="${PANEL_ROOT:-${PANELZE_HOME}/panel}"
ZONES_DIR="/var/lib/bind/panelze/zones"
CONF_SNIPPET="/etc/bind/named.conf.panelze-zones"
OPTIONS_FILE="/etc/bind/named.conf.options"

echo "==> BIND9 kurulumu..."
apt-get update -qq
apt-get install -y -qq bind9 bind9utils bind9-dnsutils

mkdir -p "${ZONES_DIR}"
chown bind:bind "${ZONES_DIR}"
chmod 775 "${ZONES_DIR}"

if [[ -f "${OPTIONS_FILE}" ]]; then
  cp -a "${OPTIONS_FILE}" "${OPTIONS_FILE}.bak-panelze" 2>/dev/null || true
  sed -i \
    -e 's/listen-on port 53 { [^}]*};/listen-on port 53 { any; };/' \
    -e 's/listen-on-v6 port 53 { [^}]*};/listen-on-v6 port 53 { any; };/' \
    "${OPTIONS_FILE}" || true
  if grep -q 'allow-query' "${OPTIONS_FILE}"; then
    sed -i 's/allow-query { [^}]*};/allow-query { any; };/' "${OPTIONS_FILE}" || true
  fi
  sed -i 's/recursion yes;/recursion no;/' "${OPTIONS_FILE}" || true
fi

if grep -q 'named.conf.hostvim-zones' /etc/bind/named.conf.local 2>/dev/null; then
  sed -i 's|include "/etc/bind/named.conf.hostvim-zones";|// eski hostvim zones\ninclude "/etc/bind/named.conf.panelze-zones";|' \
    /etc/bind/named.conf.local
fi
if ! grep -q 'named.conf.panelze-zones' /etc/bind/named.conf.local 2>/dev/null; then
  cat >>/etc/bind/named.conf.local <<EOF

// Panelze panel DNS
include "${CONF_SNIPPET}";
EOF
fi

install -d -m 755 /usr/local/sbin
_HERE="$(cd "$(dirname "$0")" && pwd)"
if [[ -f "${_HERE}/panelze-bind-sync" ]]; then
  install -m 755 "${_HERE}/panelze-bind-sync" /usr/local/sbin/panelze-bind-sync
  ln -sfn /usr/local/sbin/panelze-bind-sync /usr/local/sbin/panelze-bind-sync
fi

if [[ -f "${PANELZE_HOME}/deploy/scripts/ensure-engine-sudoers.sh" ]]; then
  bash "${PANELZE_HOME}/deploy/scripts/ensure-engine-sudoers.sh"
fi

# named-checkconf include dosyası mevcut olmalı (zone'lar sync ile dolar)
touch "${CONF_SNIPPET}"
chmod 644 "${CONF_SNIPPET}"
printf '%s\n' '// Panelze: panelze:sync-bind-dns ile doldurulur' >"${CONF_SNIPPET}"

named-checkconf
BIND_UNIT="named"
if ! systemctl list-unit-files named.service >/dev/null 2>&1; then
  BIND_UNIT="bind9"
fi
systemctl enable "${BIND_UNIT}"
systemctl restart "${BIND_UNIT}"

if systemctl is-active "${BIND_UNIT}" >/dev/null 2>&1; then
  echo "BIND servisi: ${BIND_UNIT} aktif"
else
  echo "UYARI: BIND servisi başlatılamadı (${BIND_UNIT})" >&2
  journalctl -u "${BIND_UNIT}" -n 20 --no-pager >&2 || true
fi

if command -v ufw >/dev/null 2>&1 && ufw status 2>/dev/null | grep -q 'Status: active'; then
  ufw allow 53/tcp comment 'BIND DNS' || true
  ufw allow 53/udp comment 'BIND DNS' || true
fi

if [[ -f "${PANEL_ROOT}/artisan" ]]; then
  echo "==> İlk zone senkronu..."
  /usr/local/sbin/panelze-bind-sync || echo "UYARI: ilk BIND sync başarısız — panelde kayıt ekleyip tekrar deneyin" >&2
fi

SERVER_IP="$(hostname -I 2>/dev/null | awk '{print $1}')"
NS_HOST="$(hostname -f 2>/dev/null || hostname)"
echo ""
echo "=== BIND9 hazır (mail-stack-webmail) ==="
echo "Nameserver önerisi (registrar'da NS + glue):"
echo "  ns1.${NS_HOST#*.}  →  ${SERVER_IP}"
echo "  ns2.${NS_HOST#*.}  →  ${SERVER_IP}"
echo "  veya NS: ${NS_HOST}"
echo ""
echo "Panel DNS kaydı ekledikten sonra otomatik senkron olur."
echo "Manuel: php artisan panelze:sync-bind-dns"
echo "OK bind9"
