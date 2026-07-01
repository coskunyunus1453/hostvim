<?php

$boost = [
    '03_vps_nedir.php' => '<p>VPS seçimi uzun vadeli bir karardır; metrikler sizi zorladığında geçişi ertelemeyin. Hostvim satış ve destek ekibi mevcut trafiğinize göre paket önerisi sunar.</p>',
    '04_bulut_sunucu.php' => '<p>Bulut sunucu ile KOBİ\'ler de kampanya dönemlerinde ölçeklenip maliyeti kontrol edebilir. Hostvim bulut altyapısı bu esnekliği yerel destekle birleştirir.</p>',
    '05_domain_nedir.php' => '<p>Domain kaydı ilk adımdır; hosting ve SSL ile tamamlayın. Projenizi bugün başlatmak için domain sorgulama sayfasını ziyaret edin.</p>',
    '06_domain_uzantilari.php' => '<p>Uzantı kararı marka stratejinizin parçasıdır. Pazarlama ekibinizle birlikte değerlendirin; teknik ekip DNS tarafını halleder.</p>',
    '07_ssl.php' => '<p>HTTPS olmadan modern web yok. Ziyaretçileriniz güvenlik uyarısı görmemeli; Hostvim ile bu risk ortadan kalkar.</p>',
    '08_hizlandirma.php' => '<p>Hız optimizasyonu yatırım getirisi sunar: daha hızlı site, daha fazla dönüşüm. Sonuçları Analytics ile ölçün.</p>',
    '09_wordpress.php' => '<p>Doğru WordPress hosting üretkenliğinizi artırır. Yavaş hosting motivasyon kırar; Hostvim ile hızlı başlayın.</p>',
    '10_hosting_tasima.php' => '<p>Taşıma checklist\'ini yazdırın, adım adım ilerleyin. Hostvim destek ekibi süreç boyunca yanınızdadır.</p>',
];

foreach ($boost as $file => $html) {
    $path = __DIR__.'/'.$file;
    $content = file_get_contents($path);
    if (! str_contains($content, 'FINALBOOST')) {
        $content = str_replace('<h2>Sonuç</h2>', "<!-- FINALBOOST -->\n".$html."\n<h2>Sonuç</h2>", $content);
        file_put_contents($path, $content);
    }
}

foreach (glob(__DIR__.'/[0-9]*.php') as $f) {
    $d = require $f;
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($d['content']), $m);
    $c = count($m[0]);
    echo basename($f).' => '.$c.($c >= 1000 ? ' OK' : '')."\n";
}
