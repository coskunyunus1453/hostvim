<?php

$extra = [
    '03_vps_nedir.php' => '<h2>Özet tablo: VPS ne zaman?</h2><table><thead><tr><th>Durum</th><th>Öneri</th></tr></thead><tbody><tr><td>Yeni blog</td><td>Paylaşımlı</td></tr><tr><td>5K+ günlük ziyaret</td><td>VPS</td></tr><tr><td>Özel API</td><td>VPS</td></tr><tr><td>Kampanya trafiği</td><td>Bulut</td></tr></tbody></table><p>Bu tablo başlangıç noktasıdır; gerçek metrikleriniz kararı netleştirir.</p>',
    '04_bulut_sunucu.php' => '<h2>Bulut sunucu maliyet örneği</h2><p>2 GB RAM bulut sunucu aylık sabit ücret + ek trafik ile çalışabilir. Kampanya ayında 8 GB\'a çıkıp sonraki ay düşürmek dedicated sunucu satın almaktan ucuz olabilir. Faturanızı aylık inceleyin.</p>',
    '05_domain_nedir.php' => '<h2>Domain güvenlik ipuçları</h2><p>2FA açın, transfer kilidini unutmayın, registrar şifresini password manager\'da saklayın. Phishing mail ile auth code isteyen sahte maillere dikkat edin.</p>',
    '06_domain_uzantilari.php' => '<h2>.shop ve .store uzantıları</h2><p>E-ticaret odaklı yeni gTLD\'ler akılda kalıcı olabilir; ancak müşteri alışkanlığı hâlâ .com ve .com.tr yönündedir. Marka adınız kısa ve güçlüyse alternatif uzantı düşünülebilir.</p>',
    '07_ssl.php' => '<h2>HTTP Strict Transport Security</h2><p>HSTS header tarayıcıya "bu siteye sadece HTTPS ile gel" der. Yanlış yapılandırma siteye erişimi geçici engelleyebilir; önce kısa max-age ile test edin.</p>',
    '08_hizlandirma.php' => '<h2>Kritik CSS ve font yükleme</h2><p>Above-the-fold CSS inline, geri kalanı defer. Google Fonts yerine self-hosted font kullanımı GDPR ve hız açısından avantajlıdır. font-display: swap ile metin hemen görünür.</p>',
    '09_wordpress.php' => '<h2>Managed WordPress vs standart hosting</h2><p>Managed plan otomatik güncelleme ve staging sunar; maliyet yüksektir. Teknik ekibiniz yoksa değer. Kendi sunucunuzu yönetebiliyorsanız standart VPS + cache yeterli olabilir.</p>',
    '10_hosting_tasima.php' => '<h2>Gece taşıması avantajı</h2><p>02:00–06:00 arası trafik düşüktür; DNS propagation sırasında etkilenen kullanıcı sayısı azalır. Hafta sonu gece kombinasyonu birçok site için idealdir.</p>',
];

foreach ($extra as $file => $html) {
    $path = __DIR__.'/'.$file;
    $content = file_get_contents($path);
    if (str_contains($content, 'GENISLETME7')) {
        continue;
    }
    $content = str_replace('<h2>Sonuç</h2>', "<!-- GENISLETME7 -->\n".$html."\n<h2>Sonuç</h2>", $content);
    file_put_contents($path, $content);
}

foreach (glob(__DIR__.'/[0-9]*.php') as $f) {
    $d = require $f;
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($d['content']), $m);
    $c = count($m[0]);
    echo basename($f).' => '.$c.($c >= 1000 ? ' OK' : '')."\n";
}
