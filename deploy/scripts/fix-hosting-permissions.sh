#!/usr/bin/env bash
# Müşteri site dosyaları (data/www) — Engine www-data ile yazar; SSH/root/Mac yüklemelerinde izin onarımı.
#
# Kullanım (root):
#   bash deploy/scripts/fix-hosting-permissions.sh
#   bash deploy/scripts/fix-hosting-permissions.sh --domain gumusfiyat.com
#   PANELZE_DOMAIN=kodsar.com bash deploy/scripts/fix-hosting-permissions.sh
#   PANELZE_HOME=/var/www/hostvim bash deploy/scripts/fix-hosting-permissions.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ -f "$SCRIPT_DIR/lib/panelze-deploy-common.sh" ]]; then
  # shellcheck source=lib/panelze-deploy-common.sh
  source "$SCRIPT_DIR/lib/panelze-deploy-common.sh"
elif [[ -f "${PANELZE_HOME:-/var/www/panelze}/deploy/scripts/lib/panelze-deploy-common.sh" ]]; then
  SCRIPT_DIR="${PANELZE_HOME:-/var/www/panelze}/deploy/scripts"
  # shellcheck source=/dev/null
  source "$SCRIPT_DIR/lib/panelze-deploy-common.sh"
else
  echo "Hata: panelze-deploy-common.sh bulunamadi." >&2
  exit 1
fi

PANELZE_HOME="$(panelze_resolve_home "${PANEL_ROOT:-}")"
WEB_ROOT="${PANELZE_HOSTING_WEB_ROOT:-$PANELZE_HOME/data/www}"
DATA_ROOT="${PANELZE_HOME}/data"
OWNER="${RUN_USER:-www-data}"
GROUP="${RUN_GROUP:-$OWNER}"

TARGET_DOMAIN="${PANELZE_DOMAIN:-}"
CAGE_HELPER="/usr/local/sbin/panelze-site-cage"
ENGINE_CFG="${PANELZE_ENGINE_CFG:-/etc/panelze/engine.yaml}"
PANELKAFES=0
if [[ -x "$CAGE_HELPER" ]] && [[ -f "$ENGINE_CFG" ]] && grep -Eq 'site_cage_enabled:\s*true' "$ENGINE_CFG" 2>/dev/null; then
  PANELKAFES=1
fi
if [[ "${PANELZE_PANELKAFES:-}" == "1" ]]; then
  PANELKAFES=1
fi

while [[ $# -gt 0 ]]; do
  case "$1" in
    --domain)
      TARGET_DOMAIN="${2:-}"
      shift 2
      ;;
    -h|--help)
      echo "Kullanım: panelze-fix-hosting-perms [--domain SITE]"
      exit 0
      ;;
    *)
      shift
      ;;
  esac
done

if [[ ! -d "$WEB_ROOT" ]]; then
  mkdir -p "$WEB_ROOT"
fi

echo "==> Hosting web kökü: $WEB_ROOT"
echo "==> Sahiplik: $OWNER:$GROUP"

_apply_tree() {
  local target="$1"
  [[ -e "$target" ]] || return 0
  echo "    -> $target"
  if [[ "$(id -u)" -eq 0 ]]; then
    chown -R "$OWNER:$GROUP" "$target"
    find "$target" -type d -exec chmod 2775 {} +
    find "$target" -type f -exec chmod 664 {} +
  else
    sudo chown -R "$OWNER:$GROUP" "$target"
    sudo find "$target" -type d -exec chmod 2775 {} +
    sudo find "$target" -type f -exec chmod 664 {} +
  fi
}

# data/ üst dizinleri: www-data geçiş + yeni site oluşturma
_fix_data_parents() {
  local d
  for d in "$DATA_ROOT" "$WEB_ROOT"; do
    [[ -d "$d" ]] || continue
    if [[ "$(id -u)" -eq 0 ]]; then
      chown "$OWNER:$GROUP" "$d"
      chmod 2775 "$d"
    else
      sudo chown "$OWNER:$GROUP" "$d"
      sudo chmod 2775 "$d"
    fi
  done
}

_fix_data_parents

if [[ -n "$TARGET_DOMAIN" ]]; then
  if [[ "$PANELKAFES" == "1" ]]; then
    echo "==> PanelKafes: $TARGET_DOMAIN"
    "$CAGE_HELPER" apply "$TARGET_DOMAIN" "$WEB_ROOT"
    echo "Tamam. $TARGET_DOMAIN — PanelKafes izolasyonu uygulandı."
  else
    _apply_tree "$WEB_ROOT/$TARGET_DOMAIN"
    echo "Tamam. $TARGET_DOMAIN — panel dosya yöneticisi yazabilir."
  fi
  exit 0
fi

_site_count=0
if [[ -d "$WEB_ROOT" ]]; then
  _site_count="$(find "$WEB_ROOT" -mindepth 1 -maxdepth 1 -type d 2>/dev/null | wc -l | tr -d ' ')"
fi

_quick=0
if [[ "${PANELZE_UPDATE_ONLY:-0}" == "1" ]] || [[ "${PANELZE_QUICK_PERM_FIX:-0}" == "1" ]]; then
  _quick=1
fi
if [[ "$_site_count" -gt 20 ]] && [[ "${PANELZE_FORCE_FULL_PERM_FIX:-0}" != "1" ]]; then
  _quick=1
  echo "==> $_site_count site; hızlı mod (web kökü tam onarım)."
  echo "    Tek site: panelze-fix-hosting-perms --domain ornek.com"
fi

if [[ "$_quick" == "1" ]]; then
  echo "==> Tüm web kökü izinleri…"
  if [[ "$PANELKAFES" == "1" ]]; then
    echo "==> PanelKafes apply-all"
    "$CAGE_HELPER" apply-all "$WEB_ROOT"
  else
    _apply_tree "$WEB_ROOT"
  fi
else
  echo "==> Tam izin onarımı (data + www)…"
  for _d in logs ssl backups vhosts apache-vhosts ols-staging tmp pm2; do
    [[ -d "$DATA_ROOT/$_d" ]] && _apply_tree "$DATA_ROOT/$_d"
  done
  if [[ "$PANELKAFES" == "1" ]]; then
    echo "==> PanelKafes apply-all"
    "$CAGE_HELPER" apply-all "$WEB_ROOT"
  else
    _apply_tree "$WEB_ROOT"
  fi
fi

echo "Tamam. Panel dosya yöneticisi (Engine/www-data) tüm site dosyalarına yazabilir."
