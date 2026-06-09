#!/usr/bin/env bash
#
# Panelze — güvenli güncelleme (mevcut kurulumda veri silmez).
#
#   curl -fsSL "https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install-update.sh" | sudo bash
#
# Yapar: git pull, panel/frontend/engine güncelleme, yalnızca migrate --force (fresh değil).
# Korur: panel .env, data/www siteleri, müşteri MySQL veritabanları, yedekler.
#
# Fabrika sıfırlama (TÜM VERİ SİLİNİR):
#   PANELZE_FRESH_INSTALL=1 curl -fsSL "…/install.sh" | sudo bash
#
set -euo pipefail

export PANELZE_UPDATE_ONLY=1
export RESET_PANEL_DB=0
export PANELZE_FRESH_INSTALL=0
export CLEAN_HOSTING_STATE_ON_RESET=0
export PANELZE_PRESERVE_ADMIN_PASSWORD="${PANELZE_PRESERVE_ADMIN_PASSWORD:-1}"
export PANELZE_AUTO_SYNC_GIT=1

PANELZE_REPO_URL="${PANELZE_REPO_URL:-https://github.com/coskunyunus1453/hostvim.git}"
PANELZE_BRANCH="${PANELZE_BRANCH:-main}"
PANELZE_HOME="${PANELZE_HOME:-/var/www/panelze}"

export PANELSAR_REPO_URL="$PANELZE_REPO_URL"
export PANELSAR_BRANCH="$PANELZE_BRANCH"

# Mevcut kurulumda yerel betik kullan (iç içe curl|bash kurulumu yarıda kesilmesin).
if [[ -f "$PANELZE_HOME/deploy/bootstrap/install-production.sh" ]]; then
  echo "==> Yerel güncelleme: $PANELZE_HOME"
  cd "$PANELZE_HOME"
  git remote set-url origin "$PANELZE_REPO_URL" 2>/dev/null || true
  git fetch origin "$PANELZE_BRANCH" --depth 1
  git checkout "$PANELZE_BRANCH"
  git merge --ff-only "origin/$PANELZE_BRANCH" 2>/dev/null || git reset --hard "origin/$PANELZE_BRANCH"
  exec bash deploy/bootstrap/install-production.sh
fi

PANELZE_INSTALL_SCRIPT_URL="${PANELZE_INSTALL_SCRIPT_URL:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/main/deploy/host/install.sh}"
PANELZE_INSTALL_SCRIPT_URL="${PANELZE_INSTALL_SCRIPT_URL}?ts=$(date +%s)"
export PANELSAR_INSTALL_SCRIPT_URL="$PANELZE_INSTALL_SCRIPT_URL"
_TMP_INSTALL="$(mktemp)"
trap 'rm -f "$_TMP_INSTALL"' EXIT
curl -fsSL "$PANELZE_INSTALL_SCRIPT_URL" -o "$_TMP_INSTALL"
bash "$_TMP_INSTALL"
