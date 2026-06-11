#!/usr/bin/env bash
# Apache / OpenLiteSpeed / Nginx edge — helper, sudoers, backend portları, engine yenileme (root).
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
  echo "ensure-webserver-stack: root gerekli" >&2
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PANELZE_HOME="${PANELZE_HOME:-/var/www/hostvim}"
cd "$PANELZE_HOME"

install_helper() {
  local base="$1"
  local src="$PANELZE_HOME/deploy/host/panelze-${base}"
  [[ -f "$src" ]] || { echo "Eksik: $src" >&2; exit 1; }
  install -m 755 "$src" "/usr/local/sbin/panelze-${base}"
  ln -sfn "/usr/local/sbin/panelze-${base}" "/usr/local/sbin/panelsar-${base}"
}

engine_binary_has_marker() {
  local bin="$1"
  local marker="$2"
  [[ -x "$bin" ]] || return 1
  if command -v strings >/dev/null 2>&1; then
    strings "$bin" | grep -qF "$marker"
    return
  fi
  # binutils yoksa (minimal sunucu) grep -a ile binary içinde ara
  grep -aqF "$marker" "$bin" 2>/dev/null
}

free_engine_port() {
  local pids pid comm
  pids="$(lsof -ti :9090 2>/dev/null || true)"
  [[ -n "$pids" ]] || return 0
  systemctl stop panelze-engine 2>/dev/null || true
  systemctl stop hostvim-engine 2>/dev/null || true
  systemctl stop panelsar-engine 2>/dev/null || true
  sleep 1
  pids="$(lsof -ti :9090 2>/dev/null || true)"
  for pid in $pids; do
    comm="$(ps -p "$pid" -o comm= 2>/dev/null | tr -d '[:space:]')"
    if [[ "$comm" =~ ^(panelze-engine|panelsar-engine|hostvim-engine|go)$ ]]; then
      kill -TERM "$pid" 2>/dev/null || kill -KILL "$pid" 2>/dev/null || true
    fi
  done
  sleep 1
}

echo "==> staging dizinleri"
mkdir -p "$PANELZE_HOME/data/apache-vhosts" "$PANELZE_HOME/data/ols-staging"
chown www-data:www-data "$PANELZE_HOME/data/apache-vhosts" "$PANELZE_HOME/data/ols-staging"

echo "==> vhost sudo helper betikleri"
install_helper nginx-vhost
install_helper apache-vhost
install_helper ols-vhost

echo "==> sudoers"
bash "$SCRIPT_DIR/ensure-engine-sudoers.sh"

echo "==> Apache backend :8080"
if [[ -f /etc/apache2/ports.conf ]]; then
  a2enmod proxy_fcgi rewrite headers 2>/dev/null || true
  sed -i \
    -e 's/^Listen 80$/Listen 8080/' \
    -e 's/^Listen \[::\]:80$/Listen [::]:8080/' \
    /etc/apache2/ports.conf 2>/dev/null || true
  systemctl enable apache2 2>/dev/null || true
  apache2ctl configtest 2>/dev/null && systemctl restart apache2 2>/dev/null || true
fi

echo "==> OpenLiteSpeed backend :8088"
if [[ -f "$PANELZE_HOME/deploy/host/panelze-openlitespeed-setup.sh" ]]; then
  bash "$PANELZE_HOME/deploy/host/panelze-openlitespeed-setup.sh" || echo "Uyarı: OLS setup atlandı" >&2
fi

echo "==> engine derle"
if ! command -v go >/dev/null 2>&1; then
  echo "Hata: go yok — engine güncellenemedi" >&2
  exit 1
fi
(cd "$PANELZE_HOME/engine" && go build -buildvcs=false -o /usr/local/bin/panelze-engine ./cmd/panelze-engine)
chmod 755 /usr/local/bin/panelze-engine

echo "==> legacy engine binary adları (hostvim-engine / panelsar-engine)"
for legacy in hostvim-engine panelsar-engine; do
  if [[ -f /etc/systemd/system/${legacy}.service ]] || [[ -e /usr/local/bin/$legacy ]]; then
    install -m 755 /usr/local/bin/panelze-engine "/usr/local/bin/$legacy"
  fi
