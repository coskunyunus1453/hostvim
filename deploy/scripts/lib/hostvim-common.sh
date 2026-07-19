#!/usr/bin/env bash
# HostVim — panel + satış sitesi ortak kurulum yardımcıları (source ile kullanın).
#   source deploy/scripts/lib/hostvim-common.sh

hostvim_common_loaded() { :; }

hostvim_repo_root() {
  if [[ -n "${HOSTVIM_REPO_ROOT:-}" && -d "$HOSTVIM_REPO_ROOT/deploy" ]]; then
    printf '%s\n' "$HOSTVIM_REPO_ROOT"
    return 0
  fi
  local script="${BASH_SOURCE[1]:-${BASH_SOURCE[0]}}"
  local dir
  dir="$(cd "$(dirname "$script")" && pwd)"
  if [[ "$dir" == */deploy/scripts/lib ]]; then
    cd "$dir/../../.." && pwd
    return 0
  fi
  if [[ "$dir" == */deploy/scripts ]]; then
    cd "$dir/../.." && pwd
    return 0
  fi
  pwd
}

hostvim_resolve_paths() {
  if [[ -z "${PANELZE_HOME:-}" ]]; then
    if [[ -d /var/www/panelze/panel ]]; then
      PANELZE_HOME=/var/www/panelze
    elif [[ -d /var/www/hostvim/panel ]]; then
      PANELZE_HOME=/var/www/hostvim
    else
      PANELZE_HOME=/var/www/panelze
    fi
  fi
  export PANELZE_HOME
  PANEL_ROOT="${PANEL_ROOT:-$PANELZE_HOME/panel}"
  STORE_ROOT="${STORE_ROOT:-}"
  STORE_DOMAIN="${STORE_DOMAIN:-}"
  STORE_URL="${STORE_URL:-}"
  PANEL_PUBLIC_HOST="${PANEL_PUBLIC_HOST:-}"
  if [[ -z "$PANEL_PUBLIC_HOST" ]]; then
    if [[ -f "$PANEL_ROOT/.env" ]]; then
      PANEL_PUBLIC_HOST="$(hostvim_read_env_value "$PANEL_ROOT/.env" APP_URL | sed -E 's#^https?://##; s#/$##')"
    fi
  fi
  if [[ -z "$STORE_ROOT" && -d "$PANELZE_HOME/data/www/hostvim.com/public_html" ]]; then
    STORE_ROOT="$PANELZE_HOME/data/www/hostvim.com/public_html"
    STORE_DOMAIN="${STORE_DOMAIN:-hostvim.com}"
  fi
  STORE_URL="${STORE_URL:-${STORE_DOMAIN:+https://${STORE_DOMAIN}}}"
  PANEL_URL="${PANEL_URL:-${PANEL_PUBLIC_HOST:+https://${PANEL_PUBLIC_HOST}}}"
  export PANEL_ROOT STORE_ROOT STORE_DOMAIN STORE_URL PANEL_URL PANEL_PUBLIC_HOST
}

hostvim_read_env_value() {
  local env_file="$1"
  local key="$2"
  grep -E "^${key}=" "$env_file" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r' | sed 's/^["'\''"]//; s/["'\''"]$//'
}

hostvim_env_set() {
  local env_file="$1"
  local key="$2"
  local value="$3"
  touch "$env_file"
  if grep -q "^${key}=" "$env_file" 2>/dev/null; then
    sed -i "/^${key}=/d" "$env_file"
  fi
  printf '%s=%s\n' "$key" "$value" >> "$env_file"
}

hostvim_detect_web_user() {
  if [[ -n "${HOSTVIM_WEB_USER:-}" ]]; then
    printf '%s\n' "$HOSTVIM_WEB_USER"
    return 0
  fi
  if id www-data &>/dev/null; then
    printf '%s\n' www-data
    return 0
  fi
  if [[ -f "${PANEL_ROOT}/artisan" ]]; then
    stat -c '%U' "${PANEL_ROOT}/artisan" 2>/dev/null || stat -f '%Su' "${PANEL_ROOT}/artisan" 2>/dev/null || true
    return 0
  fi
  printf '%s\n' "$(id -un)"
}

hostvim_resolve_store_owner() {
  local owner="${1:-}"
  if [[ -n "$owner" ]] && [[ ! "$owner" =~ ^[0-9]+$ ]] && id "$owner" &>/dev/null; then
    printf '%s\n' "$owner"
    return 0
  fi
  if id pk-hostvim-com &>/dev/null; then
    printf '%s\n' pk-hostvim-com
    return 0
  fi
  hostvim_detect_web_user
}

