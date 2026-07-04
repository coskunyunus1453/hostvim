#!/usr/bin/env bash
#
# Tüm barındırılan sitelerin HTTP, DNS ve PHP-FPM durumunu kontrol eder.
#   bash deploy/scripts/site-health-check.sh
#   curl -fsSL .../site-health-check.sh | bash
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib/resolve-paths.sh
source "$SCRIPT_DIR/lib/resolve-paths.sh"

PANELZE_HOME="$(resolve_panelze_home)"
WEB_ROOT="${PANELZE_HOSTING_WEB_ROOT:-$PANELZE_HOME/data/www}"
VHOST_DIR="${PANELZE_VHOST_DIR:-$PANELZE_HOME/data/vhosts}"
SERVER_IP="${PANELZE_SERVER_IP:-207.180.237.13}"
PUBLIC_DNS="${PUBLIC_DNS:-8.8.8.8}"
FAIL=0

red() { printf '\033[31m%s\033[0m\n' "$*"; }
grn() { printf '\033[32m%s\033[0m\n' "$*"; }
ylw() { printf '\033[33m%s\033[0m\n' "$*"; }

echo "==> Site sağlık kontrolü"
echo "    Web kökü: $WEB_ROOT"
echo "    Sunucu IP: $SERVER_IP"
echo

domains=()
if [[ -d "$VHOST_DIR" ]]; then
  while IFS= read -r conf; do
    d="$(basename "$conf" .conf)"
    d="${d#panelze-}"
    domains+=("$d")
  done < <(find "$VHOST_DIR" -maxdepth 1 -name 'panelze-*.conf' -type f 2>/dev/null | sort)
fi

if [[ ${#domains[@]} -eq 0 && -d "$WEB_ROOT" ]]; then
  while IFS= read -r d; do
    domains+=("$d")
  done < <(find "$WEB_ROOT" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null | sort)
fi

printf "%-32s %6s %6s %16s %s\n" "DOMAIN" "LOCAL" "HTTPS" "DNS(public)" "NOT"
printf "%-32s %6s %6s %16s %s\n" "------" "-----" "-----" "----------" "---"

for domain in "${domains[@]}"; do
  local_code="---"
  https_code="---"
  dns_ip=""
  note=""

  if curl -s -o /dev/null -w "%{http_code}" --max-time 6 -H "Host: $domain" "http://127.0.0.1/" 2>/dev/null | grep -qE '^[0-9]+$'; then
    local_code="$(curl -s -o /dev/null -w "%{http_code}" --max-time 6 -H "Host: $domain" "http://127.0.0.1/" 2>/dev/null || echo 000)"
  fi

  https_code="$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "https://$domain/" 2>/dev/null || echo 000)"
  dns_ip="$(dig +short "$domain" @"$PUBLIC_DNS" 2>/dev/null | head -1 || true)"

  if [[ "$local_code" =~ ^(000|5) ]]; then
    note="local HTTP sorun"
    FAIL=1
  fi
  if [[ "$https_code" =~ ^(000|5) ]]; then
    note="${note:+$note; }HTTPS sorun"
    FAIL=1
  fi
  if [[ -n "$dns_ip" && "$dns_ip" != "$SERVER_IP" ]]; then
    note="${note:+$note; }DNS yanlış IP ($dns_ip)"
    FAIL=1
  elif [[ -z "$dns_ip" ]]; then
    note="${note:+$note; }DNS kaydı yok"
    FAIL=1
  fi

  printf "%-32s %6s %6s %16s %s\n" "$domain" "$local_code" "$https_code" "${dns_ip:-—}" "$note"
done

echo
if [[ "$FAIL" -eq 0 ]]; then
  grn "Tüm kontroller geçti."
else
  red "Bazı sitelerde sorun var (yukarıdaki NOT sütununa bakın)."
  exit 1
fi
