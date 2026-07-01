# Roundcube webmail TLS aracı — source ile kullanın.
# shellcheck shell=bash

install_roundcube_ssl_tool() {
  local repo="${1:-${PANELZE_HOME:-/var/www/panelze}}"
  local wrapper="$repo/deploy/host/panelze-configure-roundcube-ssl"
  local script="$repo/deploy/scripts/configure-roundcube-ssl.sh"
  if [[ -f "$wrapper" ]]; then
    install -m 755 "$wrapper" /usr/local/sbin/panelze-configure-roundcube-ssl
  elif [[ -f "$script" ]]; then
    install -m 755 "$script" /usr/local/sbin/panelze-configure-roundcube-ssl
  fi
}

ensure_roundcube_ssl_deploy_steps() {
  local script_dir="${1:-}"
  local panel_root="${2:-}"
  [[ -n "$script_dir" ]] || return 0
  if [[ -f "$script_dir/configure-roundcube-signon.sh" ]] \
    && dpkg-query -W -f='${Status}' roundcube-core 2>/dev/null | grep -q 'install ok'; then
    bash "$script_dir/configure-roundcube-signon.sh" || true
  fi
  if [[ -f "$script_dir/configure-roundcube-ssl.sh" ]] \
    && dpkg-query -W -f='${Status}' roundcube-core 2>/dev/null | grep -q 'install ok'; then
    install_roundcube_ssl_tool "${PANELZE_HOME:-$(cd "$script_dir/../.." && pwd)}"
  fi
  if [[ -n "$panel_root" && -f "$panel_root/artisan" ]] \
    && dpkg-query -W -f='${Status}' roundcube-core 2>/dev/null | grep -q 'install ok'; then
    (cd "$panel_root" && php artisan panelze:ensure-webmail-ssl --all --no-interaction) \
      || echo "Uyarı: webmail SSL toplu kurulumu atlandı veya kısmen başarısız" >&2
  fi
}
