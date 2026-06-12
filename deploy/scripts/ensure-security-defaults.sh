#!/usr/bin/env bash
# Temel güvenlik paketi: PANELZE-FW, fail2ban, ModSecurity — kurulu ve açık olmalı.
#
# Kullanım (root):
#   PANEL_ROOT=/var/www/panelze/panel bash deploy/scripts/ensure-security-defaults.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ -f "$SCRIPT_DIR/lib/panelze-deploy-common.sh" ]]; then
  # shellcheck source=lib/panelze-deploy-common.sh
  source "$SCRIPT_DIR/lib/panelze-deploy-common.sh"
fi

PANELZE_HOME="${PANELZE_HOME:-$(panelze_resolve_home 2>/dev/null || echo /var/www/panelze)}"
PANEL_ROOT="${PANEL_ROOT:-$PANELZE_HOME/panel}"
PANEL_ROOT="${PANEL_ROOT%/}"
RUN_USER="${RUN_USER:-www-data}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Hata: ensure-security-defaults root olarak çalıştırılmalı." >&2
  exit 1
fi

if [[ ! -x /usr/local/sbin/panelze-security ]]; then
  echo "Hata: /usr/local/sbin/panelze-security yok." >&2
  exit 1
fi

echo "==> Temel güvenlik (güvenlik duvarı, fail2ban, ModSecurity)"
OUT="$(/usr/local/sbin/panelze-security security-bootstrap-defaults 2>&1)" || {
  echo "Uyarı: security-bootstrap-defaults tamamlanamadı (log: /var/log/panelze-security-bootstrap.log)" >&2
  echo "$OUT" >&2
}
echo "$OUT"

if [[ -f "$SCRIPT_DIR/fix-mail-firewall.sh" ]] && { systemctl is-active postfix >/dev/null 2>&1 || command -v postfix >/dev/null 2>&1; }; then
  bash "$SCRIPT_DIR/fix-mail-firewall.sh" || true
fi

if [[ -f "$PANEL_ROOT/artisan" ]]; then
  echo "==> Güvenlik önbelleği temizleniyor"
  if command -v panelze_run_artisan >/dev/null 2>&1; then
    panelze_run_artisan cache:forget panelze:security:overview 2>/dev/null || true
    panelze_run_artisan cache:forget panelze:security:advisor 2>/dev/null || true
  else
    sudo -u "$RUN_USER" php "$PANEL_ROOT/artisan" cache:forget panelze:security:overview 2>/dev/null || true
    sudo -u "$RUN_USER" php "$PANEL_ROOT/artisan" cache:forget panelze:security:advisor 2>/dev/null || true
  fi
fi
