#!/usr/bin/env bash
# Panelze deploy — ortak yardımcılar (source ile kullanın).
#   source "$(dirname "$0")/lib/panelze-deploy-common.sh"

hostvim_deploy_common_loaded() { :; }

hostvim_resolve_hostvim_home() {
  local panel_root="${1:-${PANEL_ROOT:-}}"
  if [[ -n "${PANELZE_HOME:-}" ]]; then
    printf '%s\n' "$PANELZE_HOME"
    return 0
  fi
  if [[ -n "$panel_root" ]]; then
    cd "$(dirname "$panel_root")" && pwd
    return 0
  fi
  printf '%s\n' "/var/www/hostvim"
}

hostvim_www_data_home() {
  local panel_root="${1:-${PANEL_ROOT:-}}"
  if [[ -n "$panel_root" ]]; then
    printf '%s\n' "$panel_root"
    return 0
  fi
  printf '%s\n' "$(hostvim_resolve_hostvim_home)/panel"
}

hostvim_ensure_www_data_config_dir() {
  local panel_root
  panel_root="$(hostvim_www_data_home "$1")"
  mkdir -p "$panel_root/storage/framework/.config"
  chown -R "${RUN_USER:-www-data}:${RUN_GROUP:-www-data}" "$panel_root/storage/framework/.config" 2>/dev/null || true
}

hostvim_run_artisan() {
  local panel_root="${PANEL_ROOT:?PANEL_ROOT gerekli}"
  local run_user="${RUN_USER:-www-data}"
  local home
  home="$(hostvim_www_data_home "$panel_root")"
  hostvim_ensure_www_data_config_dir "$panel_root"
  if [[ "$(id -un)" == "$run_user" ]]; then
    env HOME="$home" XDG_CONFIG_HOME="$home/storage/framework/.config" \
      php "$panel_root/artisan" "$@"
  else
    sudo -u "$run_user" env HOME="$home" XDG_CONFIG_HOME="$home/storage/framework/.config" \
      php "$panel_root/artisan" "$@"
  fi
}

hostvim_mysql_admin() {
  local -a cmd=()
  if [[ -n "${MYSQL_ROOT_PASS:-}" ]]; then
    if command -v mariadb >/dev/null 2>&1; then
      cmd=(mariadb -u root "-p${MYSQL_ROOT_PASS}")
    else
      cmd=(mysql -u root "-p${MYSQL_ROOT_PASS}")
    fi
  elif [[ -s /root/.my.cnf ]]; then
    if command -v mariadb >/dev/null 2>&1; then
      cmd=(mariadb --defaults-extra-file=/root/.my.cnf)
    else
      cmd=(mysql --defaults-extra-file=/root/.my.cnf)
    fi
  elif command -v mariadb >/dev/null 2>&1; then
    cmd=(mariadb)
  else
    cmd=(mysql)
  fi
  "${cmd[@]}" "$@"
}

hostvim_read_env_value() {
  local env_file="$1"
  local key="$2"
  grep -E "^${key}=" "$env_file" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r' | sed 's/^["'\''"]//; s/["'\''"]$//'
}
