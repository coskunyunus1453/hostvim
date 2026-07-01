#!/usr/bin/env bash
# Roundcube webmail.* için Let's Encrypt TLS + nginx 443 vhost.
# Kullanım: sudo configure-roundcube-ssl.sh <apex-domain> [acme-email]
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
RESOLVE_LIB="${SCRIPT_DIR}/lib/resolve-paths.sh"
if [[ ! -f "$RESOLVE_LIB" ]]; then
  for candidate in \
    /var/www/panelze/deploy/scripts/lib/resolve-paths.sh \
    /var/www/hostvim/deploy/scripts/lib/resolve-paths.sh; do
    if [[ -f "$candidate" ]]; then
      RESOLVE_LIB="$candidate"
      break
    fi
  done
fi
# shellcheck source=lib/resolve-paths.sh
source "$RESOLVE_LIB"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "configure-roundcube-ssl: root ile çalıştırılmalı" >&2
  exit 1
fi

APEX="${1:-}"
if [[ -z "$APEX" ]]; then
  echo "Kullanım: $0 <apex-domain> [acme-email]" >&2
  exit 1
fi

APEX="$(echo "$APEX" | tr '[:upper:]' '[:lower:]' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
HOST="webmail.${APEX}"

PANELZE_HOME="$(resolve_panelze_home)"
PANEL_ENV="$(resolve_panel_root)/.env"
CFG="${PANELZE_HOME}/data/ssl/letsencrypt/config"
WD="${PANELZE_HOME}/data/ssl/letsencrypt/work"
LD="${PANELZE_HOME}/data/ssl/letsencrypt/logs"
ROUNDCUBE_ROOT="${PANELZE_ROUNDCUBE_ROOT:-/usr/share/roundcube}"
SSL_SITE_DIR="/etc/nginx/sites-available/panelze-roundcube-ssl"
SSL_ENABLED_PREFIX="/etc/nginx/sites-enabled/51-roundcube-ssl-"

read_env() {
  local key="$1"
  [[ -f "$PANEL_ENV" ]] || return 0
  grep -E "^${key}=" "$PANEL_ENV" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r"'\''' || true
}

EMAIL="${2:-}"
if [[ -z "$EMAIL" ]]; then
  EMAIL="$(read_env PANELZE_LETS_ENCRYPT_EMAIL)"
fi
if [[ -z "$EMAIL" ]] && [[ -f /etc/panelze/engine.yaml ]]; then
  EMAIL="$(grep -E 'lets_encrypt_email:' /etc/panelze/engine.yaml 2>/dev/null | head -1 | sed 's/.*: *//; s/"//g; s/'\''//g' | tr -d ' ')"
fi
if [[ -z "$EMAIL" ]]; then
  echo "ACME e-postası gerekli (argüman veya PANELZE_LETS_ENCRYPT_EMAIL)." >&2
  exit 1
fi

PHP_SOCK="$(readlink -f /run/php/php*-fpm.sock 2>/dev/null | head -1 || true)"
if [[ -z "$PHP_SOCK" ]]; then
  PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo 8.2)"
  PHP_SOCK="/run/php/php${PHP_VER}-fpm.sock"
fi

mkdir -p "$CFG" "$WD" "$LD" "$SSL_SITE_DIR"
chmod 700 "$CFG" "$WD" "$LD" 2>/dev/null || true
install -d -m 0755 "${ROUNDCUBE_ROOT}/.well-known/acme-challenge"

patch_roundcube_http_acme() {
  local site
  for site in /etc/nginx/sites-available/panelze-roundcube /etc/nginx/sites-available/hostvim-roundcube; do
    [[ -f "$site" ]] || continue
    if grep -q 'panelze-roundcube-acme.conf' "$site"; then
      continue
    fi
    if grep -q 'location / {' "$site"; then
      sed -i '/location \/ {/i\  include snippets/panelze-roundcube-acme.conf;' "$site"
    else
      sed -i '/root \/usr\/share\/roundcube;/a\  include snippets/panelze-roundcube-acme.conf;' "$site"
    fi
  done
}

