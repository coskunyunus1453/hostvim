#!/usr/bin/env bash
# Panelze sertifikaları /etc/letsencrypt altında DEĞİL; özel config-dir kullanır.
# Varsayılan certbot.timer boş /etc/letsencrypt'e bakıp hiçbir şeyi yenilemez.
# Bu betik systemd override + sarmalayıcı kurar; SSL sürelerinin dolmasını önler.
#
# Kurulum / güncelleme yolları:
#   install-production.sh, panelze-post-install, panelze-panel-update, ensure-webserver-stack.sh
set -euo pipefail

resolve_panelze_home() {
  if [[ -n "${PANELZE_HOME:-}" && -d "${PANELZE_HOME}" ]]; then
    echo "$PANELZE_HOME"
    return
  fi
  if [[ -n "${PANELSAR_HOME:-}" && -d "${PANELSAR_HOME}" ]]; then
    echo "$PANELSAR_HOME"
    return
  fi
  for cand in /var/www/panelze /var/www/hostvim /opt/panelze; do
    if [[ -d "$cand/data" ]] || [[ -d "$cand/panel" ]]; then
      echo "$cand"
      return
    fi
  done
  echo "/var/www/panelze"
}

PANELZE_HOME="$(resolve_panelze_home)"
CFG="${PANELZE_HOME}/data/ssl/letsencrypt/config"
WD="${PANELZE_HOME}/data/ssl/letsencrypt/work"
LD="${PANELZE_HOME}/data/ssl/letsencrypt/logs"
WRAPPER=/usr/local/sbin/panelze-certbot-renew
DROPIN_DIR=/etc/systemd/system/certbot.service.d
DROPIN="${DROPIN_DIR}/panelze.conf"

if ! command -v certbot >/dev/null 2>&1; then
  echo "certbot yok; atlanıyor." >&2
  exit 0
fi

mkdir -p "$(dirname "$WRAPPER")" "$DROPIN_DIR" "$CFG" "$WD" "$LD"

cat > "$WRAPPER" <<EOF
#!/bin/bash
set -euo pipefail
exec /usr/bin/certbot -q renew --no-random-sleep-on-renew \\
  --config-dir "${CFG}" \\
  --work-dir "${WD}" \\
  --logs-dir "${LD}" \\
  --deploy-hook "/usr/sbin/nginx -t && /bin/systemctl reload nginx"
EOF
chmod 755 "$WRAPPER"

cat > "$DROPIN" <<EOF
[Service]
ExecStart=
ExecStart=${WRAPPER}
EOF

if command -v systemctl >/dev/null 2>&1; then
  systemctl daemon-reload
  systemctl enable --now certbot.timer 2>/dev/null || true
  echo "==> certbot.service Panelze config-dir ile yenilenecek: ${CFG}"
  systemctl show certbot.service -p ExecStart --value | head -3
else
  echo "==> systemd yok; wrapper yazıldı: ${WRAPPER}"
fi
