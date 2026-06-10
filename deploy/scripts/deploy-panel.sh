#!/usr/bin/env bash
# Panelze panel — tek sunucu deploy sırası (örnek).
# Kullanım: PANEL_ROOT=/var/www/panelze/panel bash deploy/scripts/deploy-panel.sh
set -euo pipefail

PANEL_ROOT="${PANEL_ROOT:?PANEL_ROOT tanımlayın (örn. /var/www/panelze/panel)}"
FRONTEND_ROOT="${FRONTEND_ROOT:-$(dirname "$PANEL_ROOT")/frontend}"
REPO_ROOT="$(cd "$(dirname "$PANEL_ROOT")" && pwd)"
export PANELZE_HOME="${PANELZE_HOME:-$REPO_ROOT}"
RUN_USER="${RUN_USER:-www-data}"
DEPLOY_SCRIPTS="$REPO_ROOT/deploy/scripts"
if [[ -f "$DEPLOY_SCRIPTS/lib/panelze-deploy-common.sh" ]]; then
  # shellcheck source=lib/panelze-deploy-common.sh
  source "$DEPLOY_SCRIPTS/lib/panelze-deploy-common.sh"
else
  # shellcheck source=lib/panelze-deploy-common.sh
  source "$DEPLOY_SCRIPTS/lib/panelze-deploy-common.sh"
fi
export PANEL_ROOT

echo "==> Panel: $PANEL_ROOT"

if [[ ! -f "$PANEL_ROOT/.env" ]]; then
  echo "Hata: $PANEL_ROOT/.env yok. Önce sunucuya .env yerleştirin (Git dışı)." >&2
  exit 1
fi

if [[ "${PANELZE_SKIP_GIT_PULL:-0}" != "1" ]] && command -v git >/dev/null 2>&1; then
  if [[ -d "$REPO_ROOT/.git" ]]; then
    echo "==> git pull ($REPO_ROOT)"
    git -C "$REPO_ROOT" pull --ff-only
  elif [[ -d "$PANEL_ROOT/.git" ]]; then
    echo "==> git pull ($PANEL_ROOT)"
    git -C "$PANEL_ROOT" pull --ff-only
  fi
fi

# Engine'nin sudo ile çağırdığı yardımcı; panel deploy'da da repo sürümüne sabitlenir.
if [[ -f "$REPO_ROOT/deploy/host/panelze-security" ]]; then
  echo "==> /usr/local/sbin/panelze-security (repo ile güncelle)"
  sudo install -m 755 "$REPO_ROOT/deploy/host/panelze-security" /usr/local/sbin/panelze-security
  sudo ln -sfn /usr/local/sbin/panelze-security /usr/local/sbin/panelsar-security
  if [[ "$(id -u)" -eq 0 ]] && [[ -f "$DEPLOY_SCRIPTS/ensure-security-defaults.sh" ]]; then
    PANEL_ROOT="$PANEL_ROOT" bash "$DEPLOY_SCRIPTS/ensure-security-defaults.sh" || true
  fi
fi
if [[ -f "$REPO_ROOT/deploy/host/panelze-nginx-vhost" ]]; then
  echo "==> /usr/local/sbin/panelze-nginx-vhost (repo ile güncelle)"
  sudo install -m 755 "$REPO_ROOT/deploy/host/panelze-nginx-vhost" /usr/local/sbin/panelze-nginx-vhost
  sudo ln -sfn /usr/local/sbin/panelze-nginx-vhost /usr/local/sbin/panelsar-nginx-vhost
fi
if [[ -f "$REPO_ROOT/deploy/host/panelze-panel-update" ]]; then
  echo "==> /usr/local/sbin/panelze-panel-update (repo ile güncelle)"
  sudo install -m 755 "$REPO_ROOT/deploy/host/panelze-panel-update" /usr/local/sbin/panelze-panel-update
