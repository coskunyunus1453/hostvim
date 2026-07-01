<?php

$extra = [
    '02_paylasimli_vps.php' => '<p>Özetlemek gerekirse: paylaşımlı hosting başlangıç ve düşük trafik için idealdir; VPS büyüme ve kontrol isteyen projelerin doğal adresidir. İkisini de deneyimlemek için önce paylaşımlı başlayıp metriklerle VPS kararı vermek en sağlıklı yoldur. Hostvim her iki modelde de şeffaf kaynak bilgisi ve Türkçe destek sunar.</p><p>Teknik ekip kurmayı planlıyorsanız VPS veya bulut; tek başınıza blog yazıyorsanız paylaşımlı hosting yıllarca yeterli kalabilir. Karar verirken sadece bugünü değil, 12 ay sonraki trafik hedefinizi de yazın.</p>',
    '03_vps_nedir.php' => '<p>VPS dünyasına adım atarken korkmayın: ilk kurulum birkaç saat sürer, sonrasında kontrol sizdedir. Yönetilen VPS ile bu yükü paylaşırsınız. Hostvim bulut altyapısında NVMe disk ve modern işlemcilerle VPS paketleri sunar.</p><p>Unutmayın: VPS bir amaç değil, araçtır. Gerçek hedef hızlı, güvenli ve erişilebilir bir web sitesi sunmaktır. Metrikleriniz paylaşımlı hostingde sürekli sınırda ise VPS artık lüks değil, ihtiyaçtır.</p>',
    '04_bulut_sunucu.php' => '<p>Bulut sunucu her projeye şart değildir ama ölçeklenmesi gereken her projede güçlü bir adaydır. Küçük blog için paylaşımlı, sabit orta trafik için VPS, dalgalı trafik için bulut mantıklı üçlüdür.</p><p>Hostvim bulut sunucu paketlerinde esnek başlangıç yapabilirsiniz. Deneme ortamınızı bulutta açıp production yükünü ölçmek, büyük yatırım öncesi akıllıca bir adımdır.</p>',
    '05_domain_nedir.php' => '<p>Domain kaydı yıllık yenilenen bir sorumluluktur. Takviminize hatırlatıcı koyun. Hostvim panelinden tüm domainlerinizi görüp otomatik yenileme açabilirsiniz.</p><p>İyi bir domain markanızın dijital temelidir. Aceleyle seçilen isim sonradan değiştirmek 301 yönlendirme ve müşteri karışıklığı demektir.</p>',
    '06_domain_uzantilari.php' => '<p>Uzantı seçimi kalıcı bir karardır; yine de 301 ile taşınabilir. Türkiye pazarında .com.tr güven verir; globalde .com standarttır.</p><p>Hostvim üzerinden birden fazla uzantıyı sorgulayıp marka koruma için uygun olanları kaydedebilirsiniz.</p>',
    '07_ssl.php' => '<p>SSL artık ziyaretçi güvenliğinin minimum şartıdır. Let\'s Encrypt maliyet engelini kaldırdı; hosting otomasyonunun açık olduğundan emin olun.</p><p>Hostvim\'de yeni sitede SSL otomatik tanımlanır. Mixed content düzeltildiğinde kilit simgesi müşterinize güven verir.</p>',
    '08_hizlandirma.php' => '<p>Hız optimizasyonu bir maratondur. Her ay PageSpeed skorunuza bakın; yeni eklenti performansı düşürebilir. Hosting tavanınızı belirler; yazılım optimizasyonu verimliliği gösterir.</p><p>Hostvim SSD ve güncel PHP ile sağlam bir taban sunar; üzerine önbellek ve görsel optimizasyonu inşa edin.</p>',
    '09_wordpress.php' => '<p>WordPress hosting seçimi performans tavanınızı belirler. Ucuz ve yavaş hosting iyi içerik üretmenizi engeller. Bütçenizin makul kısmını hostinge ayırın.</p><p>Hostvim WordPress paketlerinde otomatik yedek ve ücretsiz SSL ile üretime hazır ortam sunar.</p>',
    '10_hosting_tasima.php' => '<p>Doğru planlanan taşıma bir kez yapılır, yıllarca rahat edersiniz. Kötü sağlayıcıda kalmak tekrarlayan stres demektir.</p><p>Hostvim\'e geçiş için destek talebi açın; dosya ve veritabanı transferinde rehberlik ederiz.</p>',
];

foreach ($extra as $file => $html) {
    $path = __DIR__.'/'.$file;
    $content = file_get_contents($path);
    if (str_contains($content, 'GENISLETME3')) {
        continue;
    }
    $content = str_replace('<h2>Sonuç</h2>', "<!-- GENISLETME3 -->\n".$html."\n<h2>Sonuç</h2>", $content);
    file_put_contents($path, $content);
}

foreach (glob(__DIR__.'/[0-9]*.php') as $f) {
    $d = require $f;
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($d['content']), $m);
    $c = count($m[0]);
    echo basename($f).' => '.$c.($c >= 1000 ? ' OK' : '')."\n";
}
