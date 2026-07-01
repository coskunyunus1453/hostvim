<?php

/** Tek seferlik genişletme — çalıştır: php database/seeders/blog/_expand.php */

$blocks = [
    '01_web_hosting.php' => <<<'HTML'
<h2>Yeni başlayanlar için ilk 24 saat checklist</h2>
<p>Hosting ve domain aldıktan sonra şu sırayı izleyin: panelden nameserver veya A kaydını doğrulayın, SSL sertifikasının aktif olduğunu kontrol edin, tek tıkla WordPress veya statik dosyalarınızı yükleyin, iletişim formunu test edin, Google Search Console'a site ekleyin, robots.txt ve sitemap.xml erişimini doğrulayın. Bu adımlar basit görünür ama atlandığında haftalarca indekslenme gecikmesi yaşanır.</p>
<p>Hostvim müşterileri sipariş sonrası e-posta ile panel bilgilerini alır; DNS yönlendirmesi için destek dökümanları Türkçe sunulur. Takıldığınız noktada ticket açmak saatlerce forum aramaktan hızlıdır.</p>
<h2>Hosting maliyetini doğru hesaplama</h2>
<p>Sadece aylık paket fiyatına bakmayın. Domain yenileme, SSL (ücretsiz olmalı), yedekleme, e-posta kotası ve vergi dahil toplam sahip olma maliyetini (TCO) hesaplayın. Yıllık ödemede %10–20 indirim yaygındır. İlk yıl kampanyalı fiyat, ikinci yıl normal fiyat olabilir; yenileme tablosunu okuyun.</p>
HTML,
    '02_paylasimli_vps.php' => <<<'HTML'
<h2>Geçiş sürecinde dikkat edilecekler</h2>
<p>Paylaşımlı hostingden VPS'e geçerken dosya izinleri, PHP sürümü ve veritabanı charset (utf8mb4) uyumunu kontrol edin. wp-config.php içindeki sabit URL'ler yeni ortama uygun olmalıdır. Geçiş öncesi tam yedek alın; DNS TTL'i düşürün. İlk hafta eski hostingi kapatmayın.</p>
<p>VPS'te mail() fonksiyonu varsayılan olarak spam'e düşebilir; SMTP eklentisi (WP Mail SMTP) kullanın. Cron job'ları sunucu crontab'a taşıyın; wp-cron.php web isteği yerine gerçek cron daha güvenilirdir.</p>
<h2>Uzun vadeli maliyet karşılaştırması</h2>
<p>Paylaşımlı hosting 3 yıllık TCO düşük görünür; ancak site büyüdükçe performans kaybı müşteri kaybına dönüşür. VPS başlangıç maliyeti yüksek olsa da dönüşüm oranı artışı yatırımı geri ödeyebilir. Rakamlarla plan yapın: aylık ziyaretçi, sayfa görüntüleme ve hedef dönüşüm oranı.</p>
HTML,
    '03_vps_nedir.php' => <<<'HTML'
<h2>VPS güvenlik sertleştirme (hardening)</h2>
<p>SSH portunu 22'den değiştirmek bot taramalarını azaltır. Root login'i kapatıp sudo yetkili ayrı kullanıcı oluşturun. Otomatik güvenlik güncellemelerini (unattended-upgrades) açın. ModSecurity WAF kuralları web saldırılarına karşı ek katman sağlar. Düzenli olarak <code>lynis audit system</code> ile güvenlik skorunuzu ölçebilirsiniz.</p>
<h2>Ne zaman dedicated sunucuya geçilir?</h2>
<p>VPS kaynakları fiziksel sunucunun üst sınırına dayandığında dedicated (ayrılmış) sunucu düşünülür. Yüksek I/O gerektiren veritabanı sunucuları, oyun sunucuları veya milyonlarca aylık ziyaretçi bu kategoriye girer. Çoğu KOBİ projesi için VPS yıllarca yeterlidir.</p>
HTML,
    '04_bulut_sunucu.php' => <<<'HTML'
<h2>Bulut sağlayıcı seçim kriterleri</h2>
<p>Veri merkezi lokasyonu, API desteği, snapshot fiyatlandırması, egress (çıkış trafiği) ücreti ve destek SLA'sı karşılaştırın. Büyük hyperscaler'lar (AWS, GCP, Azure) esnek ama karmaşıktır; bölgesel sağlayıcılar (Hostvim gibi) basit fiyatlandırma ve Türkçe destek sunar. Karmaşıklık ihtiyacınız yoksa yerel bulut çoğu zaman daha verimlidir.</p>
<h2>Auto-scaling örneği</h2>
<p>Bir e-ticaret sitesi normalde 2 GB RAM kullanır; kampanya günü 8 GB'a çıkar, ertesi gün tekrar düşer. Bulut panelinden eşik kuralları tanımlanır: CPU %80 üzeri 5 dakika sürerse RAM ekle. Bu esneklik fiziksel sunucu satın almaktan ucuz olabilir; fakat izleme şarttır.</p>
HTML,
    '05_domain_nedir.php' => <<<'HTML'
<h2>Domain ve e-posta itibarı</h2>
<p>Yeni alınan domainler e-posta gönderiminde "yeni domain" filtresine takılabilir. Isınma (warm-up) süreci: ilk günlerde az sayıda mail gönderin, SPF/DKIM/DMARC'ı doğru yapılandırın. Toplu mail için domain itibarını riske atmayın; ayrı subdomain (mail.sirket.com) kullanın.</p>
<h2>Whois ve gizlilik</h2>
<p>WHOIS sorgusu domain sahibinin iletişim bilgilerini gösterir. Privacy protection bu bilgileri gizler; spam ve kimlik avı aramalarını azaltır. Yasal zorunluluk halinde registrar gerçek bilgiyi yetkili makamlara verir.</p>
HTML,
    '06_domain_uzantilari.php' => <<<'HTML'
<h2>Startup ve girişimler için uzantı stratejisi</h2>
<p>Erken aşamada .com alıp .com.tr'yi de koruma altına almak mantıklıdır. Yatırım turu veya global expansion planlıyorsanız .com ana domain olmalıdır. Yerel pazar testi için .com.tr ile başlayıp büyüyünce .com'a geçiş de mümkündür; 301 yönlendirme ile SEO değeri taşınır.</p>
<h2>Domain broker ve premium fiyatlar</h2>
<p>Kısa .com domainleri on binlerce dolar satılabilir. Bütçeniz yoksa bileşik kelime (gethostvim.com gibi) veya alternatif uzantı düşünün. Premium domain satın alırken escrow servisi kullanın; dolandırıcılığa dikkat edin.</p>
HTML,
    '07_ssl.php' => <<<'HTML'
<h2>SSL yenileme otomasyonu</h2>
<p>Let's Encrypt sertifikaları 90 günde bir yenilenir. Certbot cron job veya hosting paneli otomasyonu bunu sessizce yapmalıdır. Yenileme başarısız olursa site ziyaretçileri güvenlik uyarısı görür. UptimeRobot veya benzeri araçla SSL bitiş tarihini izleyin; panel bildirimlerini açık tutun.</p>
<h2>Çoklu domain SSL yönetimi</h2>
<p>Birden fazla siteniz varsa her biri için ayrı sertifika veya wildcard kullanın. SAN sertifikası birkaç domaini tek dosyada toplar. Hostvim hostingde domain başına otomatik SSL tanımlanır; manuel işlem gerekmez.</p>
HTML,
    '08_hizlandirma.php' => <<<'HTML'
<h2>Önbellek katmanları derinlemesine</h2>
<p>Tarayıcı önbelleği (Cache-Control header), CDN edge cache, sayfa cache (HTML), object cache (Redis), opcode cache (OPcache) ve veritabanı sorgu cache farklı katmanlardır. Hepsi birlikte çalıştığında TTFB ve LCP dramatik iyileşir. Hangi katmanın eksik olduğunu waterfall grafiğinden okuyun.</p>
<h2>Görsel CDN ve lazy load</h2>
<p>Büyük hero görselleri LCP'yi bozar. Responsive srcset ile mobilde küçük dosya sunun. fetchpriority="high" sadece LCP adayı görsele verin. Diğer görsellerde loading="lazy" kullanın. Video için poster görseli ve preload dikkatli planlayın.</p>
HTML,
    '09_wordpress.php' => <<<'HTML'
<h2>WordPress hosting red flags</h2>
<p>Şunları gördüğünüzde uzak durun: PHP 7.4 veya altı, "sınırsız" ama CPU throttle, yedekleme yok, tek veri merkezi ve destek yok, gizli kurulum ücreti. Ucuz hosting uzun vadede taşıma maliyeti ve kayıp gelir demek olabilir.</p>
<h2>Performans testi yapmadan almayın</h2>
<p>Birçok sağlayıcı deneme süresi sunar. Aynı WordPress yedeğinizi yükleyip PageSpeed skorunu karşılaştırın. Admin paneli hızı da önemlidir; yavaş wp-admin içerik üretimini yavaşlatır.</p>
<h2>Topluluk ve ekosistem</h2>
<p>WordPress.org, Türkçe forumlar ve Hostvim bilgi bankası sorun çözümünde hız kazandırır. Hosting firmanızın WordPress odaklı dökümantasyonu olması uzun vadede zaman kazandırır.</p>
HTML,
    '10_hosting_tasima.php' => <<<'HTML'
<h2>Taşıma öncesi paydaş iletişimi</h2>
<p>E-ticaret sitesi taşırken müşterilere kısa bakım penceresi duyurusu yapın. Sosyal medyada "geçici yavaşlık" bilgisi güven oluşturur. Taşıma sonrası ödeme ve sipariş akışını manuel test edin.</p>
<h2>Rollback planı</h2>
<p>DNS değişikliğinden önce eski sunucunun tam yedeğini ve DNS eski değerlerini not edin. Sorun çıkarsa DNS'i geri alarak 1 saat içinde eski ortama dönebilirsiniz. Panik yapmadan checklist takip edin.</p>
<h2>Profesyonel destek ne zaman şart?</h2>
<p>Özel yazılım, çoklu veritabanı, microservice veya 50 GB üzeri dosya varsa profesyonel taşıma hizmeti alın. Hostvim ücretsiz taşıma kotası standart WordPress siteleri için yeterlidir; karmaşık projelerde özel teklif isteyin.</p>
HTML,
];

foreach ($blocks as $file => $html) {
    $path = __DIR__.'/'.$file;
    if (! is_file($path)) {
        echo "Atlandı: $file\n";
        continue;
    }
    $content = file_get_contents($path);
    if (str_contains($content, 'GENISLETME_ISARETI')) {
        echo "Zaten genişletilmiş: $file\n";
        continue;
    }
    $marker = "<!-- GENISLETME_ISARETI -->\n";
    $content = str_replace('<h2>Sonuç</h2>', $marker.$html.'<h2>Sonuç</h2>', $content);
    file_put_contents($path, $content);
    echo "Genişletildi: $file\n";
}
