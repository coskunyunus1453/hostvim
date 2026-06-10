#!/usr/bin/env bash
# Laravel queue worker — uzun stack kurulumları için yeterli --timeout.
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
  echo "ensure-queue-worker: root gerekli" >&2
  exit 1
fi

PANEL_ROOT="${PANEL_ROOT:-/var/www/panelze/panel}"
QUEUE_TIMEOUT="${PANELZE_QUEUE_TIMEOUT:-1900}"

cat >"/etc/systemd/system/panelze-panel-queue.service" <<EOF
[Unit]
Description=Panelze Laravel Queue Worker
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

systemctl daemon-reload
systemctl enable --now panelze-panel-queue
echo "OK panelze-panel-queue (timeout=${QUEUE_TIMEOUT})"