hostvim_resolve_store_group() {
  local group="${1:-}"
  local user
  user="$(hostvim_resolve_store_owner)"
  if [[ "$user" == "pk-hostvim-com" ]] && getent group panelze-hosting &>/dev/null; then
    printf '%s\n' panelze-hosting
    return 0
  fi
  if [[ -n "$group" ]] && getent group "$group" &>/dev/null; then
    printf '%s\n' "$group"
    return 0
  fi
  if getent group panelze-hosting &>/dev/null; then
    printf '%s\n' panelze-hosting
    return 0
  fi
  printf '%s\n' "$user"
}

hostvim_detect_store_user() {
  if [[ -n "${HOSTVIM_STORE_USER:-}" ]]; then
    printf '%s\n' "$HOSTVIM_STORE_USER"
    return 0
  fi
  # PanelKafes üretiminde FPM kullanıcısı her zaman doğru kaynak; rsync ile gelen
  # Mac sahipliği (501/staff) artisan dosyasından yanlış kullanıcı seçilmesin.
  if id pk-hostvim-com &>/dev/null; then
    printf '%s\n' pk-hostvim-com
    return 0
  fi
  hostvim_resolve_paths
  local owner=""
  if [[ -f "${STORE_ROOT}/artisan" ]]; then
    owner="$(stat -c '%U' "${STORE_ROOT}/artisan" 2>/dev/null || stat -f '%Su' "${STORE_ROOT}/artisan" 2>/dev/null || true)"
  fi
  hostvim_resolve_store_owner "$owner"
}

hostvim_detect_store_group() {
  if [[ -n "${HOSTVIM_STORE_GROUP:-}" ]]; then
    printf '%s\n' "$HOSTVIM_STORE_GROUP"
    return 0
  fi
  hostvim_resolve_paths
  local group=""
  if [[ -f "${STORE_ROOT}/artisan" ]]; then
    group="$(stat -c '%G' "${STORE_ROOT}/artisan" 2>/dev/null || stat -f '%Sg' "${STORE_ROOT}/artisan" 2>/dev/null || true)"
  fi
  hostvim_resolve_store_group "$group"
}

hostvim_run_as_web() {
  local user
  user="$(hostvim_detect_web_user)"
  if [[ "$(id -un)" == "$user" ]]; then
    "$@"
  else
    sudo -u "$user" "$@"
  fi
}

hostvim_run_as_store() {
  local user
  user="$(hostvim_detect_store_user)"
  if [[ "$(id -un)" == "$user" ]]; then
    "$@"
  else
    sudo -u "$user" "$@"
  fi
}

hostvim_panel_integration_files() {
  cat <<'EOF'
app/Services/Integrations/StoreFulfillmentService.php
app/Services/Billing/OrderService.php
app/Services/Billing/InvoiceService.php
app/Services/Domain/DomainRegistrarService.php
app/Http/Controllers/Api/Integrations/StoreIntegrationController.php
app/Models/OrderItem.php
resources/views/emails
app/Http/Middleware/AuthenticateStoreIntegration.php
app/Models/Order.php
database/migrations/2026_06_22_100000_add_store_order_number_to_orders.php
database/migrations/2026_06_25_100000_order_items_registrar_api.php
app/Services/Integrations/StoreSettingsApplier.php
app/Http/Controllers/Api/Integrations/StoreSettingsSyncController.php
routes/api.php
config/panelze.php
bootstrap/app.php
app/Providers/AppServiceProvider.php
EOF
}

hostvim_rsync_store() {
  local repo="$1"
  local dst="$2"
  local ssh_cmd="${3:-}"
  echo "==> Store rsync -> $dst"
  # storage + bootstrap/cache asla rsync ile taşınmaz:
  # Mac'teki 501:staff sahipliği ve dev-only provider önbelleği (Pail vb.) üretimde 500 üretir.
  local -a _rsync_excludes=(
    --exclude vendor
    --exclude node_modules
    --exclude .env
    --exclude storage
    --exclude bootstrap/cache
    --exclude .git
  )
  if [[ -n "$ssh_cmd" ]]; then
    rsync -az --delete \
      "${_rsync_excludes[@]}" \
      -e "$ssh_cmd" \
      "$repo/store/" "$dst"
  else
    rsync -az --delete \
      "${_rsync_excludes[@]}" \
      "$repo/store/" "$dst"
  fi
}

hostvim_rsync_store_assets() {
  local repo="$1"
  local dst="$2"
  local ssh_cmd="${3:-}"
  local blog_src="$repo/store/storage/app/public/blog"
  [[ -d "$blog_src" ]] || return 0
  echo "==> Store blog görselleri rsync"
  if [[ -n "$ssh_cmd" ]]; then
    rsync -az -e "$ssh_cmd" "$blog_src/" "${dst}storage/app/public/blog/"
  else
    mkdir -p "${dst}storage/app/public/blog"
    rsync -az "$blog_src/" "${dst}storage/app/public/blog/"
  fi
}

