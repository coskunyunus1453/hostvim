#!/usr/bin/env bash
#
# Hostvim — SUNUCU (Linux): git pull + engine + panel + frontend deploy.
# Mac'te ÇALIŞTIRMAYIN — Mac için: bash hostvim-push
#
#   ssh root@SUNUCU_IP
#   cd /var/www/hostvim && bash hostvim-deploy
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
HOSTVIM_HOME="${HOSTVIM_HOME:-${PANELZE_HOME:-$REPO_ROOT}}"
PANELZE_HOME="$HOSTVIM_HOME"
BRANCH="${HOSTVIM_DEPLOY_BRANCH:-${PANELZE_DEPLOY_BRANCH:-main}}"
PANEL_ROOT="${PANEL_ROOT:-$HOSTVIM_HOME/panel}"
SKIP_ENGINE="${HOSTVIM_SKIP_ENGINE:-${PANELZE_SKIP_ENGINE:-0}}"
SKIP_FRONTEND="${HOSTVIM_SKIP_FRONTEND:-${PANELZE_SKIP_FRONTEND:-0}}"

cd "$HOSTVIM_HOME"

if [[ "$(uname -s)" == "Darwin" ]] && [[ "$HOSTVIM_HOME" == *"/Applications/"* || "$HOSTVIM_HOME" == *"htdocs"* ]]; then
  echo "Hata: hostvim-deploy sunucu betiğidir; Mac'te çalıştırmayın." >&2
  echo "  Mac:  bash hostvim-push" >&2
  echo "  Sunucu: ssh ile bağlanıp  cd /var/www/hostvim && bash hostvim-deploy" >&2
  exit 1
fi

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
  # Sunucuda panel/public build artıkları merge'i bozmasın — her zaman origin ile aynı olsun.
  git reset --hard "origin/$BRANCH"
else
  echo "Hata: origin/$BRANCH bulunamadı. Mac'te önce: bash hostvim-push" >&2
  exit 1
fi

AFTER="$(git rev-parse HEAD)"
if [[ "$BEFORE" == "$AFTER" ]]; then
  echo "==> Git: zaten güncel ($AFTER)"
else
  echo "==> Git: $BEFORE -> $AFTER"
  git log -1 --oneline
fi

if [[ "$SKIP_ENGINE" != "1" ]]; then
  ENGINE_CMD=""
  if [[ -d "$HOSTVIM_HOME/engine/cmd/hostvim-engine" ]]; then
    ENGINE_CMD="hostvim-engine"
  elif [[ -d "$HOSTVIM_HOME/engine/cmd/panelze-engine" ]]; then
    ENGINE_CMD="panelze-engine"
  fi
  if [[ -n "$ENGINE_CMD" ]] && command -v go >/dev/null 2>&1; then
    echo "==> $ENGINE_CMD derleniyor -> /usr/local/bin/hostvim-engine"
    (cd "$HOSTVIM_HOME/engine" && go build -buildvcs=false -o /usr/local/bin/hostvim-engine "./cmd/$ENGINE_CMD")
    for svc in hostvim-engine panelze-engine panelsar-engine; do
      if systemctl is-active "$svc" >/dev/null 2>&1; then
        systemctl restart "$svc"
      fi
    done
  elif [[ -n "$ENGINE_CMD" ]]; then
    echo "Uyarı: go yok; engine atlandı." >&2
  fi
fi

DEPLOY_PANEL="$PANELZE_HOME/deploy/scripts/deploy-panel.sh"
if [[ -f "$DEPLOY_PANEL" ]] && [[ "$SKIP_FRONTEND" != "1" ]]; then
  echo "==> deploy-panel.sh"
  export HOSTVIM_HOME PANELZE_HOME
  export HOSTVIM_SKIP_GIT_PULL=1 PANELZE_SKIP_GIT_PULL=1
  export PANEL_ROOT
  bash "$DEPLOY_PANEL"
elif [[ -f "$DEPLOY_PANEL" ]] && [[ "$SKIP_FRONTEND" == "1" ]]; then
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
  if [[ "$SKIP_FRONTEND" != "1" ]] && [[ -f "$FRONTEND_ROOT/package.json" ]] && command -v npm >/dev/null 2>&1; then
    echo "==> frontend build"
    (cd "$FRONTEND_ROOT" && (test -f package-lock.json && npm ci || npm install) && npm run build)
    rsync -a --delete --exclude index.php --exclude .htaccess \
      "$FRONTEND_ROOT/dist/" "$PANEL_ROOT/public/"
  fi
fi

install_host_tool() {
  local base="$1"
  local src=""
  if [[ -f "${HOSTVIM_HOME}/deploy/host/hostvim-${base}" ]]; then
    src="${HOSTVIM_HOME}/deploy/host/hostvim-${base}"
  elif [[ -f "${HOSTVIM_HOME}/deploy/host/panelze-${base}" ]]; then
    src="${HOSTVIM_HOME}/deploy/host/panelze-${base}"
  else
    return 0
  fi
  install -m 755 "$src" "/usr/local/sbin/hostvim-${base}"
  ln -sfn "/usr/local/sbin/hostvim-${base}" "/usr/local/sbin/panelze-${base}"
  ln -sfn "/usr/local/sbin/hostvim-${base}" "/usr/local/sbin/panelsar-${base}" 2>/dev/null || true
}

if [[ "$(id -u)" -eq 0 ]]; then
  echo "==> host araçları (/usr/local/sbin)"
  install_host_tool stack-install
  install_host_tool mail-stack-setup.sh
  install_host_tool mail-provision
fi

echo ""
echo "Tamam. Panel: $PANEL_ROOT | Dal: $BRANCH @ $(git rev-parse --short HEAD)"
