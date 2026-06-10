#!/usr/bin/env bash
# Müşteri site dosyaları (data/www) — Engine www-data ile yazar; SSH/root yüklemelerinde izin onarımı.
#
# Kullanım (root):
#   bash deploy/scripts/fix-hosting-permissions.sh
#   PANELZE_HOME=/var/www/panelze bash deploy/scripts/fix-hosting-permissions.sh
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
OWNER="${RUN_USER:-www-data}"
GROUP="${RUN_GROUP:-$OWNER}"

if [[ ! -d "$WEB_ROOT" ]]; then
  mkdir -p "$WEB_ROOT"
fi

echo "==> Hosting web kökü: $WEB_ROOT"
echo "==> Sahiplik: $OWNER:$GROUP"

_site_count=0
if [[ -d "$WEB_ROOT" ]]; then
  _site_count="$(find "$WEB_ROOT" -mindepth 1 -maxdepth 1 \( -type d -o -type f \) 2>/dev/null | wc -l | tr -d ' ')"
fi

# Güncelleme: tüm data/ üzerinde chown+find saatler sürebilir; yalnızca web kökü
_quick=0
if [[ "${PANELZE_UPDATE_ONLY:-0}" == "1" ]] || [[ "${PANELZE_QUICK_PERM_FIX:-0}" == "1" ]]; then
  _quick=1
fi

if [[ "$_site_count" -gt 20 ]] && [[ "${PANELZE_FORCE_FULL_PERM_FIX:-0}" != "1" ]]; then
  _quick=1
  echo "==> $_site_count site/klasör algılandı; hızlı izin modu (tam tarama atlandı)."
  echo "    Tam onarım: PANELZE_FORCE_FULL_PERM_FIX=1 sudo panelze-fix-hosting-perms"
fi

_run_chown_chmod() {
  local target="$1"
  if [[ "$(id -u)" -eq 0 ]]; then
    chown -R "$OWNER:$GROUP" "$target"
    chmod -R ug=rwX,o=rX "$target" 2>/dev/null || chmod -R 775 "$target"
  else
    sudo chown -R "$OWNER:$GROUP" "$target"
    sudo chmod -R ug=rwX,o=rX "$target" 2>/dev/null || sudo chmod -R 775 "$target"
  fi
}

if [[ "$_quick" == "1" ]]; then
  echo "==> Hızlı izin (web kökü; birkaç dakika sürebilir)…"
  _run_chown_chmod "$WEB_ROOT"
  for _d in tmp ssl backups logs vhosts; do
    if [[ -d "$PANELZE_HOME/data/$_d" ]]; then
      if [[ "$(id -u)" -eq 0 ]]; then
        chown -R "$OWNER:$GROUP" "$PANELZE_HOME/data/$_d"
      else
        sudo chown -R "$OWNER:$GROUP" "$PANELZE_HOME/data/$_d"
      fi
    fi
  done
else
  echo "==> Tam izin onarımı (çok dosya varsa uzun sürebilir, bekleyin)…"
  if [[ "$(id -u)" -eq 0 ]]; then
    chown -R "$OWNER:$GROUP" "$PANELZE_HOME/data"
    chmod -R ug=rwX,o=rX "$WEB_ROOT" 2>/dev/null || chmod -R 775 "$WEB_ROOT"
  else
    sudo chown -R "$OWNER:$GROUP" "$PANELZE_HOME/data"
    sudo chmod -R ug=rwX,o=rX "$WEB_ROOT" 2>/dev/null || sudo chmod -R 775 "$WEB_ROOT"
  fi
fi

echo "Tamam. Panel dosya düzenleyici (Engine) site dosyalarına yazabilir."