hostvim_post_rsync_store() {
  hostvim_resolve_paths
  local user group
  user="$(hostvim_detect_store_user)"
  group="$(hostvim_detect_store_group)"
  if [[ "$(id -u)" -eq 0 ]] && [[ -d "$STORE_ROOT" ]]; then
    echo "==> Store kod sahipliği: $user:$group (rsync sonrası)"
    find "$STORE_ROOT" \( -path "$STORE_ROOT/vendor" -o -path "$STORE_ROOT/.env" \) -prune \
      -o -exec chown "$user:$group" {} + 2>/dev/null || true
  fi
  hostvim_ensure_store_runtime_dirs
  hostvim_fix_store_permissions
  hostvim_sanitize_store_bootstrap_cache
}

hostvim_rsync_panel_integration() {
  local repo="$1"
  local panel_root="$2"
  local ssh_host="${3:-}"
  local ssh_key="${4:-}"
  echo "==> Panel store entegrasyonu"
  while IFS= read -r f; do
    [[ -z "$f" ]] && continue
    if [[ -n "$ssh_host" ]]; then
      rsync -az -e "ssh -i $ssh_key -o StrictHostKeyChecking=no" \
        "$repo/panel/$f" "${ssh_host}:${panel_root}/$f"
    else
      mkdir -p "$(dirname "$panel_root/$f")"
      rsync -az "$repo/panel/$f" "$panel_root/$f"
    fi
  done < <(hostvim_panel_integration_files)
}

hostvim_rsync_deploy_helpers() {
  local repo="$1"
  local panelze_home="$2"
  local ssh_host="${3:-}"
  local ssh_key="${4:-}"
  if [[ -n "$ssh_host" ]]; then
    rsync -az -e "ssh -i $ssh_key -o StrictHostKeyChecking=no" \
      "$repo/deploy/scripts/lib/hostvim-common.sh" \
      "${ssh_host}:${panelze_home}/deploy/scripts/lib/"
    rsync -az -e "ssh -i $ssh_key -o StrictHostKeyChecking=no" \
      "$repo/deploy/scripts/install-hostvim-full.sh" \
      "$repo/deploy/scripts/deploy-store.sh" \
      "$repo/deploy/scripts/fix-store-permissions.sh" \
      "${ssh_host}:${panelze_home}/deploy/scripts/"
  else
    mkdir -p "$panelze_home/deploy/scripts/lib"
    rsync -az "$repo/deploy/scripts/lib/hostvim-common.sh" "$panelze_home/deploy/scripts/lib/"
    rsync -az "$repo/deploy/scripts/install-hostvim-full.sh" "$repo/deploy/scripts/deploy-store.sh" \
      "$repo/deploy/scripts/fix-store-permissions.sh" \
      "$panelze_home/deploy/scripts/" 2>/dev/null || true
  fi
}

hostvim_rsync_repo_for_panel() {
  local repo="$1"
  local dst="$2"
  local ssh_cmd="${3:-}"
  echo "==> Tam repo rsync (panel kurulumu) -> $dst"
  if [[ -n "$ssh_cmd" ]]; then
    rsync -az --delete \
      --exclude vendor \
      --exclude node_modules \
      --exclude .env \
      --exclude storage \
      --exclude .git \
      --exclude '*/vendor' \
      --exclude 'frontend/node_modules' \
      --exclude 'store/storage' \
      --exclude 'store/bootstrap/cache' \
      --exclude 'panel/storage' \
      --exclude 'landing/storage' \
      --exclude 'data/www' \
      --exclude 'data/vhosts' \
      --exclude 'data/apache-vhosts' \
      --exclude 'data/logs' \
      --exclude 'data/ssl' \
      --exclude 'data/backups' \
      -e "$ssh_cmd" \
      "$repo/" "$dst/"
  else
    rsync -az --delete \
      --exclude vendor \
      --exclude node_modules \
      --exclude .env \
      --exclude storage \
      --exclude .git \
      --exclude '*/vendor' \
      --exclude 'frontend/node_modules' \
      --exclude 'store/storage' \
      --exclude 'store/bootstrap/cache' \
      --exclude 'panel/storage' \
      --exclude 'landing/storage' \
      --exclude 'data/www' \
      --exclude 'data/vhosts' \
      --exclude 'data/apache-vhosts' \
      --exclude 'data/logs' \
      --exclude 'data/ssl' \
      --exclude 'data/backups' \
      "$repo/" "$dst/"
  fi
}

hostvim_ensure_panel_secret() {
  hostvim_resolve_paths
  if [[ ! -f "$PANEL_ROOT/.env" ]]; then
    echo "Hata: Panel .env yok: $PANEL_ROOT/.env" >&2
    echo "Önce panel kurulumu yapın (HOSTVIM_INSTALL_PANEL=1) veya .env oluşturun." >&2
    return 1
  fi
  local secret
  secret="$(hostvim_read_env_value "$PANEL_ROOT/.env" PANELZE_STORE_SECRET)"
  if [[ -z "$secret" ]]; then
    secret="$(openssl rand -hex 32)"
    hostvim_env_set "$PANEL_ROOT/.env" PANELZE_STORE_SECRET "$secret"
  fi
  hostvim_env_set "$PANEL_ROOT/.env" PANELZE_STORE_PANEL_URL "$PANEL_URL"
  hostvim_env_set "$PANEL_ROOT/.env" PANELZE_PANEL_URL "$PANEL_URL"
  printf '%s\n' "$secret"
}

