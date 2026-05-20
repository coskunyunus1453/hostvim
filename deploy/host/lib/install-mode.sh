#!/usr/bin/env bash
# shellcheck shell=bash
# Ortak kurulum / güncelleme modu (install.sh ve install-production.sh tarafından source edilir).

hostvim_install_home() {
  echo "${HOSTVIM_HOME:-${PANELSAR_HOME:-/var/www/hostvim}}"
}

# fresh | update
hostvim_resolve_install_mode() {
  local home
  home="$(hostvim_install_home)"

  if [[ "${HOSTVIM_FRESH_INSTALL:-0}" == "1" ]] || [[ "${HOSTVIM_FRESH_INSTALL:-0}" == "yes" ]]; then
    echo "fresh"
    return 0
  fi
  if [[ "${RESET_PANEL_DB:-0}" == "1" ]] || [[ "${RESET_PANEL_DB:-0}" == "yes" ]]; then
    echo "fresh"
    return 0
  fi
  if [[ "${HOSTVIM_UPDATE_ONLY:-0}" == "1" ]] || [[ "${HOSTVIM_UPDATE_ONLY:-0}" == "yes" ]]; then
    echo "update"
    return 0
  fi

  if [[ -f "$home/panel/.env" ]]; then
    echo "update"
    return 0
  fi
  if [[ -d "$home/data/www" ]] && find "$home/data/www" -mindepth 1 -maxdepth 1 \( -type d -o -type f \) -print -quit 2>/dev/null | grep -q .; then
    echo "update"
    return 0
  fi

  echo "fresh"
}

hostvim_apply_update_safe_env() {
  export HOSTVIM_UPDATE_ONLY=1
  export RESET_PANEL_DB=0
  export HOSTVIM_FRESH_INSTALL=0
  export CLEAN_HOSTING_STATE_ON_RESET=0
  export HOSTVIM_PRESERVE_ADMIN_PASSWORD="${HOSTVIM_PRESERVE_ADMIN_PASSWORD:-1}"
  if [[ -z "${SKIP_APT:-}" ]]; then
    export SKIP_APT="${HOSTVIM_SKIP_APT_ON_UPDATE:-1}"
  fi
}

hostvim_apply_fresh_env() {
  : "${RESET_PANEL_DB:=0}"
  if [[ "${HOSTVIM_FRESH_INSTALL:-0}" == "1" ]] || [[ "${HOSTVIM_FRESH_INSTALL:-0}" == "yes" ]]; then
    export RESET_PANEL_DB=1
    export CLEAN_HOSTING_STATE_ON_RESET="${CLEAN_HOSTING_STATE_ON_RESET:-1}"
  fi
}

hostvim_print_install_mode_banner() {
  local mode="$1"
  if [[ "$mode" == "update" ]]; then
    echo "╔══════════════════════════════════════════════════════════════╗"
    echo "║  Hostvim GÜNCELLEME — panel DB, siteler ve MySQL korunur       ║"
    echo "║  Yalnızca kod + migrate (değişen tablolar) uygulanır.          ║"
    echo "╚══════════════════════════════════════════════════════════════╝"
  else
    echo "╔══════════════════════════════════════════════════════════════╗"
    echo "║  Hostvim YENİ KURULUM                                         ║"
    echo "║  Sıfırlamak için: HOSTVIM_FRESH_INSTALL=1 (veri silinir!)     ║"
    echo "╚══════════════════════════════════════════════════════════════╝"
  fi
}
