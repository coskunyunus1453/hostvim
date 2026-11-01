# PHP-FPM ProtectSystem=full altında panelze host araçlarının /etc yazabilmesi.
# Drop-in: ProtectSystem=false (ReadWritePaths bu sunucuda yeterli olmuyor).
# shellcheck shell=bash

install_php_fpm_hosttools_dropin() {
  local repo="${1:-${PANELZE_HOME:-/var/www/panelze}}"
  local src="$repo/deploy/systemd/php-fpm-panelze-hosttools.conf"
  local unit ver drop_dir dest restarted=0

  if [[ ! -f "$src" ]]; then
    echo "install-php-fpm-hosttools-dropin: kaynak yok: $src" >&2
    return 1
  fi

  if [[ "$(id -u)" -ne 0 ]]; then
    echo "install-php-fpm-hosttools-dropin: root gerekli" >&2
    return 1
  fi

  shopt -s nullglob
  for unit_path in /lib/systemd/system/php*-fpm.service /usr/lib/systemd/system/php*-fpm.service; do
    [[ -f "$unit_path" ]] || continue
    unit="$(basename "$unit_path")"
    drop_dir="/etc/systemd/system/${unit}.d"
    dest="${drop_dir}/panelze-hosttools.conf"
    install -d -m 0755 "$drop_dir"
    if [[ -f "$dest" ]] && cmp -s "$src" "$dest"; then
      continue
    fi
    install -m 0644 "$src" "$dest"
    restarted=1
  done
  shopt -u nullglob

  if [[ "$restarted" -eq 1 ]]; then
    systemctl daemon-reload
    for ver in 8.4 8.3 8.2 8.1; do
      if systemctl list-unit-files "php${ver}-fpm.service" &>/dev/null \
        && systemctl is-enabled --quiet "php${ver}-fpm" 2>/dev/null; then
        systemctl restart "php${ver}-fpm" || true
      fi
    done
  fi

  echo "OK php-fpm panelze hosttools drop-in"
}
