#!/usr/bin/env bash
# hostvim.conf — /admin/index.php/api PUT/POST 405 düzeltmesi
set -euo pipefail
CONF="${1:-/etc/nginx/sites-enabled/hostvim.conf}"
if [[ ! -f "$CONF" ]]; then
  echo "Config not found: $CONF" >&2
  exit 1
fi
if grep -q 'location \^~ /admin/index.php/' "$CONF"; then
  echo "Already patched: $CONF"
  exit 0
fi
python3 - "$CONF" <<'PY'
import sys
from pathlib import Path
p = Path(sys.argv[1])
text = p.read_text()
needle = "    location ~ ^/admin/index\\.php/ {"
if needle not in text:
    raise SystemExit("admin index.php block not found")
insert = """    # ^~ ile SPA fallback'ten önce PHP'ye yönlendir (PUT → 405 önleme).
    location ^~ /admin/index.php/ {
        rewrite ^/admin/index\\.php(.*)$ /index.php$1 last;
    }

"""
text = text.replace(needle, insert + needle, 1)
p.write_text(text)
print("Patched", p)
PY
nginx -t
systemctl reload nginx
echo "nginx reloaded"