hostvim_ensure_store_env() {
  hostvim_resolve_paths
  local template="${HOSTVIM_STORE_ENV_TEMPLATE:-}"
  if [[ -z "$template" ]]; then
    for candidate in \
      "$STORE_ROOT/deploy/.env.production" \
      "$(hostvim_repo_root)/store/deploy/.env.production" \
      "$STORE_ROOT/.env.example" \
      "$(hostvim_repo_root)/store/.env.example"; do
      if [[ -f "$candidate" ]]; then
        template="$candidate"
        break
      fi
    done
  fi

  if [[ ! -f "$STORE_ROOT/.env" ]]; then
    if [[ -z "$template" || ! -f "$template" ]]; then
      echo "Hata: Store .env şablonu bulunamadı." >&2
      return 1
    fi
    echo "==> Store .env oluşturuluyor ($template)"
    cp "$template" "$STORE_ROOT/.env"
    hostvim_env_set "$STORE_ROOT/.env" APP_URL "$STORE_URL"
    hostvim_env_set "$STORE_ROOT/.env" APP_ENV production
    hostvim_env_set "$STORE_ROOT/.env" APP_DEBUG false
    hostvim_env_set "$STORE_ROOT/.env" SESSION_ENCRYPT true
    hostvim_env_set "$STORE_ROOT/.env" SESSION_DOMAIN "$STORE_DOMAIN"
    hostvim_env_set "$STORE_ROOT/.env" SESSION_SECURE_COOKIE true
    cd "$STORE_ROOT"
    if ! hostvim_read_env_value "$STORE_ROOT/.env" APP_KEY | grep -q '^base64:'; then
      hostvim_run_as_web php artisan key:generate --force --no-interaction
    fi
    echo ""
    echo "UYARI: $STORE_ROOT/.env içinde DB_* ve MAIL_* değerlerini kontrol edin."
  fi
}

hostvim_sync_store_integration_env() {
  hostvim_resolve_paths
  local secret="$1"
  hostvim_ensure_store_env || return 1

  hostvim_env_set "$STORE_ROOT/.env" PANELZE_API_URL "http://127.0.0.1"
  hostvim_env_set "$STORE_ROOT/.env" PANELZE_STORE_SECRET "$secret"
  hostvim_env_set "$STORE_ROOT/.env" PANELZE_PANEL_URL "$PANEL_URL"
  hostvim_env_set "$STORE_ROOT/.env" PANELZE_API_ALLOW_INTERNAL_HTTP true
  hostvim_env_set "$STORE_ROOT/.env" APP_ENV production
  hostvim_env_set "$STORE_ROOT/.env" APP_DEBUG false
  hostvim_env_set "$STORE_ROOT/.env" CACHE_STORE file
  hostvim_env_set "$STORE_ROOT/.env" SESSION_DRIVER file
  hostvim_env_set "$STORE_ROOT/.env" SESSION_ENCRYPT true
}

