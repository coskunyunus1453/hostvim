#!/usr/bin/env bash
# shellcheck shell=bash
# curl | bash ile çalıştırıldığında BASH_SOURCE dizini güvenilmez; lib'i bul veya indir.

hostvim_source_install_mode_lib() {
  if declare -F hostvim_resolve_install_mode &>/dev/null; then
    return 0
  fi

  local candidates=()
  local s0="${BASH_SOURCE[1]:-${BASH_SOURCE[0]:-}}"
  if [[ -n "$s0" && "$s0" != bash && "$s0" != /bin/bash && "$s0" != - ]]; then
    local dir
    dir="$(cd "$(dirname "$s0")" 2>/dev/null && pwd)" || dir=""
    if [[ -n "$dir" && -f "$dir/lib/install-mode.sh" ]]; then
      candidates+=("$dir/lib/install-mode.sh")
    fi
  fi

  local home="${PANELZE_HOME:-${PANELSAR_HOME:-/var/www/panelze}}"
  candidates+=(
    "$home/deploy/host/lib/install-mode.sh"
    "/var/www/panelze/deploy/host/lib/install-mode.sh"
  )

  local c f="" tmp=""
  for c in "${candidates[@]}"; do
    if [[ -f "$c" ]]; then
      f="$c"
      break
    fi
  done

  if [[ -z "$f" ]]; then
    local branch="${PANELZE_BRANCH:-${PANELSAR_BRANCH:-main}}"
    local raw="${PANELZE_RAW_BASE:-https://raw.githubusercontent.com/coskunyunus1453/hostvim/${branch}}"
    tmp="$(mktemp)"
    if ! curl -fsSL "${raw}/deploy/host/lib/install-mode.sh" -o "$tmp" 2>/dev/null; then
      rm -f "$tmp"
      echo "Hata: install-mode.sh bulunamadı (yerel veya ${raw}/deploy/host/lib/install-mode.sh)." >&2
      return 1
    fi
    f="$tmp"
  fi

  # shellcheck source=install-mode.sh
  source "$f"
  [[ -n "$tmp" && "$f" == "$tmp" ]] && rm -f "$tmp"
}