done

if ! engine_binary_has_marker /usr/local/bin/panelze-engine 'ols-staging'; then
  echo "Hata: panelze-engine güncel değil (ols-staging işareti yok — derleme başarısız olabilir)" >&2
  exit 1
fi
echo "==> panelze-engine güncel (ols-staging staging helper desteği)"

CONFIG_DIR="/etc/panelze"
if [[ -f /etc/hostvim/engine.yaml ]]; then
  CONFIG_DIR="/etc/hostvim"
elif [[ -f /etc/panelze/engine.yaml ]]; then
  CONFIG_DIR="/etc/panelze"
fi

write_panelze_engine_unit() {
  sed \
    -e "s|__PANELZE_HOME__|$PANELZE_HOME|g" \
    -e "s|__ENGINE_BINARY__|/usr/local/bin/panelze-engine|g" \
    -e "s|PANELZE_CONFIG_DIR=/etc/panelze|PANELZE_CONFIG_DIR=$CONFIG_DIR|g" \
    "$PANELZE_HOME/deploy/systemd/panelze-engine.service"
}

if [[ ! -f /etc/systemd/system/panelze-engine.service ]] && [[ ! -f /etc/systemd/system/hostvim-engine.service ]]; then
  echo "==> systemd panelze-engine.service"
  write_panelze_engine_unit > /etc/systemd/system/panelze-engine.service
  systemctl daemon-reload
  systemctl enable panelze-engine 2>/dev/null || true
elif [[ -f /etc/systemd/system/panelze-engine.service ]] \
  && grep -q 'ExecStart=/usr/local/bin/hostvim-engine' /etc/systemd/system/panelze-engine.service 2>/dev/null; then
  echo "==> panelze-engine.service ExecStart düzeltiliyor"
  write_panelze_engine_unit > /etc/systemd/system/panelze-engine.service
  systemctl daemon-reload
fi

free_engine_port

restarted=0
for svc in panelze-engine hostvim-engine panelsar-engine; do
  if systemctl list-unit-files "${svc}.service" 2>/dev/null | grep -qE 'enabled|disabled|static'; then
    systemctl restart "$svc" && echo "==> $svc yeniden başlatıldı" && restarted=1 && break
  fi
done
if [[ "$restarted" -eq 0 ]]; then
  echo "Uyarı: systemd yok; engine elle başlatılmalı" >&2
fi

echo "==> helper doğrulama"
# Geçersiz girdi ile sudo+helper yolunu test et (başarılı disable Syntax OK döner — yanlış pozitif olmasın)
out_apache="$(sudo -u www-data sudo -n /usr/local/sbin/panelze-apache-vhost disable test.conf 2>&1 || true)"
if grep -qiE 'sudo:|not allowed|password required' <<<"$out_apache"; then
  echo "Hata: panelze-apache-vhost sudo çalışmıyor: $out_apache" >&2
  exit 1
fi
if ! grep -qiE 'geçersiz|invalid|izin verilmeyen' <<<"$out_apache"; then
  echo "Hata: panelze-apache-vhost reddetmedi (beklenen geçersiz dosya adı): $out_apache" >&2
  exit 1
fi
out_ols="$(sudo -u www-data sudo -n /usr/local/sbin/panelze-ols-vhost remove .invalid 2>&1 || true)"
if grep -qiE 'sudo:|not allowed|password required' <<<"$out_ols"; then
  echo "Hata: panelze-ols-vhost sudo çalışmıyor: $out_ols" >&2
  exit 1
fi
if ! grep -qiE 'geçersiz|invalid|izin verilmeyen' <<<"$out_ols"; then
  echo "Hata: panelze-ols-vhost reddetmedi (beklenen geçersiz domain): $out_ols" >&2
  exit 1
fi

echo "OK webserver stack (CONFIG_DIR=$CONFIG_DIR)"
