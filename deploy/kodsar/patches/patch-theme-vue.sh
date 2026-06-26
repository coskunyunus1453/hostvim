#!/usr/bin/env bash
# Sunucuda tema Vue dosyalarına ortak yasal link patch'i (rsync sonrası yedek)
set -euo pipefail
KODSAR="${1:-/var/www/hostvim/data/www/kodsar.com/public_html}"
THEMES=(Ecommerce Software Gamer Kids)

for theme in "${THEMES[@]}"; do
  header="$KODSAR/resources/js/Components/Frontend/Themes/$theme/Header.vue"
  [[ -f "$header" ]] && sed -i 's/item\.url || item\.href/item.href || item.url/g' "$header"
done

echo "Theme headers patched (href priority)"