hostvim_prune_nginx_symlinks() {
  local f
  for f in /etc/nginx/sites-enabled/*; do
    [[ -e "$f" ]] || [[ ! -L "$f" ]] && continue
    rm -f "$f"
  done
}

# Tam repo rsync sonrası vhost dosyaları silinmişse nginx reload kırılır; otomatik onar.
hostvim_ensure_nginx_vhosts() {
  hostvim_resolve_paths
  [[ "$(id -u)" -eq 0 ]] || return 0
  [[ -f "$PANEL_ROOT/artisan" ]] || return 0

  if [[ -x /usr/local/sbin/panelze-nginx-vhost ]]; then
    :
  elif [[ -f "$(hostvim_repo_root)/deploy/host/panelze-nginx-vhost" ]]; then
    install -m 755 "$(hostvim_repo_root)/deploy/host/panelze-nginx-vhost" /usr/local/sbin/panelze-nginx-vhost
  fi

  hostvim_prune_nginx_symlinks

  local vhosts_dir="${PANELZE_HOME}/data/vhosts"
  mkdir -p "$vhosts_dir" "${PANELZE_HOME}/data/logs" "${PANELZE_HOME}/data/apache-vhosts" "${PANELZE_HOME}/data/ols-staging"
  # Engine (www-data) yazar; setgid + doğru sahiplik → root artıkları sahiplik kayması ile yazımı blokelemesin.
  chown www-data:www-data "$vhosts_dir" "${PANELZE_HOME}/data/logs" "${PANELZE_HOME}/data/apache-vhosts" "${PANELZE_HOME}/data/ols-staging" 2>/dev/null || true
  chmod 2775 "$vhosts_dir" "${PANELZE_HOME}/data/logs" "${PANELZE_HOME}/data/apache-vhosts" "${PANELZE_HOME}/data/ols-staging" 2>/dev/null || true
  # Geçmişte root olarak oluşmuş vhost/pool artıklarını www-data'ya devret (idempotent onarım).
  find "$vhosts_dir" "${PANELZE_HOME}/data/apache-vhosts" "${PANELZE_HOME}/data/ols-staging" -maxdepth 2 ! -user www-data -exec chown www-data:www-data {} + 2>/dev/null || true

  local active_count vhost_count
  active_count="$(cd "$PANEL_ROOT" && php artisan tinker --execute="echo (int) App\\Models\\Domain::where('status','active')->count();" 2>/dev/null | tr -d '\r' || echo 0)"
  vhost_count="$(find "$vhosts_dir" -maxdepth 1 -name 'panelze-*.conf' 2>/dev/null | wc -l | tr -d ' ')"

  if [[ "${active_count:-0}" -gt 0 ]] && [[ "${vhost_count:-0}" -lt "$(( active_count / 2 ))" ]]; then
    echo "==> UYARI: nginx vhost eksik ($vhost_count / $active_count) — yalnızca vhost yeniden uygulanıyor (dosyalar korunur)"
    local domains d
    domains="$(cd "$PANEL_ROOT" && php artisan tinker --execute="echo App\\Models\\Domain::where('status','active')->orderBy('name')->pluck('name')->implode(' ');" 2>/dev/null | tr -d '\r')"
    for d in $domains; do
      [[ -z "$d" ]] && continue
      echo "  reapply vhost: $d"
      cd "$PANEL_ROOT" && php artisan tinker --execute="
\$dom = App\\Models\\Domain::where('name', '$d')->first();
if (\$dom) {
  \$r = app(App\\Services\\EngineApiService::class)->reapplyWebServer(\$dom->name, (string)(\$dom->php_version ?? '8.2'), (string)(\$dom->server_type ?? 'nginx'));
  echo json_encode(\$r);
}
" >/dev/null 2>&1 || true
    done
    hostvim_prune_nginx_symlinks
    nginx -t >/dev/null 2>&1 && systemctl reload nginx 2>/dev/null || true
  fi
}

hostvim_panel_post_deploy() {
  hostvim_resolve_paths
  [[ -f "$PANEL_ROOT/artisan" ]] || { echo "Panel atlanıyor (artisan yok)"; return 0; }

  echo "==> Panel: composer + migrate"
  cd "$PANEL_ROOT"
  hostvim_run_as_web composer install --no-dev --optimize-autoloader --no-interaction \
    2>/dev/null || composer install --no-dev --optimize-autoloader --no-interaction
  php artisan route:clear 2>/dev/null || true
  hostvim_run_as_web php artisan migrate --force --no-interaction
  hostvim_run_as_web php artisan config:cache
  hostvim_run_as_web php artisan route:cache
}

hostvim_store_post_deploy() {
  hostvim_resolve_paths
  [[ -f "$STORE_ROOT/artisan" ]] || { echo "Hata: Store artisan yok: $STORE_ROOT" >&2; return 1; }

  echo "==> Store: composer + migrate + seed"
  cd "$STORE_ROOT"
  hostvim_run_as_store composer install --no-dev --optimize-autoloader --no-interaction \
    2>/dev/null || composer install --no-dev --optimize-autoloader --no-interaction

  hostvim_run_as_store php artisan migrate --force --no-interaction

  if [[ "${HOSTVIM_STORE_SEED:-0}" == "1" ]]; then
    echo "==> Store: db:seed"
    hostvim_run_as_store php artisan db:seed --force --no-interaction
  else
    local user_count
    user_count="$(cd "$STORE_ROOT" && php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo (int) \\App\\Models\\User::count();
" 2>/dev/null || echo 0)"
    if [[ "${user_count:-0}" -lt 1 ]]; then
      echo "==> Store: ilk kurulum — db:seed"
      hostvim_run_as_store php artisan db:seed --force --no-interaction
    fi
  fi

  hostvim_ensure_store_runtime_dirs
  hostvim_fix_store_permissions
  hostvim_sanitize_store_bootstrap_cache

  hostvim_run_as_store php artisan filament:assets --no-interaction 2>/dev/null || true
  hostvim_run_as_store php artisan filament:optimize --no-interaction 2>/dev/null || true
  hostvim_run_as_store php artisan icons:cache --no-interaction 2>/dev/null || true
  hostvim_run_as_store php artisan vendor:publish --tag=livewire:assets --force --no-interaction 2>/dev/null || true
  hostvim_run_as_store php artisan storage:link --force 2>/dev/null || true

  local public_dir="$STORE_ROOT/public"
  if [[ -d "$public_dir" && ! -e "$STORE_ROOT/storage" ]]; then
    :
  fi
  if [[ -d "$public_dir" ]] && [[ ! -L "$STORE_ROOT/public/../storage" ]] && [[ "$STORE_ROOT" == */public_html ]]; then
    if [[ ! -e "$STORE_ROOT/storage" ]] && [[ -d "$(dirname "$STORE_ROOT")/storage" ]]; then
      ln -sfn "$(dirname "$STORE_ROOT")/storage" "$STORE_ROOT/storage" 2>/dev/null || true
    fi
  fi

  hostvim_run_as_store php artisan config:cache
  hostvim_run_as_store php artisan route:cache
  hostvim_run_as_store php artisan view:cache 2>/dev/null || hostvim_run_as_store php artisan view:clear
  hostvim_run_as_store php artisan event:cache 2>/dev/null || true
  hostvim_run_as_store php artisan seo:publish-static 2>/dev/null || true

  hostvim_fix_store_permissions

  echo "==> Store: sayfa önbelleği ısıtma"
  # HTTPS üzerinden ısıt (http://127.0.0.1 301 ile https'e yönlenir ve sayfayı
  # render etmeden döner; eski hali ısıtmayı etkisiz bırakıyordu). -k: yerel sertifika.
  local _warm
  for _warm in / /blog /urunler /web-hosting /bulut-sunucu; do
    curl -sk -o /dev/null -H "Host: ${STORE_DOMAIN}" "https://127.0.0.1${_warm}" 2>/dev/null || true
  done
}

