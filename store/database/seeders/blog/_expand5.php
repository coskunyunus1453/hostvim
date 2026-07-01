<?php

$extra = [
    '02_paylasimli_vps.php' => '<h2>Karar vermeden önce sorulacak 10 soru</h2><ol><li>Günlük ziyaretçi sayım ne?</li><li>Özel yazılım veya cron ihtiyacım var mı?</li><li>Root erişimine ihtiyacım var mı?</li><li>E-ticaret mi yoksa blog mu?</li><li>Teknik destek mi yoksa kendi ekibim mi yönetecek?</li><li>Bütçem aylık ne kadar?</li><li>6 ay sonra trafik iki katına çıkar mı?</li><li>E-posta hosting aynı pakette mi?</li><li>SSL ve yedek dahil mi?</li><li>Taşıma desteği var mı?</li></ol><p>Bu soruların çoğuna "hayır" veya düşük rakam diyorsanız paylaşımlı hosting; "evet" diyorsanız VPS değerlendirin.</p>',
    '03_vps_nedir.php' => '<h2>VPS performans ipuçları</h2><p>PHP-FPM pm.max_children değerini RAM\'e göre ayarlayın; aşırı yüksek değer OOM killer tetikler. MySQL innodb_buffer_pool_size toplam RAM\'in %50–70\'i civarında olabilir. Nginx gzip ve brotli açık olsun. Swap alanı düşük RAM VPS\'te geçici nefes aldırır ancak kalıcı çözüm değildir.</p>',
    '04_bulut_sunucu.php' => '<h2>Bulut güvenlik pratikleri</h2><p>API anahtarlarını environment variable olarak saklayın; repoya commit etmeyin. Security group\'larda sadece gerekli portları açın. IAM benzeri rol tabanlı erişim kullanın. Düzenli penetration test veya vulnerability scan planlayın.</p>',
    '05_domain_nedir.php' => '<h2>Domain transfer checklist</h2><p>Transfer kilidi kapalı mı? Auth code alındı mı? 60 gün iç kuralı geçti mi? E-posta onayı yapıldı mı? DNS kayıtları yedeklendi mi? Transfer sırasında site kesintisiz kalır; sadece yönetim paneli değişir.</p>',
    '06_domain_uzantilari.php' => '<h2>Sektöre göre uzantı örnekleri</h2><p>Teknoloji startup: .com veya .io. Avukatlık bürosu: .com.tr. E-ticaret global: .com. E-ticaret yerel: .com.tr. Eğitim kurumu: .edu.tr. Dernek: .org.tr. Kişisel portfolyo: .com veya .me. Her sektörde müşteri beklentisi farklıdır; rakiplerinizi inceleyin.</p>',
    '07_ssl.php' => '<h2>SSL sorun giderme</h2><p>Sertifika süresi dolduysa yenileyin. Yanlış domain için sertifika yüklendiyse doğru SAN\'lı sertifika alın. Zincir eksikse intermediate certificate ekleyin. Cloudflare "flexible" SSL origin\'e HTTP gönderir; "full strict" kullanın.</p>',
    '08_hizlandirma.php' => '<h2>WordPress hız eklentileri</h2><p>LiteSpeed Cache (LiteSpeed sunucuda), WP Rocket (ücretli, popüler), Autoptimize (CSS/JS minify) sık kullanılır. Birden fazla cache eklentisi aynı anda çakışır; birini seçin. Object cache için Redis Object Cache eklentisi + sunucuda Redis gerekir.</p>',
    '09_wordpress.php' => '<h2>WordPress hosting benchmark</h2><p>Aynı tema ve 10 örnek yazı ile test kurun. Admin-ajax.php yanıt süresi, TTFB ve tam yüklenme süresini ölçün. WooCommerce varsa ürün sayfası ve checkout\'u ayrı test edin. Rakamları tabloya yazıp karar verin.</p>',
    '10_hosting_tasima.php' => '<h2>DNS kayıt yedekleme</h2><p>Taşımadan önce tüm DNS kayıtlarını (A, AAAA, CNAME, MX, TXT) ekran görüntüsü veya zone file export ile saklayın. SPF ve DKIM TXT kayıtlarını unutmak e-posta kesintisine yol açar. Google Workspace kullanıyorsanız MX öncelik değerlerini aynen taşıyın.</p>',
];

foreach ($extra as $file => $html) {
    $path = __DIR__.'/'.$file;
    $content = file_get_contents($path);
    if (str_contains($content, 'GENISLETME5')) {
        continue;
    }
    $content = str_replace('<h2>Sonuç</h2>', "<!-- GENISLETME5 -->\n".$html."\n<h2>Sonuç</h2>", $content);
    file_put_contents($path, $content);
}

foreach (glob(__DIR__.'/[0-9]*.php') as $f) {
    $d = require $f;
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($d['content']), $m);
    $c = count($m[0]);
    echo basename($f).' => '.$c.($c >= 1000 ? ' OK' : '')."\n";
}
