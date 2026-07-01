<?php

$extra = [
    '03_vps_nedir.php' => '<p>Son olarak: VPS seçerken sadece fiyat değil, ağ kalitesi, destek yanıt süresi ve veri merkezi lokasyonunu da değerlendirin. Ucuz VPS düşük IOPS veya aşırı paylaşımlı CPU ile hayal kırıklığı yaratabilir.</p>',
    '04_bulut_sunucu.php' => '<p>Bulut sunucu yatırımınızı geri ölçmek için kampanya öncesi/sonrası dönüşüm oranını ve sunucu maliyetini karşılaştırın. Ölçeklenebilirlik gelir kaybını önlediğinde bulut maliyeti kendini amorti eder.</p><p>Hostvim bulut paketleri şeffaf fiyatlandırma ile gizli egress sürprizleri yaşatmadan büyümenize ortam sağlar.</p>',
    '05_domain_nedir.php' => '<p>Domain yönetimini hosting ile aynı panelde toplamak operasyonel hatayı azaltır. Yenileme tarihlerini kaçırmamak için otomatik yenileme ve güncel iletişim bilgisi kritiktir.</p><p>Hostvim müşteri panelinde domain ve hosting tek hesapta yönetilir; DNS değişikliği için ayrı panele girmeniz gerekmez.</p>',
    '06_domain_uzantilari.php' => '<p>Uzantı seçimi pazarlama kararıdır; teknik olarak hepsi DNS ile çalışır. Hedef kitlenizin hangi uzantıya güvendiğini anket veya rakip analizi ile test edebilirsiniz.</p><p>Markanız büyüdükçe ek uzantıları alıp 301 ile ana domaine yönlendirmek cybersquatting riskini azaltır.</p>',
    '07_ssl.php' => '<p>SSL kurulumundan sonra tüm alt sayfaları, ödeme ve giriş formlarını HTTPS üzerinden test edin. Mobil uygulama deep link\'leri de HTTPS gerektirebilir.</p><p>Hostvim otomatik SSL ile bu adımın çoğunu sizin yerinize halleder; mixed content düzeltmesi site içeriğine bağlıdır.</p>',
    '08_hizlandirma.php' => '<p>Hız iyileştirmesi asla bitmez; yeni içerik ve özellikler ekledikçe tekrar ölçüm yapın. Performans bütçesi (performance budget) belirleyin: örneğin ana sayfa 1.5 MB altı kalsın.</p><p>Hostvim altyapısı üzerinde cache ve CDN ile birlikte Core Web Vitals hedeflerinize ulaşmak mümkündür.</p>',
    '09_wordpress.php' => '<p>WordPress hosting seçiminde referans ve uptime istatistikleri isteyin. %99.9 yazıp sık sık kesinti yaşayan sağlayıcıdan kaçının. SLA metnini okuyun.</p><p>Hostvim şeffaf uptime ve Türkçe destek ile WordPress projelerinizi güvenle barındırır.</p>',
    '10_hosting_tasima.php' => '<p>Taşıma sonrası ilk ay eski hosting faturasını iptal etmeyi unutmayın; çift ödeme yapmayın. Yedekleri en az 30 gün saklayın.</p><p>Hostvim\'e hoş geldiniz; taşıma sonrası hız farkını kısa sürede fark edeceksiniz.</p>',
];

foreach ($extra as $file => $html) {
    $path = __DIR__.'/'.$file;
    $content = file_get_contents($path);
    if (str_contains($content, 'GENISLETME6')) {
        continue;
    }
    $content = str_replace('<h2>Sonuç</h2>', "<!-- GENISLETME6 -->\n".$html."\n<h2>Sonuç</h2>", $content);
    file_put_contents($path, $content);
}

foreach (glob(__DIR__.'/[0-9]*.php') as $f) {
    $d = require $f;
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($d['content']), $m);
    $c = count($m[0]);
    echo basename($f).' => '.$c.($c >= 1000 ? ' OK' : '')."\n";
}