hostvim_ensure_store_runtime_dirs() {
  hostvim_resolve_paths
  [[ -d "$STORE_ROOT" ]] || return 0
  local user group
  user="$(hostvim_detect_store_user)"
  group="$(hostvim_detect_store_group)"
  mkdir -p \
    "$STORE_ROOT/storage/framework/cache/data" \
    "$STORE_ROOT/storage/framework/sessions" \
    "$STORE_ROOT/storage/framework/views" \
    "$STORE_ROOT/storage/logs" \
    "$STORE_ROOT/storage/app/public" \
    "$STORE_ROOT/bootstrap/cache"
  if [[ "$(id -u)" -eq 0 ]]; then
    chown -R "$user:$group" "$STORE_ROOT/storage" "$STORE_ROOT/bootstrap/cache" 2>/dev/null || true
  fi
}

# Mac/rsync ile gelen dev bootstrap önbelleğini (Pail vb.) üretimde yeniden kurar.
hostvim_sanitize_store_bootstrap_cache() {
  hostvim_resolve_paths
  [[ -f "$STORE_ROOT/artisan" ]] || return 0
  local packages="$STORE_ROOT/bootstrap/cache/packages.php"
  local needs_rebuild=0
  if [[ ! -f "$packages" ]]; then
    needs_rebuild=1
  elif grep -qE 'PailServiceProvider|CollisionServiceProvider|Laravel\\\\Sail' "$packages" 2>/dev/null; then
    needs_rebuild=1
  fi
  if [[ "$needs_rebuild" != "1" ]]; then
    return 0
  fi
  echo "==> Store bootstrap cache yeniden oluşturuluyor (dev provider temizliği)"
  cd "$STORE_ROOT"
  rm -f \
    "$STORE_ROOT/bootstrap/cache/packages.php" \
    "$STORE_ROOT/bootstrap/cache/services.php" \
    "$STORE_ROOT/bootstrap/cache/config.php"
  hostvim_fix_store_permissions
  hostvim_run_as_store php artisan package:discover --ansi --no-interaction 2>/dev/null || true
}

hostvim_fix_store_permissions() {
  hostvim_resolve_paths
  local user group
  user="$(hostvim_detect_store_user)"
  group="$(hostvim_detect_store_group)"

  hostvim_ensure_store_runtime_dirs

  for dir in "$STORE_ROOT/storage" "$STORE_ROOT/bootstrap/cache"; do
    [[ -d "$dir" ]] || continue
    chown -R "$user:$group" "$dir" 2>/dev/null || true
    find "$dir" -type d -exec chmod 2775 {} + 2>/dev/null || true
    find "$dir" -type f -exec chmod 664 {} + 2>/dev/null || true
  done

  if [[ -d "$STORE_ROOT/public" ]]; then
    chown -R "$user:$group" "$STORE_ROOT/public/js" "$STORE_ROOT/public/css" \
      "$STORE_ROOT/public/fonts" "$STORE_ROOT/public/vendor" 2>/dev/null || true
  fi

  echo "==> Store izinleri: $user:$group"
}

