#!/usr/bin/env bash
#
# Panelze — SUNUCU (Linux): git pull + engine + panel + frontend deploy.
# Mac'te ÇALIŞTIRMAYIN — Mac için: bash panelze-push
#
#   ssh root@SUNUCU_IP
#   cd /var/www/panelze && bash panelze-deploy
#
# Ortam:
#   PANELZE_HOME=/var/www/panelze
#   PANELZE_DEPLOY_BRANCH=main
#   PANELZE_SKIP_ENGINE=1      # Go derlemesini atla
#   PANELZE_SKIP_FRONTEND=1    # npm build atla
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
PANELZE_HOME="${PANELZE_HOME:-${PANELZE_HOME:-$REPO_ROOT}}"
PANELZE_HOME="$PANELZE_HOME"
BRANCH="${PANELZE_DEPLOY_BRANCH:-${PANELZE_DEPLOY_BRANCH:-main}}"
PANEL_ROOT="${PANEL_ROOT:-$PANELZE_HOME/panel}"
SKIP_ENGINE="${PANELZE_SKIP_ENGINE:-${PANELZE_SKIP_ENGINE:-0}}"
SKIP_FRONTEND="${PANELZE_SKIP_FRONTEND:-${PANELZE_SKIP_FRONTEND:-0}}"

cd "$PANELZE_HOME"

if [[ "$(uname -s)" == "Darwin" ]] && [[ "$PANELZE_HOME" == *"/Applications/"* || "$PANELZE_HOME" == *"htdocs"* ]]; then
  echo "Hata: panelze-deploy sunucu betiğidir; Mac'te çalıştırmayın." >&2
  echo "  Mac:  bash panelze-push" >&2
  echo "  Sunucu: ssh ile bağlanıp  cd /var/www/panelze && bash panelze-deploy" >&2
  exit 1
fi

if [[ ! -d "$PANELZE_HOME/.git" ]]; then
  echo "Hata: $PANELZE_HOME bir git deposu değil." >&2
  exit 1
fi

BEFORE="$(git rev-parse HEAD 2>/dev/null || echo none)"

echo "==> git fetch + checkout $BRANCH ($PANELZE_HOME)"
git remote -v | head -1 || true
git fetch origin "$BRANCH" --tags 2>/dev/null || git fetch origin
if git show-ref --verify --quiet "refs/remotes/origin/$BRANCH"; then
  git checkout "$BRANCH" 2>/dev/null || git checkout -B "$BRANCH" "origin/$BRANCH"
  # Sunucuda panel/public build artıkları merge'i bozmasın — her zaman origin ile aynı olsun.
  git reset --hard "origin/$BRANCH"
else
  echo "Hata: origin/$BRANCH bulunamadı. Mac'te önce: bash panelze-push" >&2
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
  if [[ "$(id -u)" -eq 0 ]] && [[ -f "$SCRIPT_DIR/ensure-webserver-stack.sh" ]] && [[ -d "$PANELZE_HOME/engine/cmd/panelze-engine" ]]; then
    echo "==> engine + web sunucu stack (helper, apache:8080, ols:8088)"
    PANELZE_HOME="$PANELZE_HOME" bash "$SCRIPT_DIR/ensure-webserver-stack.sh"
  elif [[ -d "$PANELZE_HOME/engine/cmd/panelze-engine" ]] && command -v go >/dev/null 2>&1; then
    echo "==> panelze-engine derleniyor -> /usr/local/bin/panelze-engine"
    if ! (cd "$PANELZE_HOME/engine" && go build -buildvcs=false -o /usr/local/bin/panelze-engine ./cmd/panelze-engine); then
      echo "Hata: engine derlenemedi." >&2
      exit 1
    fi
  elif [[ -d "$PANELZE_HOME/engine/cmd/panelze-engine" ]]; then
    echo "Uyarı: go yok; engine atlandı." >&2
  fi
fi

DEPLOY_PANEL="$PANELZE_HOME/deploy/scripts/deploy-panel.sh"
if [[ -f "$DEPLOY_PANEL" ]] && [[ "$SKIP_FRONTEND" != "1" ]]; then
  echo "==> deploy-panel.sh"
  export PANELZE_HOME PANELZE_HOME
  export PANELZE_SKIP_GIT_PULL=1 PANELZE_SKIP_GIT_PULL=1
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
  FRONTEND_ROOT="$PANELZE_HOME/frontend"
  if [[ "$SKIP_FRONTEND" != "1" ]] && [[ -f "$FRONTEND_ROOT/package.json" ]] && command -v npm >/dev/null 2>&1; then
    echo "==> frontend build"
    (cd "$FRONTEND_ROOT" && (test -f package-lock.json && npm ci || npm install) && npm run build)
    rsync -a --delete --exclude index.php --exclude .htaccess \
      "$FRONTEND_ROOT/dist/" "$PANEL_ROOT/public/"
  fi
