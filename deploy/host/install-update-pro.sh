#!/usr/bin/env bash
#
# Pro — güvenli güncelleme (siteler + veritabanları korunur).
#
# ÖNERİLEN (sudo ile pipe KULLANMAYIN — betik boş kalır, hiç çıktı vermez):
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update-pro.sh" -o /tmp/hostvim-update.sh
#   sudo bash /tmp/hostvim-update.sh
#
# Root iseniz sudo gerekmez:
#   curl -fsSL "…/install-update-pro.sh" -o /tmp/hostvim-update.sh && bash /tmp/hostvim-update.sh
#
set -euo pipefail

echo "==> Hostvim Pro güncelleme başlıyor…"

export APP_PROFILE=customer
export VENDOR_ENABLED=false
export ENFORCE_ADMIN_2FA="${ENFORCE_ADMIN_2FA:-false}"
export HOSTVIM_REPO_URL="${HOSTVIM_REPO_URL:-https://github.com/coskunyunus1453/hostvim.git}"
export HOSTVIM_BRANCH="${HOSTVIM_BRANCH:-main}"

if [[ -n "${HOSTVIM_LICENSE_KEY:-}" ]]; then
  export HOSTVIM_LICENSE_KEY
fi

HOSTVIM_HOME="${HOSTVIM_HOME:-/var/www/hostvim}"
export HOSTVIM_UPDATE_ONLY=1 RESET_PANEL_DB=0 HOSTVIM_FRESH_INSTALL=0 CLEAN_HOSTING_STATE_ON_RESET=0
export HOSTVIM_PRESERVE_ADMIN_PASSWORD="${HOSTVIM_PRESERVE_ADMIN_PASSWORD:-1}"
export HOSTVIM_AUTO_SYNC_GIT=1
export HOSTVIM_HOME

if [[ ! -f "$HOSTVIM_HOME/deploy/bootstrap/install-production.sh" ]]; then
  echo "Hata: Kurulum bulunamadı: $HOSTVIM_HOME/deploy/bootstrap/install-production.sh" >&2
  echo "Önce install-pro.sh ile kurun veya HOSTVIM_HOME yolunu kontrol edin." >&2
  exit 1
fi

cd "$HOSTVIM_HOME"
echo "==> Git: $HOSTVIM_HOME (dal: $HOSTVIM_BRANCH)"
git remote set-url origin "$HOSTVIM_REPO_URL" 2>/dev/null || true
git fetch origin "$HOSTVIM_BRANCH" --depth 1
git checkout "$HOSTVIM_BRANCH"
git merge --ff-only "origin/$HOSTVIM_BRANCH" 2>/dev/null || git reset --hard "origin/$HOSTVIM_BRANCH"

echo "==> install-production.sh (veri korunur)…"
exec bash deploy/bootstrap/install-production.sh
