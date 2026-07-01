<?php

$blocks = [
    '01_web_hosting.php' => '<h2>Kapanış notu</h2><p>Hosting dünyası ilk bakışta karmaşık görünür; temel kavramları öğrendiğinizde ise tekrarlayan bir alışveriş haline gelir. Doğru sağlayıcıyla uzun yıllar sorunsuz çalışmak mümkündür.</p>',
    '02_paylasimli_vps.php' => <<<'HTML'
<h2>Sık sorulan ek sorular</h2>
<h3>CloudLinux ne işe yarar?</h3>
<p>Paylaşımlı sunucuda her kullanıcının CPU ve RAM tüketimini sınırlar; bir sitenin tüm sunucuyu kilitlemesini önler.</p>
<h3>VPS'te Docker kullanılabilir mi?</h3>
<p>Evet, yeterli RAM ile container çalıştırabilirsiniz; ancak paylaşımlı hostingde Docker genelde kapalıdır.</p>
<h3>Hangisi daha çevre dostu?</h3>
<p>Paylaşımlı hosting kaynak paylaşımıyla daha az boş kapasite bırakır; ölçek açısından verimli sayılabilir.</p>
HTML,
    '03_vps_nedir.php' => <<<'HTML'
<h2>VPS vs dedicated kısa tablo</h2>
<p>Dedicated sunucuda tüm fiziksel donanım size aittir; VPS'te sanal pay alırsınız. Dedicated aylık maliyet yüzlerce dolar olabilir. Orta ölçekli e-ticaret çoğu zaman 4–8 GB VPS ile rahatlar.</p>
<h2>Yedekleme stratejisi özeti</h2>
<p>3-2-1 kuralı: 3 kopya, 2 farklı ortam, 1 uzak lokasyon. VPS snapshot + haftalık uzak S3 yedek kombinasyonu güvenli başlangıçtır.</p>
HTML,
    '04_bulut_sunucu.php' => <<<'HTML'
<h2>Edge computing notu</h2>
<p>CDN edge sunucuları statik içeriği kullanıcıya yakın noktadan sunar; bulut sunucu ile birlikte kullanıldığında küresel performans artar. Dinamik içerik yine origin sunucudan gelir; cache stratejisi buna göre planlanmalıdır.</p>
HTML,
    '05_domain_nedir.php' => <<<'HTML'
<h2>Domain hikayesi: kısa vaka</h2>
<p>Bir yerel restoran zinciri önce uzun tireli domain aldı; müşteriler adresi yanlış yazdı. Kısa .com.tr'ye geçince telefon siparişleri %15 arttı. Kısa ve akılda kalıcı isim, offline pazarlamada da önemlidir.</p>
HTML,
    '06_domain_uzantilari.php' => <<<'HTML'
<h2>Yerel vs global marka</h2>
<p>Sadece Ankara'da hizmet veren tamirci için .com.tr yeterlidir. İhracat yapan üretici hem .com.tr hem .com alıp İngilizce siteyi .com'da tutabilir. Dil ve uzantı stratejisini birlikte planlayın.</p>
HTML,
    '07_ssl.php' => <<<'HTML'
<h2>Tarayıcı uyarıları rehberi</h2>
<p>"Bağlantınız gizli değil" uyarısı HTTP kullanımında görülür. "Sertifika geçersiz" ise süresi dolmuş veya yanlış domain sertifikası demektir. "Karma içerik" uyarısında HTTPS sayfadaki HTTP kaynakları düzeltilmelidir.</p>
HTML,
    '08_hizlandirma.php' => <<<'HTML'
<h2>Hosting yükseltme kararı</h2>
<p>Tüm yazılım optimizasyonlarına rağmen TTFB 800 ms üzerindeyse hosting kaynakları darboğazdır. Bir üst pakete geçiş veya VPS'e taşıma gündeme gelir. Önce ölçün, sonra yükseltin; körlemesine pahalı paket almak gereksizdir.</p>
HTML,
    '09_wordpress.php' => <<<'HTML'
<h2>WooCommerce özel notlar</h2>
<p>Sepet oturumu, ödeme gateway callback ve stok düşümü ek veritabanı yükü oluşturur. Canlı ortamda test siparişi vererek ödeme akışını doğrulayın. Staging'de gerçek API anahtarı kullanmayın.</p>
<h2>Güvenlik taraması rutini</h2>
<p>Haftalık Wordfence taraması, aylık kullanıcı listesi kontrolü ve bilinmeyen admin hesabı taraması basit ama etkili alışkanlıklardır.</p>
HTML,
    '10_hosting_tasima.php' => <<<'HTML'
<h2>Checklist PDF mantığı</h2>
<p>Taşıma günü yazdırılabilir checklist kullanın: yedek alındı mı, DB import edildi mi, wp-config güncellendi mi, SSL aktif mi, DNS değişti mi, e-posta test edildi mi, Analytics çalışıyor mu. Her maddeyi işaretleyerek ilerleyin; hafıza yerine kağıt güvenilirdir.</p>
HTML,
];

foreach ($blocks as $file => $html) {
    $path = __DIR__.'/'.$file;
    $content = file_get_contents($path);
    if (! str_contains($content, 'GENISLETME2')) {
        $content = str_replace('<h2>Sonuç</h2>', "<!-- GENISLETME2 -->\n".$html.'<h2>Sonuç</h2>', $content);
        file_put_contents($path, $content);
    }
}

foreach (glob(__DIR__.'/[0-9]*.php') as $f) {
    $d = require $f;
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($d['content']), $m);
    echo basename($f).' => '.count($m[0])."\n";
}