fi
for helper in panelze-post-install.sh repair-mysql-users.sh fix-hosting-permissions.sh; do
  if [[ -f "$DEPLOY_SCRIPTS/$helper" ]]; then
    base="${helper%.sh}"
    base="${base/panelze-/panelze-}"
    case "$helper" in
      panelze-post-install.sh) dest=panelze-post-install ;;
      repair-mysql-users.sh) dest=panelze-repair-mysql ;;
      fix-hosting-permissions.sh) dest=panelze-fix-hosting-perms ;;
    esac
    sudo install -m 755 "$DEPLOY_SCRIPTS/$helper" "/usr/local/sbin/$dest"
  fi
done

cd "$PANEL_ROOT"

echo "==> composer install"
if [[ "$(id -un)" == "$RUN_USER" ]]; then
  composer install --no-dev --optimize-autoloader --no-interaction
else
  sudo -u "$RUN_USER" composer install --no-dev --optimize-autoloader --no-interaction
fi

if grep -q '^DB_CONNECTION=mysql' "$PANEL_ROOT/.env" 2>/dev/null; then
  echo "==> MySQL kullanıcıları eşitleniyor"
  if [[ "$(id -u)" -eq 0 ]]; then
    bash "$DEPLOY_SCRIPTS/repair-mysql-users.sh"
  else
    sudo bash "$DEPLOY_SCRIPTS/repair-mysql-users.sh"
  fi
fi

echo "==> migrate"
panelze_run_artisan migrate --force
panelze_run_artisan panelze:init-outbound-mail --no-interaction 2>/dev/null || panelze_run_artisan panelze:init-outbound-mail --no-interaction 2>/dev/null || true

echo "==> optimize"
panelze_run_artisan config:cache
panelze_run_artisan route:cache
panelze_run_artisan view:cache
panelze_run_artisan schedule:clear-cache 2>/dev/null || true

if [[ -d "$FRONTEND_ROOT" ]] && [[ -f "$FRONTEND_ROOT/package.json" ]]; then
  if ! command -v npm >/dev/null 2>&1; then
    echo "Hata: npm yok; frontend derlenemiyor." >&2
    exit 1
  fi
  echo "==> frontend build ($FRONTEND_ROOT)"
  if [[ -f "$FRONTEND_ROOT/package-lock.json" ]]; then
    (cd "$FRONTEND_ROOT" && npm ci && VITE_BASE_URL="${VITE_BASE_URL:-}" npm run build)
  else
    (cd "$FRONTEND_ROOT" && npm install && VITE_BASE_URL="${VITE_BASE_URL:-}" npm run build)
  fi
  echo "==> rsync frontend dist -> panel/public (index.php korunur)"
  rsync -a --delete \
    --exclude index.php \
    --exclude .htaccess \
    "$FRONTEND_ROOT/dist/" "$PANEL_ROOT/public/"
fi

FIX_SCRIPT="$DEPLOY_SCRIPTS/fix-panel-permissions.sh"
if [[ -f "$FIX_SCRIPT" ]] && [[ -f "$PANEL_ROOT/artisan" ]]; then
  echo "==> panelze:fix-permissions"
  panelze_run_artisan panelze:fix-permissions || true
  echo "==> panel storage/bootstrap izinleri ($RUN_USER)"
  if [[ "$(id -u)" -eq 0 ]]; then
    env RUN_USER="$RUN_USER" RUN_GROUP="${RUN_GROUP:-$RUN_USER}" bash "$FIX_SCRIPT" "$PANEL_ROOT"
  else
    sudo env RUN_USER="$RUN_USER" RUN_GROUP="${RUN_GROUP:-$RUN_USER}" bash "$FIX_SCRIPT" "$PANEL_ROOT"
  fi
fi

echo "==> site dosya izinleri (data/www)"
if [[ "$(id -u)" -eq 0 ]]; then
  bash "$DEPLOY_SCRIPTS/fix-hosting-permissions.sh"
else
  sudo bash "$DEPLOY_SCRIPTS/fix-hosting-permissions.sh"
fi

echo "==> panelze:install-check"
panelze_run_artisan panelze:install-check --ping || true

echo "Tamam."
