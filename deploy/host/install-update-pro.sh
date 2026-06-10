#!/usr/bin/env bash
#
# Pro — güvenli güncelleme (siteler + veritabanları korunur).
#
# ÖNERİLEN (sudo ile pipe KULLANMAYIN — betik boş kalır, hiç çıktı vermez):
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/panelze/main/deploy/host/install-update-pro.sh" -o /tmp/panelze-update.sh
#   sudo bash /tmp/panelze-update.sh
#
# Root iseniz sudo gerekmez:
#   curl -fsSL "…/install-update-pro.sh" -o /tmp/panelze-update.sh && bash /tmp/panelze-update.sh
#
set -euo pipefail

echo "==> Panelze Pro güncelleme başlıyor…"

export APP_PROFILE=customer
export VENDOR_ENABLED=false
export ENFORCE_ADMIN_2FA="${ENFORCE_ADMIN_2FA:-false}"
export PANELZE_REPO_URL="${PANELZE_REPO_URL:-https://github.com/coskunyunus1453/panelze.git}"
export PANELZE_BRANCH="${PANELZE_BRANCH:-main}"

if [[ -n "${PANELZE_LICENSE_KEY:-}" ]]; then
  export PANELZE_LICENSE_KEY
fi

PANELZE_HOME="${PANELZE_HOME:-/var/www/panelze}"
export PANELZE_UPDATE_ONLY=1 RESET_PANEL_DB=0 PANELZE_FRESH_INSTALL=0 CLEAN_HOSTING_STATE_ON_RESET=0
export PANELZE_PRESERVE_ADMIN_PASSWORD="${PANELZE_PRESERVE_ADMIN_PASSWORD:-1}"
export PANELZE_AUTO_SYNC_GIT=1
export PANELZE_HOME

if [[ ! -f "$PANELZE_HOME/deploy/bootstrap/install-production.sh" ]]; then
  echo "Hata: Kurulum bulunamadı: $PANELZE_HOME/deploy/bootstrap/install-production.sh" >&2
  echo "Önce install-pro.sh ile kurun veya PANELZE_HOME yolunu kontrol edin." >&2
  exit 1
fi

cd "$PANELZE_HOME"
echo "==> Git: $PANELZE_HOME (dal: $PANELZE_BRANCH)"
git remote set-url origin "$PANELZE_REPO_URL" 2>/dev/null || true
git fetch origin "$PANELZE_BRANCH" --depth 1
git checkout "$PANELZE_BRANCH"
git merge --ff-only "origin/$PANELZE_BRANCH" 2>/dev/null || git reset --hard "origin/$PANELZE_BRANCH"

echo "==> install-production.sh (veri korunur)…"
export PANELZE_HOME
bash deploy/bootstrap/install-production.sh
_exit=$?
if [[ "$_exit" -ne 0 ]]; then
  echo "HATA: install-production çıkış kodu: $_exit" >&2
  exit "$_exit"
fi
echo "==> Güncelleme tamamlandı."
