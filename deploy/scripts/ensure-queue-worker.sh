#!/usr/bin/env bash
# Laravel queue worker — uzun stack kurulumları için yeterli --timeout.
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
  echo "ensure-queue-worker: root gerekli" >&2
  exit 1
fi

PANEL_ROOT="${PANEL_ROOT:-/var/www/hostvim/panel}"
QUEUE_TIMEOUT="${HOSTVIM_QUEUE_TIMEOUT:-1900}"

write_unit() {
  local name="$1"
  cat >"/etc/systemd/system/${name}.service" <<EOF
[Unit]
Description=Hostvim Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=${PANEL_ROOT}
Environment=HOME=${PANEL_ROOT}
Environment=XDG_CONFIG_HOME=${PANEL_ROOT}/storage/framework/.config
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=1 --timeout=${QUEUE_TIMEOUT}
Restart=always
RestartSec=5
KillSignal=SIGTERM
TimeoutStopSec=60

[Install]
WantedBy=multi-user.target
EOF
}

write_unit hostvim-panel-queue
write_unit panelze-panel-queue

systemctl daemon-reload
for svc in hostvim-panel-queue panelze-panel-queue; do
  if systemctl is-enabled "$svc" >/dev/null 2>&1 || systemctl is-active "$svc" >/dev/null 2>&1; then
    systemctl enable "$svc" 2>/dev/null || true
    systemctl restart "$svc"
    echo "OK $svc (timeout=${QUEUE_TIMEOUT})"
  fi
done

# Aktif olan yoksa hostvim-panel-queue başlat
if ! systemctl is-active hostvim-panel-queue >/dev/null 2>&1 && ! systemctl is-active panelze-panel-queue >/dev/null 2>&1; then
  systemctl enable --now hostvim-panel-queue
  echo "OK hostvim-panel-queue started"
fi
