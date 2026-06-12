#!/usr/bin/env bash
# Gelen posta (MX) + spam skoru (SPF/DKIM/DMARC) + Postfix haritaları.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

if [[ -f "${SCRIPT_DIR}/fix-mail-firewall.sh" ]]; then
  bash "${SCRIPT_DIR}/fix-mail-firewall.sh"
fi
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/resolve-paths.sh
source "$SCRIPT_DIR/lib/resolve-paths.sh"
PANEL_ROOT="${PANEL_ROOT:-$(resolve_panel_root)}"
STATE_DIR="${STATE_DIR:-$(resolve_panelze_home)/data/engine-state}"

install -m 755 "${SCRIPT_DIR}/../host/panelze-mail-dkim-sync.sh" /usr/local/sbin/panelze-mail-dkim-sync 2>/dev/null || true
bash "${SCRIPT_DIR}/ensure-engine-sudoers.sh" 2>/dev/null || true

if [[ -x /usr/local/sbin/panelze-mail-provision ]] && [[ -d "${STATE_DIR}/mail" ]]; then
  /usr/local/sbin/panelze-mail-provision "$STATE_DIR"
fi

if [[ -d "$PANEL_ROOT" ]]; then
  (cd "$PANEL_ROOT" && php artisan panelze:mail-dns-bootstrap --all)
fi

# PTR ile uyum — bounce/HELO sunucu kimliği (ör. mail.netkalan.com = Contabo rDNS)
SERVER_IP="$(hostname -I | awk '{print $1}')"
PTR_HOST="$(dig +short -x "${SERVER_IP}" 2>/dev/null | sed 's/\.$//')"
if [[ -n "$PTR_HOST" ]]; then
  postconf -e "myhostname=${PTR_HOST}"
fi
# Gmail vb. IPv6 PTR istemez; rDNS yoksa giden posta IPv4 üzerinden gitsin
postconf -e "inet_protocols=ipv4"
if [[ -n "$SERVER_IP" ]]; then
  postconf -e "smtp_bind_address=${SERVER_IP}"
fi
systemctl reload postfix >/dev/null 2>&1 || true

postmap /etc/postfix/virtual_mailbox_domains 2>/dev/null || true
postmap /etc/postfix/virtual_mailbox_maps 2>/dev/null || true
postmap /etc/postfix/virtual_alias_maps 2>/dev/null || true
systemctl reload bind9 2>/dev/null || systemctl reload named 2>/dev/null || true

echo "OK mail-deliverability"
