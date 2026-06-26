#!/usr/bin/env bash
# Panel nginx — /index.php/api/* isteklerinde FastCGI SCRIPT_FILENAME düzeltmesi
set -euo pipefail

CONF="${1:-/etc/nginx/sites-enabled/hostvim.conf}"
if [[ ! -f "$CONF" ]]; then
  echo "Config not found: $CONF" >&2
  exit 1
fi

python3 - "$CONF" <<'PY'
import sys
from pathlib import Path

p = Path(sys.argv[1])
text = p.read_text()
old = "        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n        fastcgi_param PATH_INFO $fastcgi_path_info;"
new = "        fastcgi_param SCRIPT_FILENAME $document_root/index.php;\n        fastcgi_param PATH_INFO $fastcgi_path_info;"
if old not in text:
    if "$document_root/index.php" in text:
        print("Already patched:", p)
        sys.exit(0)
    raise SystemExit("index.php fastcgi block not found in " + str(p))
text = text.replace(old, new)
p.write_text(text)
print("Patched", p)
PY

nginx -t
systemctl reload nginx
echo "nginx reloaded"
