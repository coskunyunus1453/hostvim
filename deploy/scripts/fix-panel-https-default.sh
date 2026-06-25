#!/usr/bin/env bash
# Panel IP erişimi: HTTPS istekleri api.kodsar.com vhost'una düşmesin.
# hostvim.conf'a 443 default_server (panel SPA) ekler.
#
# Sunucuda: bash deploy/scripts/fix-panel-https-default.sh
# Mac'ten:  bash deploy/scripts/fix-panel-https-default.sh --remote
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
SSH_KEY="${HOSTVIM_SSH_KEY:-$HOME/.ssh/hostvim_aapanel}"
SSH_HOST="${HOSTVIM_SSH_HOST:-root@207.180.237.13}"
REMOTE=0
[[ "${1:-}" == "--remote" ]] && REMOTE=1

run_fix() {
  local conf="${PANEL_NGINX_CONF:-/etc/nginx/sites-available/hostvim.conf}"
  local panel_public="${PANEL_PUBLIC:-/var/www/hostvim/panel/public}"
  local php_sock="${PHP_FPM_SOCK:-/run/php/php8.4-fpm.sock}"
  local ssl_dir="/etc/nginx/ssl"
  local ssl_crt="$ssl_dir/panel-default.crt"
  local ssl_key="$ssl_dir/panel-default.key"

  [[ -f "$conf" ]] || conf="/etc/nginx/sites-available/panelze.conf"
  [[ -f "$conf" ]] || { echo "nginx panel conf bulunamadı" >&2; exit 1; }

  if grep -q 'listen 443 ssl.*default_server' "$conf" 2>/dev/null; then
    echo "==> HTTPS default_server zaten var: $conf"
    nginx -t && systemctl reload nginx
    return 0
  fi

  echo "==> Panel HTTPS self-signed sertifika"
  mkdir -p "$ssl_dir"
  if [[ ! -f "$ssl_crt" || ! -f "$ssl_key" ]]; then
    openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
      -keyout "$ssl_key" -out "$ssl_crt" \
      -subj "/CN=panel-default/O=HostVim/C=TR" \
      -addext "subjectAltName=IP:207.180.237.13,DNS:localhost"
    chmod 600 "$ssl_key"
  fi

  echo "==> HTTPS server bloğu ekleniyor: $conf"
  cp -a "$conf" "${conf}.bak.$(date +%Y%m%d%H%M%S)"

  cat >> "$conf" <<NGX

# Panel — HTTPS varsayılan sunucu (IP ile erişim; api.kodsar.com /docs yönlendirmesini önler)
server {
    listen 443 ssl http2 default_server;
    listen [::]:443 ssl http2 default_server;
    server_name _ 207.180.237.13;

    ssl_certificate ${ssl_crt};
    ssl_certificate_key ${ssl_key};
    ssl_session_timeout 1d;
    ssl_session_cache shared:PanelSSL:10m;

    root ${panel_public};
    index index.html index.php;

    client_max_body_size 128M;
    fastcgi_read_timeout 1800s;
    fastcgi_send_timeout 1800s;

    location ~ /\.(?!well-known) {
        deny all;
    }

    location ^~ /engine-ws/ {
        proxy_pass http://127.0.0.1:9090/ws/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_read_timeout 86400;
        proxy_send_timeout 86400;
        proxy_buffering off;
    }

    location = /phpmyadmin {
        return 301 /phpmyadmin/;
    }

    location ^~ /phpmyadmin/ {
        root /usr/share/;
        index index.php;
        try_files \$uri \$uri/ /phpmyadmin/index.php?\$is_args\$args;
        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
            fastcgi_pass unix:${php_sock};
            fastcgi_read_timeout 1800s;
        }
    }

    location ^~ /api/ {
        rewrite ^/api/(.*)$ /index.php/api/\$1?\$query_string last;
    }

    location ^~ /sanctum/ {
        rewrite ^/sanctum/(.*)$ /index.php/sanctum/\$1?\$query_string last;
    }

    location = /up {
        try_files \$uri /index.php?\$is_args\$args;
    }

    location ^~ /admin/index.php/ {
        rewrite ^/admin/index\.php(.*)$ /index.php\$1 last;
    }

    location ~ ^/index\.php/ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
        fastcgi_pass unix:${php_sock};
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_pass unix:${php_sock};
    }

    location ^~ /admin/admin/assets/ {
        rewrite ^/admin/admin/assets/(.*)$ /assets/\$1 break;
        try_files \$uri =404;
        access_log off;
    }

    location ^~ /admin/assets/ {
        rewrite ^/admin/assets/(.*)$ /assets/\$1 break;
        try_files \$uri =404;
        access_log off;
    }

    location ^~ /assets/ {
        try_files \$uri =404;
        access_log off;
    }

    location ^~ /admin/ {
        try_files \$uri \$uri/ /index.html;
    }

    location = /pma-signon {
        rewrite ^ /index.php/pma-signon\$is_args\$args last;
    }

    location = /webmail-signon {
        rewrite ^ /index.php/webmail-signon\$is_args\$args last;
    }

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_min_length 256;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
}
NGX

  nginx -t
  systemctl reload nginx
  echo "==> nginx yenilendi"

  echo "==> Doğrulama"
  curl -sk -o /dev/null -w "HTTPS / → %{http_code}\n" https://127.0.0.1/
  curl -sk -o /dev/null -w "HTTPS /admin/ → %{http_code}\n" https://127.0.0.1/admin/
  curl -s -o /dev/null -w "HTTP /admin/ → %{http_code}\n" http://127.0.0.1/admin/
}

if [[ "$REMOTE" == "1" ]]; then
  rsync -az -e "ssh -i $SSH_KEY -o StrictHostKeyChecking=no" \
    "$SCRIPT_DIR/fix-panel-https-default.sh" \
    "${SSH_HOST}:/tmp/fix-panel-https-default.sh"
  ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$SSH_HOST" "bash /tmp/fix-panel-https-default.sh"
else
  run_fix
fi
