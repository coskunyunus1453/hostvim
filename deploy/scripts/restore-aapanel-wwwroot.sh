#!/usr/bin/env bash
# aaPanel / BT Panel yedeği: www/wwwroot/<domain>/ → Panelze: <web_root>/<domain>/public_html/
#
# Kullanım (Mac — arşivi sunucuya at + sunucuda dağıt):
#   export SSH_HOST=root@207.180.237.13
#   ./deploy/scripts/restore-aapanel-wwwroot.sh push ~/Desktop/tum_siteler_yedek.tar.gz
#
# Sadece sunucuda (arşiv zaten /tmp altında):
#   ./deploy/scripts/restore-aapanel-wwwroot.sh restore /tmp/tum_siteler_yedek.tar.gz
#
# Yerelde önizleme (hangi domainler var):
#   ./deploy/scripts/restore-aapanel-wwwroot.sh list ~/Desktop/tum_siteler_yedek.tar.gz

set -euo pipefail

SSH_HOST="${SSH_HOST:-root@207.180.237.13}"
WEB_ROOT="${PANELZE_HOSTING_WEB_ROOT:-/var/www/data/www}"
WEB_USER="${PANELZE_WEB_USER:-www-data}"
WEB_GROUP="${PANELZE_WEB_GROUP:-www-data}"
SKIP_DOMAINS="${SKIP_DOMAINS:-default}"

cmd="${1:-}"
arg="${2:-}"

skip_domain() {
  local d="$1"
  [[ " $SKIP_DOMAINS " == *" $d "* ]] && return 0
  return 1
}

list_domains_in_archive() {
  local arc="$1"
  tar -tzf "$arc" | sed -n 's|^www/wwwroot/\([^/]*\)/.*|\1|p' | sort -u
}

remote_web_root() {
  ssh "$SSH_HOST" bash -s <<'REMOTE'
set -euo pipefail
for p in "${PANELZE_HOSTING_WEB_ROOT:-}" "/var/www/data/www" "/var/www/panelze/data/www"; do
  if [[ -n "$p" && -d "$p" ]]; then
    echo "$p"
    exit 0
  fi
done
# panel .env
for env in /var/www/panelze/panel/.env /var/www/panel/.env; do
  if [[ -f "$env" ]]; then
    wr="$(grep -E '^PANELZE_HOSTING_WEB_ROOT=' "$env" 2>/dev/null | cut -d= -f2- | tr -d '"' | tr -d "'")"
    if [[ -n "$wr" && -d "$wr" ]]; then
      echo "$wr"
      exit 0
    fi
  fi
done
exit 1
REMOTE
}

restore_on_server() {
  local arc="$1"
  local wr="${2:-}"
  ssh "$SSH_HOST" bash -s -- "$arc" "$wr" "$WEB_USER" "$WEB_GROUP" "$SKIP_DOMAINS" <<'REMOTE'
set -euo pipefail
ARC="$1"
WR="${2:-}"
WU="${3:-www-data}"
WG="${4:-www-data}"
SKIP="${5:-default}"

if [[ -z "$WR" ]]; then
  for p in /var/www/data/www /var/www/panelze/data/www; do
    [[ -d "$p" ]] && WR="$p" && break
  done
fi
[[ -n "$WR" && -d "$WR" ]] || { echo "WEB_ROOT bulunamadı: $WR" >&2; exit 1; }

WORK="/tmp/panelze-www-restore-$$"
mkdir -p "$WORK"
echo "==> Arşiv açılıyor: $ARC"
tar -xzf "$ARC" -C "$WORK"

SRC="$WORK/www/wwwroot"
[[ -d "$SRC" ]] || { echo "Beklenen yol yok: www/wwwroot (aaPanel yedeği mi?)" >&2; exit 1; }

echo "==> Hedef web kökü: $WR"
shopt -s nullglob
count=0
for dir in "$SRC"/*/; do
  domain="$(basename "$dir")"
  if [[ " $SKIP " == *" $domain "* ]]; then
    echo "    atla: $domain"
    continue
  fi
  dest="$WR/$domain/public_html"
  mkdir -p "$dest"
  echo "    -> $domain"
  rsync -a --delete "${dir%/}/" "$dest/"
  count=$((count + 1))
done

echo "==> İzinler ($WU:$WG)"
chown -R "$WU:$WG" "$WR" 2>/dev/null || true
find "$WR" -type d -exec chmod 2775 {} \; 2>/dev/null || true
find "$WR" -type f -exec chmod 664 {} \; 2>/dev/null || true

rm -rf "$WORK"
echo "==> Tamam: $count site dosyası public_html altına kopyalandı."
REMOTE
}

push_and_restore() {
  local local_arc="$1"
  [[ -f "$local_arc" ]] || { echo "Dosya yok: $local_arc" >&2; exit 1; }
  local remote_arc="/tmp/$(basename "$local_arc")"
  echo "==> Yükleme (rsync): $local_arc -> ${SSH_HOST}:${remote_arc}"
  rsync -avh --progress --partial -e ssh "$local_arc" "${SSH_HOST}:${remote_arc}"
  echo "==> Sunucuda dağıtım başlıyor"
  local wr=""
  wr="$(remote_web_root 2>/dev/null || true)"
  restore_on_server "$remote_arc" "$wr"
}

case "$cmd" in
  list)
    [[ -n "$arg" ]] || { echo "Kullanım: $0 list /path/to/tum_siteler_yedek.tar.gz" >&2; exit 1; }
    echo "Arşivdeki domainler:"
    list_domains_in_archive "$arg"
    ;;
  push)
    [[ -n "$arg" ]] || { echo "Kullanım: $0 push /path/to/tum_siteler_yedek.tar.gz" >&2; exit 1; }
    push_and_restore "$arg"
    ;;
  restore)
    [[ -n "$arg" ]] || { echo "Kullanım: $0 restore /tmp/tum_siteler_yedek.tar.gz  (SSH_HOST ile uzaktan)" >&2; exit 1; }
    wr="$(remote_web_root 2>/dev/null || echo "$WEB_ROOT")"
    restore_on_server "$arg" "$wr"
    ;;
  *)
    sed -n '2,12p' "$0"
    exit 1
    ;;
esac
