#!/usr/bin/env bash
# Pure-FTPd kurulumu + pasif FTP + UFW (panel FTP hesapları için).
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
  echo "panelze-ftp-stack-setup: root gerekli" >&2
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive

# Bozuk üçüncü parti apt kaynaklarını geçici olarak kenara al (örn. ookla).
mkdir -p /etc/apt/sources.list.d/disabled
shopt -s nullglob
for f in /etc/apt/sources.list.d/*ookla*; do
  mv "$f" /etc/apt/sources.list.d/disabled/ 2>/dev/null || true
done
shopt -u nullglob

apt-get update -qq
apt-get install -y -qq pure-ftpd pure-ftpd-common

PURE_CONF=/etc/pure-ftpd/conf
mkdir -p "$PURE_CONF" /etc/pure-ftpd/auth /etc/pure-ftpd

echo 'yes' >"$PURE_CONF/ChrootEveryone"
echo 'no' >"$PURE_CONF/NoAnonymous"
echo 'yes' >"$PURE_CONF/VerboseLog"
echo '39000 39999' >"$PURE_CONF/PassivePortRange"
PUBLIC_IP="$(curl -4 -fsS --max-time 8 ifconfig.me 2>/dev/null || curl -4 -fsS --max-time 8 icanhazip.com 2>/dev/null || echo '')"
[[ -n "$PUBLIC_IP" ]] && echo "$PUBLIC_IP" >"$PURE_CONF/ForcePassiveIP"
echo 'yes' >"$PURE_CONF/DontResolve"
echo '30' >"$PURE_CONF/MaxClientsNumber"
echo '10' >"$PURE_CONF/MaxClientsPerIP"
echo '100' >"$PURE_CONF/MinUID"
echo '0' >"$PURE_CONF/TLS" 2>/dev/null || true
echo '/etc/pure-ftpd/pureftpd.pdb' >"$PURE_CONF/PureDB"
touch /etc/pure-ftpd/pureftpd.passwd
chmod 600 /etc/pure-ftpd/pureftpd.passwd
ln -sfn ../conf/PureDB /etc/pure-ftpd/auth/50pure

if [[ -f /etc/default/pure-ftpd-common ]]; then
  sed -i 's/^STANDALONE_OR_INETD=.*/STANDALONE_OR_INETD=standalone/' /etc/default/pure-ftpd-common
fi
if grep -q '^ftp' /etc/inetd.conf 2>/dev/null; then
  sed -i 's/^ftp/#ftp/' /etc/inetd.conf || true
  systemctl restart openbsd-inetd >/dev/null 2>&1 || true
fi

if command -v ufw >/dev/null 2>&1; then
  ufw allow 21/tcp comment 'Panelze FTP' >/dev/null 2>&1 || true
  ufw allow 39000:39999/tcp comment 'Panelze FTP PASV' >/dev/null 2>&1 || true
fi

systemctl enable pure-ftpd >/dev/null 2>&1 || true
systemctl restart pure-ftpd

HERE="$(cd "$(dirname "$0")" && pwd)"
PROV="${HERE}/panelze-ftp-provision"
[[ -x "$PROV" ]] || PROV="/usr/local/sbin/panelze-ftp-provision"
if [[ -x "$PROV" ]]; then
  STATE_DIR="${PANELZE_FTP_STATE_DIR:-/var/www/hostvim/data/engine-state}"
  WEB_ROOT="${PANELZE_FTP_WEB_ROOT:-/var/www/hostvim/data/www}"
  if [[ -d "$STATE_DIR/ftp" ]]; then
    "$PROV" "$STATE_DIR" "$WEB_ROOT" || true
  fi
fi

echo "OK ftp-stack-setup"
