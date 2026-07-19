#!/usr/bin/env bash
# Docker disk guard + BuildKit cache limit kurulumu.
# Kullanım: bash deploy/scripts/install-docker-disk-guard.sh [--local]
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
LOCAL=0
[[ "${1:-}" == "--local" ]] && LOCAL=1

run() {
  if [[ "$LOCAL" == "1" ]]; then
    bash -s
  else
    ssh hostvim bash -s
  fi
}

run <<EOF
set -euo pipefail

echo "==> docker-disk-guard kurulumu"
install -m 755 /var/www/hostvim/deploy/host/docker-disk-guard /usr/local/sbin/docker-disk-guard

mkdir -p /etc/docker
DAEMON_JSON=/etc/docker/daemon.json
if [[ -f "\$DAEMON_JSON" ]]; then
  if ! python3 -c "import json; json.load(open('\$DAEMON_JSON'))" 2>/dev/null; then
    cp -a "\$DAEMON_JSON" "\${DAEMON_JSON}.bak.\$(date +%Y%m%d%H%M%S)"
    echo '{}' > "\$DAEMON_JSON"
  fi
  python3 <<'PY'
import json
from pathlib import Path
p = Path("/etc/docker/daemon.json")
data = json.loads(p.read_text() or "{}")
builder = data.setdefault("builder", {})
gc = builder.setdefault("gc", {})
gc["enabled"] = True
gc["defaultKeepStorage"] = "8589934592"  # 8 GiB — BuildKit otomatik GC
p.write_text(json.dumps(data, indent=2) + "\n")
print("daemon.json güncellendi:", p.read_text())
PY
else
  cat > "\$DAEMON_JSON" <<'JSON'
{
  "builder": {
    "gc": {
      "enabled": true,
      "defaultKeepStorage": "8589934592"
    }
  }
}
JSON
  echo "daemon.json oluşturuldu"
fi

cat > /etc/cron.d/hostvim-docker-guard <<'CRON'
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# Günlük 03:40 — build cache (72h+) + dangling imaj + mobil tmp
40 3 * * * root /usr/local/sbin/docker-disk-guard daily 2>&1 | logger -t docker-disk-guard
# Pazar 04:10 — orphan imaj/volume + mobil preview
10 4 * * 0 root /usr/local/sbin/docker-disk-guard weekly 2>&1 | logger -t docker-disk-guard
CRON
chmod 644 /etc/cron.d/hostvim-docker-guard

echo "==> Docker yeniden başlatılıyor (BuildKit GC limiti)"
systemctl restart docker
sleep 3
docker ps --format '{{.Names}}\t{{.Status}}' | head -5

echo "==> İlk guard çalıştırması"
/usr/local/sbin/docker-disk-guard daily

echo "==> Bitti"
EOF

echo "Kurulum tamam."
