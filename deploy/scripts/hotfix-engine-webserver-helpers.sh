#!/usr/bin/env bash
# Apache / OpenLiteSpeed vhost sudo helper + engine yeniden derleme (root).
# Kullanım: cd /var/www/hostvim && bash deploy/scripts/hotfix-engine-webserver-helpers.sh
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Root gerekli." >&2
  exit 1
fi

PANELZE_HOME="${PANELZE_HOME:-/var/www/hostvim}"
cd "$PANELZE_HOME"

echo "==> git pull"
git fetch origin main
git reset --hard origin/main

echo "==> staging dizinleri"
mkdir -p "$PANELZE_HOME/data/apache-vhosts" "$PANELZE_HOME/data/ols-staging"
chown www-data:www-data "$PANELZE_HOME/data/apache-vhosts" "$PANELZE_HOME/data/ols-staging"

install_helper() {
  local base="$1"
  local src="$PANELZE_HOME/deploy/host/panelze-${base}"
  [[ -f "$src" ]] || { echo "Eksik: $src" >&2; exit 1; }
  install -m 755 "$src" "/usr/local/sbin/panelze-${base}"
  ln -sfn "/usr/local/sbin/panelze-${base}" "/usr/local/sbin/panelsar-${base}"
}

echo "==> sudo helper betikleri"
install_helper apache-vhost
install_helper ols-vhost
install_helper nginx-vhost

echo "==> sudoers"
bash "$PANELZE_HOME/deploy/scripts/ensure-engine-sudoers.sh"

echo "==> OpenLiteSpeed backend (8088) — kuruluysa"
if [[ -f "$PANELZE_HOME/deploy/host/panelze-openlitespeed-setup.sh" ]]; then
  bash "$PANELZE_HOME/deploy/host/panelze-openlitespeed-setup.sh" || echo "Uyarı: OLS setup atlandı/başarısız"
fi

echo "==> engine derle"
if ! command -v go >/dev/null 2>&1; then
  echo "Hata: go yok" >&2
  exit 1
fi
(cd "$PANELZE_HOME/engine" && go build -buildvcs=false -o /usr/local/bin/panelze-engine ./cmd/panelze-engine)

echo "==> helper test (www-data)"
sudo -u www-data sudo -n /usr/local/sbin/panelze-apache-vhost disable panelze-test.conf 2>&1 | head -1 || true

restarted=0
for svc in hostvim-engine panelze-engine panelsar-engine; do
  if systemctl list-unit-files "${svc}.service" 2>/dev/null | grep -qE 'enabled|disabled|static'; then
    systemctl restart "$svc"
    echo "==> $svc yeniden başlatıldı"
    restarted=1
    break
  fi
done
if [[ "$restarted" -eq 0 ]]; then
  echo "Uyarı: engine systemd servisi bulunamadı; elle restart edin." >&2
fi

echo "OK — panelden PHP sürümünü veya web sunucusunu yeniden seçerek vhost'u tetikleyin."
