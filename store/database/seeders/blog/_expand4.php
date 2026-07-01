<?php

$extra = [
    '02_paylasimli_vps.php' => '<h2>Hostvim müşteri deneyimi</h2><p>Hostvim paylaşımlı hosting paketlerinde panel kafes teknolojisi kullanır; siteniz komşu sitelerden izole çalışır. VPS ve bulut seçenekleri aynı müşteri panelinden yönetilir; büyüdükçe paket yükseltmesi kesintisiz yapılabilir. Destek ekibimiz hangi modelin size uygun olduğunu trafik ve teknik ihtiyaçlarınıza göre önerebilir.</p>',
    '03_vps_nedir.php' => '<h2>Kaynak izleme araçları</h2><p>Netdata, Grafana veya basitçe <code>htop</code> ile kaynak tüketimini izleyin. Ani CPU spike cron job veya bot trafiğinden kaynaklanabilir. Log dosyalarını rotasyona alın; dolu disk VPS\'i çökertir. Hostvim VPS paketlerinde kaynak kullanım grafikleri panelde görüntülenebilir.</p>',
    '04_bulut_sunucu.php' => '<h2>Bulut vs geleneksel barındırma</h2><p>Geleneksel dedicated sunucu 3–5 yıllık donanım döngüsüne tabidir. Bulut altyapısı sağlayıcı donanımı yeniler; siz sanal kaynak tüketmeye devam edersiniz. Bu operasyonel yükü azaltır. Kritik uygulamalar için çoklu availability zone kullanımı uptime artırır.</p>',
    '05_domain_nedir.php' => '<h2>Domain ve sosyal medya uyumu</h2><p>Domain seçerken Instagram, X ve TikTok kullanıcı adlarının da müsait olup olmadığını kontrol edin. Tutarlı marka kimliği müşterinin sizi her kanalda tanımasını kolaylaştırır. Namechk.com gibi araçlar toplu sorgu yapmanıza yardımcı olur.</p>',
    '06_domain_uzantilari.php' => '<h2>Yasal ve ticari boyut</h2><p>.tr uzantılı domainlerde sahte belge ile kayıt yaptırmak domain iptali ve hukuki yaptırımla sonuçlanır. Ticari unvanınızla uyumlu domain seçmek marka tescili sürecinizi de kolaylaştırır. Global .com için trademark araştırması yapın.</p>',
    '07_ssl.php' => '<h2>SSL ve arama motorları</h2><p>Google 2014\'ten beri HTTPS\'i sıralama sinyali olarak kullanıyor. HTTPS olmayan site rakiplerinin gerisinde kalır. Search Console\'da HTTPS mülkünü doğrulayın; HTTP ve HTTPS mülklerini birleştirin. Sitemap URL\'lerinin https ile başladığından emin olun.</p>',
    '08_hizlandirma.php' => '<h2>Ölçüm araçları karşılaştırması</h2><p>PageSpeed Insights lab verisi + CrUX field verisi sunar. GTmetrix farklı lokasyonlardan test yapar. WebPageTest waterfall ile darboğazı gösterir. Tek araç yerine ikisini birlikte kullanın; lab ile gerçek kullanıcı verisini ayırt edin.</p>',
    '09_wordpress.php' => '<h2>Barındırma lokasyonu</h2><p>Hedef kitleniz Türkiye\'deyse Avrupa veya yerel veri merkezi TTFB\'yi düşürür. WooCommerce mağazasında her 100 ms gecikme dönüşümü etkileyebilir. CDN ile statik dosyaları global dağıtın; dinamik PHP yine origin\'de çalışır.</p>',
    '10_hosting_tasima.php' => '<h2>Taşıma sonrası izleme</h2><p>Taşımadan sonraki 7 gün uptime monitörü (UptimeRobot, Pingdom) kurun. 5xx hata oranı, ortalama yanıt süresi ve SSL bitiş tarihini izleyin. Google Analytics\'te ani trafik düşüşü DNS veya yönlendirme hatasına işaret edebilir.</p>',
];

foreach ($extra as $file => $html) {
    $path = __DIR__.'/'.$file;
    $content = file_get_contents($path);
    if (str_contains($content, 'GENISLETME4')) {
        continue;
    }
    $content = str_replace('<h2>Sonuç</h2>', "<!-- GENISLETME4 -->\n".$html."\n<h2>Sonuç</h2>", $content);
    file_put_contents($path, $content);
}

foreach (glob(__DIR__.'/[0-9]*.php') as $f) {
    $d = require $f;
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($d['content']), $m);
    $c = count($m[0]);
    echo basename($f).' => '.$c.($c >= 1000 ? ' OK' : '')."\n";
}