# Hafif sağlık kontrolü — cron/guard için; tam deploy değil.
hostvim_store_guard() {
  hostvim_resolve_paths
  [[ -f "$STORE_ROOT/artisan" ]] || return 0

  local user code probe need_perm_fix=0
  user="$(hostvim_detect_store_user)"

  # logs + file-cache + sessions: FPM (pk-hostvim-com) yazabilmeli.
  # Root ile artisan çalıştırılınca alt dizinler root'a geçer → Permission denied → 500.
  for probe in \
    "$STORE_ROOT/storage/logs/.hostvim-guard-probe" \
    "$STORE_ROOT/storage/framework/cache/data/.hostvim-guard-probe" \
    "$STORE_ROOT/storage/framework/sessions/.hostvim-guard-probe" \
    "$STORE_ROOT/storage/framework/views/.hostvim-guard-probe"
  do
    mkdir -p "$(dirname "$probe")" 2>/dev/null || true
    if ! (sudo -u "$user" touch "$probe" 2>/dev/null && sudo -u "$user" rm -f "$probe" 2>/dev/null); then
      need_perm_fix=1
      break
    fi
  done

  # Root'a geçmiş cache/session dosyaları (probe geçer ama alt dizin yazılamaz)
  if [[ "$need_perm_fix" != "1" ]] && [[ -d "$STORE_ROOT/storage/framework" ]]; then
    if find "$STORE_ROOT/storage/framework" "$STORE_ROOT/storage/logs" "$STORE_ROOT/bootstrap/cache" \
      -not -user "$user" 2>/dev/null | head -1 | grep -q .; then
      need_perm_fix=1
    fi
  fi

  if [[ "$need_perm_fix" == "1" ]]; then
    echo "==> Store guard: storage/cache yazılamıyor veya sahiplik kaymış — izin onarımı"
    hostvim_fix_store_permissions
    hostvim_sanitize_store_bootstrap_cache
  fi

  hostvim_sanitize_store_bootstrap_cache

  code="$(curl -sk -o /dev/null -w '%{http_code}' -H "Host: ${STORE_DOMAIN}" "https://127.0.0.1/" 2>/dev/null || echo 000)"
  if [[ "$code" != "200" ]]; then
    echo "==> Store guard: HTTP $code — tam onarım"
    hostvim_finalize_store || return 1
  fi
  return 0
}

hostvim_install_store_guard_cron() {
  hostvim_resolve_paths
  local script="${PANELZE_HOME}/deploy/scripts/fix-store-permissions.sh"
  [[ -f "$script" ]] || script="$(hostvim_repo_root)/deploy/scripts/fix-store-permissions.sh"
  [[ -f "$script" ]] || return 0
  echo "==> Store guard cron (5 dk)"
  cat > /etc/cron.d/hostvim-store-guard <<CRON
# HostVim store — rsync/izin kayması sonrası otomatik onarım
*/5 * * * * root bash ${script} --guard-only >/dev/null 2>&1
CRON
  chmod 644 /etc/cron.d/hostvim-store-guard
}

hostvim_fix_permissions() {
  hostvim_resolve_paths
  local panel_user panel_group
  panel_user="$(hostvim_detect_web_user)"
  panel_group="${HOSTVIM_WEB_GROUP:-$panel_user}"

  for dir in "$PANEL_ROOT/storage" "$PANEL_ROOT/bootstrap/cache"; do
    [[ -d "$dir" ]] || continue
    chown -R "$panel_user:$panel_group" "$dir" 2>/dev/null || true
    chmod -R ug+rwx "$dir" 2>/dev/null || true
  done

  hostvim_fix_store_permissions
}