write_acme_snippet() {
  cat > /etc/nginx/snippets/panelze-roundcube-acme.conf <<NGX
location ^~ /.well-known/acme-challenge/ {
    default_type "text/plain";
    root ${ROUNDCUBE_ROOT};
    try_files \$uri =404;
    allow all;
}
NGX
}

write_ssl_vhost() {
  local chain="${CFG}/live/${HOST}/fullchain.pem"
  local key="${CFG}/live/${HOST}/privkey.pem"
  local out="${SSL_SITE_DIR}/${HOST}.conf"
  cat > "$out" <<NGX
# Panelze — Roundcube TLS (${HOST})
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${HOST};

    ssl_certificate ${chain};
    ssl_certificate_key ${key};
    ssl_session_timeout 1d;
    ssl_session_cache shared:PanelzeWebmailSSL:10m;

    add_header Strict-Transport-Security "max-age=31536000" always;

    root ${ROUNDCUBE_ROOT};
    index index.php;
    client_max_body_size 25M;

    include snippets/panelze-roundcube-signon.conf;

    location ^~ /.well-known/acme-challenge/ {
        default_type "text/plain";
        root ${ROUNDCUBE_ROOT};
        try_files \$uri =404;
        allow all;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    include snippets/panelze-roundcube-php.conf;
}
NGX

  # Eski hostvim-roundcube-php snippet adı
  if [[ -f /etc/nginx/snippets/hostvim-roundcube-php.conf ]] && ! grep -q 'panelze-roundcube-php.conf' "$out"; then
    sed -i 's|snippets/panelze-roundcube-php.conf|snippets/hostvim-roundcube-php.conf|' "$out"
  fi

  ln -sf "$out" "${SSL_ENABLED_PREFIX}${HOST}.conf"
}

certs_valid() {
  local chain="${CFG}/live/${HOST}/fullchain.pem"
  local key="${CFG}/live/${HOST}/privkey.pem"
  [[ -f "$chain" && -f "$key" ]] || return 1
  openssl x509 -in "$chain" -noout -checkend 86400 >/dev/null 2>&1 || return 1
  openssl x509 -in "$chain" -noout -text 2>/dev/null | grep -q "DNS:${HOST}" || return 1
  return 0
}

probe_acme_http() {
  local tok="pzwm-${RANDOM}${RANDOM}"
  mkdir -p "${ROUNDCUBE_ROOT}/.well-known/acme-challenge"
  printf '%s' "$tok" > "${ROUNDCUBE_ROOT}/.well-known/acme-challenge/${tok}"
  chmod 644 "${ROUNDCUBE_ROOT}/.well-known/acme-challenge/${tok}"
  local code
  code="$(curl -sS --max-time 10 -o /dev/null -w '%{http_code}' "http://${HOST}/.well-known/acme-challenge/${tok}" 2>/dev/null || echo 000)"
  rm -f "${ROUNDCUBE_ROOT}/.well-known/acme-challenge/${tok}"
  [[ "$code" == "200" ]]
}

write_acme_snippet
patch_roundcube_http_acme

if ! probe_acme_http; then
  if nginx -t 2>/dev/null; then
    systemctl reload nginx 2>/dev/null || true
    sleep 1
  fi
  if ! probe_acme_http; then
    echo "HTTP-01 erişilemiyor: http://${HOST}/.well-known/acme-challenge/ (DNS ve nginx kontrol edin)" >&2
    exit 1
  fi
fi

if ! certs_valid; then
  echo "==> Let's Encrypt: ${HOST}"
  certbot certonly --webroot -w "$ROUNDCUBE_ROOT" \
    -d "$HOST" --cert-name "$HOST" \
    --email "$EMAIL" --agree-tos -n --non-interactive \
    --config-dir "$CFG" --work-dir "$WD" --logs-dir "$LD" \
    --key-type ecdsa --preferred-challenges http
fi

if ! certs_valid; then
  echo "Sertifika doğrulanamadı: ${HOST}" >&2
  exit 1
fi

write_ssl_vhost

if nginx -t; then
  systemctl reload nginx
else
  echo "nginx -t başarısız" >&2
  exit 1
fi

echo "OK webmail SSL: https://${HOST}"
