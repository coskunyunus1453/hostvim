#!/usr/bin/env bash
#
# CodeIgniter 4 sitelerinde eksik API route'ları, HEAD / desteği ve 404 handler düzeltmesi.
# Örnek: fix-ci4-api-routes.sh /var/www/hostvim/data/www/gumusfiyat.com/public_html
#
set -euo pipefail

SITE_ROOT="${1:-}"
if [[ -z "$SITE_ROOT" || ! -f "$SITE_ROOT/app/Config/Routes.php" ]]; then
  echo "Kullanım: $0 /path/to/public_html" >&2
  exit 1
fi

ROUTES="$SITE_ROOT/app/Config/Routes.php"
HOME="$SITE_ROOT/app/Controllers/Home.php"

echo "==> CI4 route düzeltmesi: $SITE_ROOT"

# Ana sayfa HEAD desteği
if grep -q "\$routes->get('/', 'Home::index');" "$ROUTES"; then
  sed -i "s|\$routes->get('/', 'Home::index');|\$routes->match(['get', 'head'], '/', 'Home::index');|" "$ROUTES"
  echo "  HEAD / eklendi"
fi

# Eksik API route'ları
if ! grep -q "current-gold-price" "$ROUTES"; then
  sed -i "/\$routes->get('\/api\/gold\/price'/i\\
\$routes->get('/api/current-gold-price', 'Home::getCurrentGoldPrice');\\
\$routes->get('/api/prices', 'Home::getPricesApi');\\
" "$ROUTES"
  echo "  API route'ları eklendi"
fi

# 404 handler — Response nesnesi string'e dönüştürülemiyor (CI4.6)
if grep -q "set404Override(function" "$ROUTES"; then
  php <<PATCH
<?php
\$f = file_get_contents('$ROUTES');
if (strpos(\$f, '\$response->send();') === false) {
  \$f = preg_replace(
    '/(\\\$response->setStatusCode\\(404\\)[\\s\\S]*?->setJSON\\(\\[[\\s\\S]*?\\]\\));\\s*\\n/',
    '\$1;' . "\\n        \\\$response->send();\\n        exit;\\n",
    \$f,
    1
  );
}
\$f = str_replace('        ();', '        \$response->send();', \$f);
file_put_contents('$ROUTES', \$f);
PATCH
  echo "  404 handler düzeltildi"
fi

# Home controller — JSON API metodları
if [[ -f "$HOME" ]] && ! grep -q "function getCurrentGoldPrice" "$HOME"; then
  php -r "
    \$f = file_get_contents('$HOME');
    \$insert = <<<'PHP'

    public function getCurrentGoldPrice()
    {
        \$price = \$this->getLatestGoldPrice();
        if (\$price) {
            return \$this->response->setJSON([
                'success' => true,
                'price' => [
                    'sell_price' => number_format((float) (\$price['sell_price'] ?? 0), 2, '.', ''),
                    'scraped_at' => \$price['scraped_at'] ?? null,
                ],
            ]);
        }

        return \$this->response->setJSON([
            'success' => false,
            'message' => 'Fiyat bilgisi bulunamadı',
        ]);
    }

    public function getPricesApi()
    {
        return \$this->response->setJSON([
            'success' => true,
            'silver' => \$this->getLatestSilverPrice(),
            'gold' => \$this->getLatestGoldPrice(),
        ]);
    }

PHP;
    \$f = preg_replace('/(\\s+public function getCurrentSilverPrice\\(\\))/', \$insert . '\$1', \$f, 1);
    file_put_contents('$HOME', \$f);
  "
  echo "  Home API metodları eklendi"
fi

echo "==> Tamam"