hostvim_finalize_store() {
  hostvim_resolve_paths
  hostvim_ensure_store_runtime_dirs
  hostvim_fix_store_permissions
  hostvim_sanitize_store_bootstrap_cache
  if [[ "$(id -u)" -eq 0 ]]; then
    hostvim_install_store_scheduler
    hostvim_install_store_queue
    hostvim_install_store_guard_cron
  fi
  if [[ -f "$STORE_ROOT/artisan" ]]; then
    cd "$STORE_ROOT"
    # ÜRETİM ÖNBELLEĞİNİ KUR (SİLME!). Buradaki eski "optimize:clear" her deploy'un
    # sonunda config/route/view/event + Filament component cache'lerini siliyordu; bu da
    # admin panelini her istekte boot maliyetiyle 2-3 sn'ye çıkarıyordu. Store'u daima
    # optimize bırakmak için önbellekleri (idempotent) yeniden kuruyoruz.
    hostvim_run_as_store php artisan config:cache 2>/dev/null || true
    hostvim_run_as_store php artisan route:cache 2>/dev/null || true
    hostvim_run_as_store php artisan view:cache 2>/dev/null || true
    hostvim_run_as_store php artisan event:cache 2>/dev/null || true
    hostvim_run_as_store php artisan filament:optimize --no-interaction 2>/dev/null || true
  fi
  local code
  code="$(curl -sk -o /dev/null -w '%{http_code}' "${STORE_URL:-https://${STORE_DOMAIN:-hostvim.com}}/" 2>/dev/null || echo 000)"
  echo "==> Store HTTP: $code"
  if [[ "$code" != "200" ]]; then
    echo "UYARI: Store HTTP $code — storage izinlerini ve PHP-FPM kullanıcısını kontrol edin." >&2
    return 1
  fi
  return 0
}

hostvim_install_store_scheduler() {
  hostvim_resolve_paths
  local user
  user="$(hostvim_detect_store_user)"
  echo "==> Store scheduler cron — $user"
  cat > /etc/cron.d/hostvim-store <<CRON
* * * * * ${user} cd ${STORE_ROOT} && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
CRON
  chmod 644 /etc/cron.d/hostvim-store
}

hostvim_install_store_queue() {
  hostvim_resolve_paths
  local log_dir
  log_dir="$(dirname "$STORE_ROOT")/logs"
  mkdir -p "$log_dir" 2>/dev/null || log_dir="/var/log"
  local user group
  user="$(hostvim_detect_store_user)"
  group="$(hostvim_detect_store_group)"

  echo "==> Store queue worker (systemd) — $user:$group"
  cat > /etc/systemd/system/hostvim-store-queue.service <<UNIT
[Unit]
Description=HostVim Store Queue Worker
After=network.target mysql.service mariadb.service

[Service]
User=${user}
Group=${group}
Restart=always
RestartSec=5
WorkingDirectory=${STORE_ROOT}
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --timeout=120 --max-time=3600
StandardOutput=append:${log_dir}/hostvim-store-queue.log
StandardError=append:${log_dir}/hostvim-store-queue.log

[Install]
WantedBy=multi-user.target
UNIT
  systemctl daemon-reload
  systemctl enable hostvim-store-queue.service
  systemctl restart hostvim-store-queue.service
}

hostvim_patch_panel_nginx_api() {
  local patch_script patch_index
  patch_script="$(hostvim_repo_root)/deploy/scripts/patch-nginx-admin-api.sh"
  patch_index="$(hostvim_repo_root)/deploy/scripts/patch-nginx-index-php-api.sh"
  for conf in /etc/nginx/sites-enabled/hostvim.conf /etc/nginx/sites-enabled/panelze.conf; do
    [[ -f "$conf" ]] || continue
    [[ -f "$patch_index" ]] && bash "$patch_index" "$conf" 2>/dev/null || true
    [[ -f "$patch_script" ]] && bash "$patch_script" "$conf" 2>/dev/null || true
  done
}

hostvim_verify_integration() {
  hostvim_resolve_paths
  local secret code
  secret="$(hostvim_read_env_value "$PANEL_ROOT/.env" PANELZE_STORE_SECRET)"
  echo "==> Panel store API test"
  if curl -sf -H "Authorization: Bearer $secret" http://127.0.0.1/api/integrations/store/test | head -c 200; then
    echo ""
  else
    echo "UYARI: Store API test başarısız (panel nginx / secret kontrol edin)" >&2
  fi

  echo "==> Store HTTP test"
  code="$(curl -sk -o /dev/null -w '%{http_code}' "$STORE_URL/" 2>/dev/null || echo 000)"
  echo "${STORE_DOMAIN}: HTTP $code"

  echo "==> Filament admin route"
  cd "$STORE_ROOT"
  php artisan route:list --path=admin/muhasebe 2>/dev/null | head -5 || true
}

hostvim_full_setup() {
  hostvim_resolve_paths
  echo ""
  echo "╔══════════════════════════════════════════════════════════╗"
  echo "║  HostVim — Panel + Satış Sitesi Kurulum / Güncelleme     ║"
  echo "╚══════════════════════════════════════════════════════════╝"
  echo "  Panel:  $PANEL_ROOT"
  echo "  Store:  $STORE_ROOT"
  echo "  URL:    $STORE_URL"
  echo ""

  if [[ "${HOSTVIM_SKIP_PANEL:-0}" != "1" ]]; then
    local secret
    secret="$(hostvim_ensure_panel_secret)" || return 1
    hostvim_panel_post_deploy
    hostvim_sync_store_integration_env "$secret"
  fi

  if [[ "${HOSTVIM_SKIP_STORE:-0}" != "1" ]]; then
    hostvim_store_post_deploy || return 1
    hostvim_fix_permissions
    if [[ "$(id -u)" -eq 0 ]] && [[ "${HOSTVIM_SKIP_QUEUE:-0}" != "1" ]]; then
      hostvim_finalize_store
    fi
  fi

  if [[ "$(id -u)" -eq 0 ]]; then
    hostvim_patch_panel_nginx_api
  fi

  hostvim_verify_integration

  echo ""
  echo "══════════════════════════════════════════════════════════"
  echo "  Kurulum tamam."
  echo "  Satış sitesi:  $STORE_URL"
  echo "  Store admin:   $STORE_URL/admin"
  echo "  Panel:         $PANEL_URL"
  echo "══════════════════════════════════════════════════════════"
}
