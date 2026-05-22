#!/usr/bin/env bash
#
# Hostvim — sunucu: tek komutla git pull + engine + panel + frontend deploy.
#
#   cd /var/www/hostvim && bash deploy/scripts/hostvim-deploy.sh
#
# Ortam:
#   HOSTVIM_HOME=/var/www/hostvim
#   HOSTVIM_DEPLOY_BRANCH=main
#   HOSTVIM_SKIP_ENGINE=1      # Go derlemesini atla
#   HOSTVIM_SKIP_FRONTEND=1    # npm build atla
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
HOSTVIM_HOME="${HOSTVIM_HOME:-$REPO_ROOT}"
BRANCH="${HOSTVIM_DEPLOY_BRANCH:-main}"
PANEL_ROOT="${PANEL_ROOT:-$HOSTVIM_HOME/panel}"

cd "$HOSTVIM_HOME"

if [[ ! -d "$HOSTVIM_HOME/.git" ]]; then
  echo "Hata: $HOSTVIM_HOME bir git deposu değil." >&2
  exit 1
fi

BEFORE="$(git rev-parse HEAD 2>/dev/null || echo none)"

echo "==> git fetch + checkout $BRANCH ($HOSTVIM_HOME)"
git remote -v | head -1 || true
git fetch origin "$BRANCH" --tags 2>/dev/null || git fetch origin
if git show-ref --verify --quiet "refs/remotes/origin/$BRANCH"; then
  git checkout "$BRANCH" 2>/dev/null || git checkout -B "$BRANCH" "origin/$BRANCH"
  git merge --ff-only "origin/$BRANCH" || git reset --hard "origin/$BRANCH"
else
  echo "Hata: origin/$BRANCH bulunamadı. Mac'te önce: bash deploy/scripts/hostvim-push.sh" >&2
  exit 1
fi

AFTER="$(git rev-parse HEAD)"
if [[ "$BEFORE" == "$AFTER" ]]; then
  echo "==> Git: zaten güncel ($AFTER)"
else
  echo "==> Git: $BEFORE -> $AFTER"
  git log -1 --oneline
fi

if [[ "${HOSTVIM_SKIP_ENGINE:-0}" != "1" ]] && [[ -d "$HOSTVIM_HOME/engine/cmd/hostvim-engine" ]]; then
  if command -v go >/dev/null 2>&1; then
    echo "==> hostvim-engine derleniyor"
    (cd "$HOSTVIM_HOME/engine" && go build -buildvcs=false -o /usr/local/bin/hostvim-engine ./cmd/hostvim-engine)
    if systemctl is-active hostvim-engine >/dev/null 2>&1; then
      systemctl restart hostvim-engine
    elif systemctl is-active panelsar-engine >/dev/null 2>&1; then
      systemctl restart panelsar-engine
    fi
  else
    echo "Uyarı: go yok; engine atlandı." >&2
  fi
fi

DEPLOY_PANEL="$HOSTVIM_HOME/deploy/scripts/deploy-panel.sh"
if [[ -f "$DEPLOY_PANEL" ]] && [[ "${HOSTVIM_SKIP_FRONTEND:-0}" != "1" ]]; then
  echo "==> deploy-panel.sh"
  export HOSTVIM_HOME
  export HOSTVIM_SKIP_GIT_PULL=1
  export PANEL_ROOT
  bash "$DEPLOY_PANEL"
elif [[ -f "$DEPLOY_PANEL" ]] && [[ "${HOSTVIM_SKIP_FRONTEND:-0}" == "1" ]]; then
  echo "==> panel (frontend atlandı)"
  cd "$PANEL_ROOT"
  if [[ "$(id -u)" -eq 0 ]]; then
    sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
    sudo -u www-data php artisan migrate --force
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache
  else
    composer install --no-dev --optimize-autoloader --no-interaction
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
  fi
else
  echo "==> deploy-panel.sh yok; manuel panel/frontend deploy"
  if [[ ! -f "$PANEL_ROOT/.env" ]]; then
    echo "Hata: $PANEL_ROOT/.env yok." >&2
    exit 1
  fi
  cd "$PANEL_ROOT"
  if [[ "$(id -u)" -eq 0 ]]; then
    sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
    sudo -u www-data php artisan migrate --force
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache
  else
    composer install --no-dev --optimize-autoloader --no-interaction
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
  fi
  FRONTEND_ROOT="$HOSTVIM_HOME/frontend"
  if [[ "${HOSTVIM_SKIP_FRONTEND:-0}" != "1" ]] && [[ -f "$FRONTEND_ROOT/package.json" ]] && command -v npm >/dev/null 2>&1; then
    echo "==> frontend build"
    (cd "$FRONTEND_ROOT" && (test -f package-lock.json && npm ci || npm install) && npm run build)
    rsync -a --delete --exclude index.php --exclude .htaccess \
      "$FRONTEND_ROOT/dist/" "$PANEL_ROOT/public/"
  fi
fi

echo ""
echo "Tamam. Panel: $PANEL_ROOT | Dal: $BRANCH @ $(git rev-parse --short HEAD)"
