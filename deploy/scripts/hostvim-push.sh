#!/usr/bin/env bash
#
# Panelze — Mac / geliştirici: değişiklikleri her zaman aynı dala push eder (varsayılan: main).
#
#   bash deploy/scripts/panelze-push.sh
#   bash deploy/scripts/panelze-push.sh "Panel: nginx düzeltmesi"
#
# Ortam:
#   PANELZE_DEPLOY_BRANCH=main   # sunucunun çekeceği dal (panelze-deploy ile aynı olmalı)
#   PANELZE_SKIP_COMMIT=1        # yalnızca mevcut commit'leri push et (commit atma)
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
BRANCH="${PANELZE_DEPLOY_BRANCH:-main}"
MSG="${1:-}"

cd "$REPO_ROOT"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Hata: Git deposu değil: $REPO_ROOT" >&2
  exit 1
fi

START_BRANCH="$(git branch --show-current 2>/dev/null || true)"
if [[ -z "$START_BRANCH" ]]; then
  echo "Hata: detached HEAD; önce bir dala geçin." >&2
  exit 1
fi

if [[ "${PANELZE_SKIP_COMMIT:-0}" != "1" ]]; then
  if [[ -n "$(git status --porcelain)" ]]; then
    if [[ -z "$MSG" ]]; then
      MSG="hostvim deploy $(date '+%Y-%m-%d %H:%M')"
    fi
    echo "==> commit ($START_BRANCH)"
    git add -A
    git commit -m "$MSG"
  elif [[ -z "$MSG" ]]; then
    echo "==> Yerel commit yok; mevcut HEAD push edilecek."
  fi
fi

echo "==> fetch origin"
git fetch origin

if [[ "$START_BRANCH" != "$BRANCH" ]]; then
  echo "==> $START_BRANCH -> $BRANCH birleştiriliyor"
  if git show-ref --verify --quiet "refs/remotes/origin/$BRANCH"; then
    git checkout "$BRANCH"
    git pull --ff-only "origin/$BRANCH" || git reset --hard "origin/$BRANCH"
  else
    git checkout -B "$BRANCH" "$START_BRANCH"
  fi
  git merge --no-edit "$START_BRANCH" -m "Merge branch '$START_BRANCH' into $BRANCH for deploy"
else
  if git show-ref --verify --quiet "refs/remotes/origin/$BRANCH"; then
    git pull --ff-only "origin/$BRANCH" || true
  fi
fi

echo "==> push origin $BRANCH"
git push -u origin "$BRANCH"

echo ""
echo "Tamam. Sunucuda (SSH ile — Mac'te panelze-deploy ÇALIŞTIRMAYIN):"
echo "  ssh root@SUNUCU_IP"
echo "  cd /var/www/panelze && bash panelze-deploy"
