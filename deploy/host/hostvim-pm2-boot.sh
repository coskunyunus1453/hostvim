#!/usr/bin/env bash
# Hostvim — boot sırasında PM2 god daemon + kayıtlı uygulamalar (www-data).
set -euo pipefail

PM2_HOME="${HOSTVIM_PM2_HOME:-${PANELZE_PM2_HOME:-/var/www/hostvim/data/pm2}}"
RUN_USER="${HOSTVIM_PM2_USER:-${PANELZE_PM2_USER:-www-data}}"
PM2_BIN="${HOSTVIM_PM2_BIN:-${PANELZE_PM2_BIN:-/usr/bin/pm2}}"
DAEMON_JS="/usr/lib/node_modules/pm2/lib/Daemon.js"

mkdir -p "$PM2_HOME"
chown -R "$RUN_USER:$RUN_USER" "$PM2_HOME" 2>/dev/null || true

run_pm2() {
  if command -v runuser >/dev/null 2>&1; then
    runuser -u "$RUN_USER" -- env PM2_HOME="$PM2_HOME" HOME="$PM2_HOME" "$@"
  else
    sudo -u "$RUN_USER" env PM2_HOME="$PM2_HOME" HOME="$PM2_HOME" "$@"
  fi
}

# Ölü soket/pid temizliği
if [[ -f "$PM2_HOME/pm2.pid" ]]; then
  pid="$(tr -d '[:space:]' < "$PM2_HOME/pm2.pid" 2>/dev/null || true)"
  if [[ -z "$pid" ]] || ! kill -0 "$pid" 2>/dev/null; then
    rm -f "$PM2_HOME/pm2.pid" "$PM2_HOME/rpc.sock" "$PM2_HOME/pub.sock"
  fi
fi

if ! run_pm2 "$PM2_BIN" ping >/dev/null 2>&1; then
  if [[ -f "$DAEMON_JS" ]]; then
    run_pm2 /usr/bin/node "$DAEMON_JS" >>"$PM2_HOME/pm2.log" 2>&1 &
    sleep 2
  fi
fi

if [[ -f "$PM2_HOME/dump.pm2" ]]; then
  run_pm2 "$PM2_BIN" resurrect
fi

exit 0
