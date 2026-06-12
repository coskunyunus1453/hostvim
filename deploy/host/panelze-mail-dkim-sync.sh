#!/usr/bin/env bash
# engine-state/mail/*.json alan adları için OpenDKIM anahtarı + key/signing table.
set -euo pipefail

STATE_DIR="${1:-/var/www/panelze/data/engine-state}"
MAIL_DIR="${STATE_DIR}/mail"
KEYS_ROOT="/etc/opendkim/keys"
KEY_TABLE="/etc/opendkim/key.table"
SIGN_TABLE="/etc/opendkim/signing.table"
TRUSTED="/etc/opendkim/trusted.hosts"

if [[ ! -d "$MAIL_DIR" ]]; then
  echo "panelze-mail-dkim-sync: mail dizini yok: $MAIL_DIR" >&2
  exit 0
fi

install -d -m 0750 -o opendkim -g opendkim "$KEYS_ROOT"

domains=()
for f in "$MAIL_DIR"/*.json; do
  [[ -f "$f" ]] || continue
  dom="$(basename "$f" .json)"
  [[ "$dom" =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ ]] || continue
  domains+=("$dom")
done

if [[ ${#domains[@]} -eq 0 ]]; then
  echo "panelze-mail-dkim-sync: domain yok"
  exit 0
fi

for dom in "${domains[@]}"; do
  dom_dir="${KEYS_ROOT}/${dom}"
  install -d -m 0750 -o opendkim -g opendkim "$dom_dir"
  if [[ ! -f "${dom_dir}/default.private" ]]; then
    opendkim-genkey -b 2048 -d "$dom" -D "$dom_dir" -s default -v
    chown -R opendkim:opendkim "$dom_dir"
    chmod 640 "${dom_dir}/default.private" 2>/dev/null || true
  fi
done

{
  for dom in "${domains[@]}"; do
    echo "default._domainkey.${dom} ${dom}:default:${KEYS_ROOT}/${dom}/default.private"
  done
} >"${KEY_TABLE}.tmp"
chown opendkim:opendkim "${KEY_TABLE}.tmp"
chmod 640 "${KEY_TABLE}.tmp"
mv "${KEY_TABLE}.tmp" "$KEY_TABLE"

{
  for dom in "${domains[@]}"; do
    echo "*@${dom} default._domainkey.${dom}"
  done
} >"${SIGN_TABLE}.tmp"
chown opendkim:opendkim "${SIGN_TABLE}.tmp"
chmod 640 "${SIGN_TABLE}.tmp"
mv "${SIGN_TABLE}.tmp" "$SIGN_TABLE"

{
  echo "127.0.0.1"
  echo "localhost"
  for dom in "${domains[@]}"; do
    echo "$dom"
    echo "mail.${dom}"
  done
} | sort -u >"${TRUSTED}.tmp"
mv "${TRUSTED}.tmp" "$TRUSTED"

systemctl reload opendkim >/dev/null 2>&1 || systemctl restart opendkim
echo "OK mail-dkim-sync (${#domains[@]} domain)"
