sunucu#!/usr/bin/env bash
set -euo pipefail

# HostVim store — sunucu kurulum / güncelleme (composer, migrate, cache).
# ISPmanager: Laravel kökü public_html içindedir.
#
# Sunucuda:
#   cd /var/www/hostvim/data/www/hostvim.com/public_html
#   bash deploy/server-setup.sh
#
# Tam kurulum (panel + store + entegrasyon):
#   bash deploy/scripts/install-hostvim-full.sh --local
#
# Mac'ten:
#   bash deploy/scripts/install-hostvim-full.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STORE_ROOT="${STORE_ROOT:-$(cd "$SCRIPT_DIR/.." && pwd)}"

for candidate in \
  "/var/www/hostvim/deploy/scripts/lib/hostvim-common.sh" \
  "/var/www/panelze/deploy/scripts/lib/hostvim-common.sh" \
  "$(cd "$SCRIPT_DIR/../../.." 2>/dev/null && pwd)/deploy/scripts/lib/hostvim-common.sh"; do
  if [[ -f "$candidate" ]]; then
    COMMON="$candidate"
    break
  fi
done

if [[ -z "${COMMON:-}" ]]; then
  echo "hostvim-common.sh bulunamadı. Önce şunu çalıştırın:" >&2
  echo "  bash deploy/scripts/install-hostvim-full.sh --local" >&2
  exit 1
fi

# shellcheck source=/dev/null
source "$COMMON"

export HOSTVIM_REPO_ROOT="${HOSTVIM_REPO_ROOT:-$(dirname "$(dirname "$(dirname "$COMMON")")")}"
export STORE_ROOT
export HOSTVIM_SKIP_PANEL=1

hostvim_store_post_deploy
hostvim_fix_permissions

echo "==> Store kurulumu tamamlandı."
echo "    Admin: $(hostvim_read_env_value "$STORE_ROOT/.env" APP_URL 2>/dev/null || echo '')/admin"
