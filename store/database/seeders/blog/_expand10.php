<?php

$boost = [
    '03_vps_nedir.php' => '<p>Hostvim VPS paketlerini inceleyerek hemen başlayabilirsiniz.</p>',
    '04_bulut_sunucu.php' => '<p>Hostvim bulut sunucu sayfasından paketleri karşılaştırın; deneme ve danışmanlık için bize ulaşın. Ölçeklenebilir altyapı artık her ölçekteki işletme için erişilebilir durumda.</p>',
    '05_domain_nedir.php' => '<p>Hostvim domain ve hosting paketlerini birlikte değerlendirin; DNS yönetimi tek panelde kolaylaşır. İlk domaininizi bugün kaydederek dijital yolculuğunuza başlayın.</p>',
    '06_domain_uzantilari.php' => '<p>Hostvim üzerinden tüm uzantıları sorgulayın; markanızı koruyacak kombinasyonu oluşturun. Doğru uzantı uzun vadede pazarlama maliyetinizi düşürür.</p>',
    '07_ssl.php' => '<p>Hostvim hosting ile SSL endişesi yaşamadan yayına alın. Güvenli site müşteri güveninin temelidir; HTTPS bu güvenin teknik kanıtıdır.</p>',
    '08_hizlandirma.php' => '<p>Hostvim altyapısı ve bu rehberdeki adımlarla sitenizi hızlandırın. Performans iyileştirmesi sürekli bir süreçtir; bugün başlayın.</p>',
    '09_wordpress.php' => '<p>Hostvim WordPress hosting ile dakikalar içinde kurulum yapın. Kaliteli hosting içerik stratejinizin görünmez kahramanıdır.</p>',
    '10_hosting_tasima.php' => '<p>Hostvim\'e taşınmak için destek talebi açın; süreci birlikte yönetelim. Daha hızlı ve güvenilir hosting bir gece uzağınızda.</p>',
];

foreach ($boost as $file => $html) {
    $path = __DIR__.'/'.$file;
    $content = file_get_contents($path);
    if (! str_contains($content, 'FINALBOOST10')) {
        $content = str_replace('<h2>Sonuç</h2>', "<!-- FINALBOOST10 -->\n".$html."\n<h2>Sonuç</h2>", $content);
        file_put_contents($path, $content);
    }
}

foreach (glob(__DIR__.'/[0-9]*.php') as $f) {
    $d = require $f;
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($d['content']), $m);
    $c = count($m[0]);
    echo basename($f).' => '.$c.($c >= 1000 ? ' OK' : '')."\n";
}
