#!/usr/bin/env bash
#
# Hostvim — güvenli güncelleme (mevcut kurulumda veri silmez).
#
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update.sh" | sudo bash
#
# Yapar: git pull, panel/frontend/engine güncelleme, yalnızca migrate --force (fresh değil).
# Korur: panel .env, data/www siteleri, müşteri MySQL veritabanları, yedekler.
#
# Fabrika sıfırlama (TÜM VERİ SİLİNİR):
#   HOSTVIM_FRESH_INSTALL=1 curl -fsSL "…/install.sh" | sudo bash
#
set -euo pipefail

export HOSTVIM_UPDATE_ONLY=1
export RESET_PANEL_DB=0
export HOSTVIM_FRESH_INSTALL=0
export CLEAN_HOSTING_STATE_ON_RESET=0
export HOSTVIM_PRESERVE_ADMIN_PASSWORD="${HOSTVIM_PRESERVE_ADMIN_PASSWORD:-1}"
export HOSTVIM_AUTO_SYNC_GIT=1

HOSTVIM_REPO_URL="${HOSTVIM_REPO_URL:-https://github.com/coskunyunus1453/hostvim.git}"
HOSTVIM_BRANCH="${HOSTVIM_BRANCH:-main}"
HOSTVIM_HOME="${HOSTVIM_HOME:-/var/www/hostvim}"

export PANELSAR_REPO_URL="$HOSTVIM_REPO_URL"
export PANELSAR_BRANCH="$HOSTVIM_BRANCH"

# Mevcut kurulumda yerel betik kullan (iç içe curl|bash kurulumu yarıda kesilmesin).
if [[ -f "$HOSTVIM_HOME/deploy/bootstrap/install-production.sh" ]]; then
  echo "==> Yerel güncelleme: $HOSTVIM_HOME"
  cd "$HOSTVIM_HOME"
  git remote set-url origin "$HOSTVIM_REPO_URL" 2>/dev/null || true
  git fetch origin "$HOSTVIM_BRANCH" --depth 1
  git checkout "$HOSTVIM_BRANCH"
  git merge --ff-only "origin/$HOSTVIM_BRANCH" 2>/dev/null || git reset --hard "origin/$HOSTVIM_BRANCH"
  exec bash deploy/bootstrap/install-production.sh
fi

HOSTVIM_INSTALL_SCRIPT_URL="${HOSTVIM_INSTALL_SCRIPT_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install.sh}"
HOSTVIM_INSTALL_SCRIPT_URL="${HOSTVIM_INSTALL_SCRIPT_URL}?ts=$(date +%s)"
export PANELSAR_INSTALL_SCRIPT_URL="$HOSTVIM_INSTALL_SCRIPT_URL"
_TMP_INSTALL="$(mktemp)"
trap 'rm -f "$_TMP_INSTALL"' EXIT
curl -fsSL "$HOSTVIM_INSTALL_SCRIPT_URL" -o "$_TMP_INSTALL"
bash "$_TMP_INSTALL"