fi

install_host_tool() {
  local base="$1"
  local src="${PANELZE_HOME}/deploy/host/panelze-${base}"
  if [[ ! -f "$src" ]]; then
    return 0
  fi
  install -m 755 "$src" "/usr/local/sbin/panelze-${base}"
  ln -sfn "/usr/local/sbin/panelze-${base}" "/usr/local/sbin/panelsar-${base}" 2>/dev/null || true
}

if [[ "$(id -u)" -eq 0 ]]; then
  echo "==> host araçları (/usr/local/sbin)"
  install_host_tool stack-install
  install_host_tool mail-stack-setup.sh
  install_host_tool mail-provision
  install_host_tool mail-dkim-sync
  install_host_tool bind-sync
  install_host_tool node-pm2
  install_host_tool nginx-vhost
  install_host_tool apache-vhost
  install_host_tool ols-vhost
  install_host_tool security
  if [[ -f "$SCRIPT_DIR/ensure-security-defaults.sh" ]]; then
    PANEL_ROOT="$PANEL_ROOT" bash "$SCRIPT_DIR/ensure-security-defaults.sh" || true
  fi
  if [[ -f "$SCRIPT_DIR/fix-dovecot-virtual-auth.sh" ]]; then
    bash "$SCRIPT_DIR/fix-dovecot-virtual-auth.sh" || true
  fi
  if [[ -f "$SCRIPT_DIR/fix-mail-firewall.sh" ]]; then
    bash "$SCRIPT_DIR/fix-mail-firewall.sh" || true
  fi
  if [[ -f "$SCRIPT_DIR/configure-roundcube-signon.sh" ]] && dpkg-query -W -f='${Status}' roundcube-core 2>/dev/null | grep -q 'install ok'; then
    bash "$SCRIPT_DIR/configure-roundcube-signon.sh" || true
  fi
  if [[ -f "$SCRIPT_DIR/configure-phpmyadmin-signon.sh" ]] && [[ -d /etc/phpmyadmin ]]; then
    bash "$SCRIPT_DIR/configure-phpmyadmin-signon.sh" || true
  fi
  if [[ -f "$SCRIPT_DIR/fix-roundcube-smtp.sh" ]] && dpkg-query -W -f='${Status}' roundcube-core 2>/dev/null | grep -q 'install ok'; then
    bash "$SCRIPT_DIR/fix-roundcube-smtp.sh" || true
  fi
  install_host_tool terminal-root
  install_host_tool php-ini
  install_host_tool system-settings
  echo "==> engine sudoers (NOPASSWD)"
  bash "$SCRIPT_DIR/ensure-engine-sudoers.sh"
  if ! command -v named >/dev/null 2>&1 || { ! systemctl is-active named >/dev/null 2>&1 && ! systemctl is-active bind9 >/dev/null 2>&1; }; then
    if [[ -f "${PANELZE_HOME}/deploy/host/panelze-bind-setup.sh" ]]; then
      echo "==> BIND9 kurulumu (ilk kez)"
      bash "${PANELZE_HOME}/deploy/host/panelze-bind-setup.sh"
    fi
  fi
  ENSURE_BIND="$SCRIPT_DIR/ensure-bind-config.sh"
  if [[ -f "$ENSURE_BIND" ]]; then
    echo "==> BIND yapılandırması (panelze zones)"
    bash "$ENSURE_BIND" || echo "Uyarı: BIND config kontrolü başarısız" >&2
  fi
  if [[ -x /usr/local/sbin/panelze-bind-sync ]]; then
    echo "==> BIND DNS senkronu"
    PANELZE_HOME="$PANELZE_HOME" PANEL_ROOT="$PANEL_ROOT" \
      /usr/local/sbin/panelze-bind-sync || echo "Uyarı: BIND sync başarısız" >&2
  fi
  if [[ -f "$PANEL_ROOT/artisan" ]]; then
    echo "==> DNS kayıt onarımı (tüm domainler: @, www, mail, webmail)"
    (cd "$PANEL_ROOT" && php artisan panelze:dns-repair --all --no-interaction) \
      || echo "Uyarı: DNS repair atlandı veya başarısız" >&2
    (cd "$PANEL_ROOT" && php artisan panelze:dns-bootstrap --all --no-interaction) \
      || echo "Uyarı: DNS bootstrap atlandı veya başarısız" >&2
  fi
  echo "==> queue worker (timeout=${PANELZE_QUEUE_TIMEOUT:-1900})"
  PANEL_ROOT="$PANEL_ROOT" bash "$SCRIPT_DIR/ensure-queue-worker.sh"
fi

echo ""
echo "Tamam. Panel: $PANEL_ROOT | Dal: $BRANCH @ $(git rev-parse --short HEAD)"
