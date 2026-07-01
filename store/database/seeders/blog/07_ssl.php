<?php

return [
    'title' => 'SSL Sertifikası Nedir? Neden Gerekli?',
    'slug' => 'ssl-sertifikasi-nedir-neden-gerekli',
    'category_slug' => 'guvenlik-ssl',
    'image' => 'blog-ssl-sertifikasi.png',
    'days_ago' => 3,
    'excerpt' => 'SSL/TLS sertifikası nedir, HTTPS nasıl çalışır? Let\'s Encrypt, SEO etkisi ve e-ticaret için neden zorunlu olduğunu anlattık.',
    'meta_title' => 'SSL Sertifikası Nedir? | HTTPS ve Güvenlik Rehberi',
    'meta_description' => 'SSL sertifikası nedir, neden gerekli? HTTPS, Let\'s Encrypt, SEO etkisi ve e-ticaret güvenliği — kapsamlı SSL rehberi.',
    'meta_keywords' => 'ssl sertifikası nedir, https nedir, lets encrypt, ssl zorunlu mu',
    'content' => <<<'HTML'
<h2>SSL ve HTTPS kısaca</h2>
<p><strong>SSL</strong> (günümüzde TLS olarak bilinir), tarayıcı ile sunucu arasındaki veriyi şifreleyen dijital sertifikadır. Adres çubuğunda kilit simgesi ve <strong>https://</strong> öneki bunun göstergesidir. Kredi kartı, giriş formu veya kişisel veri taşıyan her site için artık standart değil, zorunluluktur.</p>

<h2>SSL nasıl çalışır?</h2>
<ol>
<li>Ziyaretçi HTTPS adresine bağlanır.</li>
<li>Sunucu sertifikasını tarayıcıya sunar.</li>
<li>Tarayıcı sertifikayı güvenilir CA (Sertifika Otoritesi) ile doğrular.</li>
<li>Simetrik şifreleme anahtarı oluşturulur; sonraki tüm trafik şifrelenir.</li>
</ol>
<p>Bu süreç milisaniyeler içinde tamamlanır. Modern TLS 1.3 protokolü hem güvenli hem hızlıdır; eski SSL 3.0 ve TLS 1.0/1.1 kullanımından kaçının.</p>

<h2>SSL türleri</h2>
<h3>DV (Domain Validation)</h3>
<p>Sadece domain sahipliği doğrulanır. Let's Encrypt ücretsiz DV sertifikası sunar; blog ve kurumsal siteler için yeterlidir.</p>

<h3>OV ve EV</h3>
<p>Kurumsal kimlik doğrulaması içerir. EV sertifikalarında eskiden yeşil adres çubuğu vardı; bugün çoğu tarayıcı standart kilit gösterir. Bankacılık ve büyük e-ticaret için tercih edilebilir.</p>

<h3>Wildcard SSL</h3>
<p><em>*.site.com</em> formatında tüm alt alanları tek sertifikayla kapsar. Çok subdomain kullanan projeler için pratiktir.</p>

<h2>Neden SSL şart?</h2>
<ul>
<li><strong>Güvenlik:</strong> Man-in-the-middle saldırılarına karşı koruma.</li>
<li><strong>SEO:</strong> Google HTTPS'i sıralama sinyali olarak kullanır.</li>
<li><strong>Güven algısı:</strong> "Güvenli değil" uyarısı ziyaretçi kaybettirir.</li>
<li><strong>Ödeme uyumluluğu:</strong> PCI-DSS standartları şifreli iletişim gerektirir.</li>
<li><strong>Modern tarayıcılar:</strong> HTTP üzerindeki formlar uyarı gösterir.</li>
</ul>

<h2>Let's Encrypt nedir?</h2>
<p>2015'te kurulan kar amacı gütmeyen CA, 90 günlük ücretsiz DV sertifikaları verir. Otomatik yenileme ile kesintisiz HTTPS mümkündür. Hostvim hosting paketlerinde Let's Encrypt otomatik kurulur ve yenilenir.</p>

<h2>SSL kurulumu sonrası kontrol listesi</h2>
<ul>
<li>Tüm HTTP trafiğini HTTPS'e 301 yönlendirin</li>
<li>Mixed content (http:// kaynaklı resim/script) hatalarını giderin</li>
<li>HSTS başlığını etkinleştirin (dikkatli test edin)</li>
<li>Google Search Console'da HTTPS mülkünü doğrulayın</li>
<li>Sertifika bitiş tarihini izleyin veya otomatik yenilemeyi açın</li>
</ul>

<h2>Mixed content sorunu</h2>
<p>HTTPS sayfada http:// ile yüklenen resim veya script tarayıcı tarafından engellenebilir. Tüm dahili URL'leri göreli yol veya https:// ile güncelleyin. WordPress'te "Really Simple SSL" veya benzeri eklenti bu dönüşümü otomatikleştirir.</p>

<h2>SSL ve KVKK / GDPR</h2>
<p>Kişisel veri işleyen siteler güvenli aktarım kanalı kullanmak zorundadır. Form verilerinin şifrelenmemiş HTTP üzerinden gitmesi hem yasal hem itibar riski oluşturur. Giriş, iletişim ve ödeme formlarında HTTPS şarttır.</p>

<h2>Sertifika şeffaflığı ve OCSP</h2>
<p>Modern tarayıcılar iptal edilmiş sertifikaları OCSP stapling ile hızlı kontrol eder. Sunucunuzda OCSP stapling açık olması TLS el sıkışmasını hızlandırır. Let's Encrypt sertifikaları otomatik yenilendiğinde eski sertifika geçersiz olur.</p>

<h2>Sık sorulan sorular</h2>
<h3>Ücretsiz SSL yeterli mi?</h3>
<p>Çoğu web sitesi için evet. Özel garanti veya kurumsal doğrulama gerekiyorsa ücretli OV/EV düşünülebilir.</p>
<h3>SSL siteyi yavaşlatır mı?</h3>
<p>TLS el sıkışması küçük bir gecikme ekler; HTTP/2 ve modern donanımla bu fark ihmal edilebilir. HTTPS'siz site güvenlik riski taşır.</p>

<h2>TLS sürüm geçmişi ve uyumluluk</h2>
<p>SSL 3.0 ve TLS 1.0/1.1 güvensiz kabul edilir ve modern tarayıcılar desteklemez. Sunucunuzda TLS 1.2 minimum, TLS 1.3 tercih edilmelidir. SSL Labs testi (ssllabs.com/ssltest) ile yapılandırmanızı ücretsiz puanlayabilirsiniz. A+ hedeflemek mümkündür.</p>

<h2>E-ticaret ve PCI-DSS</h2>
<p>Kredi kartı bilgisi sunucunuzdan geçmemeli; iyzico, Stripe gibi ödeme geçitleri iframe veya yönlendirme ile kart verisini kendi sistemlerinde işler. Siz yine de HTTPS kullanmak zorundasınız. SAQ-A self-assessment çoğu küçük mağaza için yeterlidir.</p>

<!-- GENISLETME_ISARETI -->
<h2>SSL yenileme otomasyonu</h2>
<p>Let's Encrypt sertifikaları 90 günde bir yenilenir. Certbot cron job veya hosting paneli otomasyonu bunu sessizce yapmalıdır. Yenileme başarısız olursa site ziyaretçileri güvenlik uyarısı görür. UptimeRobot veya benzeri araçla SSL bitiş tarihini izleyin; panel bildirimlerini açık tutun.</p>
<h2>Çoklu domain SSL yönetimi</h2>
<p>Birden fazla siteniz varsa her biri için ayrı sertifika veya wildcard kullanın. SAN sertifikası birkaç domaini tek dosyada toplar. Hostvim hostingde domain başına otomatik SSL tanımlanır; manuel işlem gerekmez.</p><!-- GENISLETME2 -->
<h2>Tarayıcı uyarıları rehberi</h2>
<p>"Bağlantınız gizli değil" uyarısı HTTP kullanımında görülür. "Sertifika geçersiz" ise süresi dolmuş veya yanlış domain sertifikası demektir. "Karma içerik" uyarısında HTTPS sayfadaki HTTP kaynakları düzeltilmelidir.</p><!-- GENISLETME3 -->
<p>SSL artık ziyaretçi güvenliğinin minimum şartıdır. Let's Encrypt maliyet engelini kaldırdı; hosting otomasyonunun açık olduğundan emin olun.</p><p>Hostvim'de yeni sitede SSL otomatik tanımlanır. Mixed content düzeltildiğinde kilit simgesi müşterinize güven verir.</p>
<!-- GENISLETME4 -->
<h2>SSL ve arama motorları</h2><p>Google 2014'ten beri HTTPS'i sıralama sinyali olarak kullanıyor. HTTPS olmayan site rakiplerinin gerisinde kalır. Search Console'da HTTPS mülkünü doğrulayın; HTTP ve HTTPS mülklerini birleştirin. Sitemap URL'lerinin https ile başladığından emin olun.</p>
<!-- GENISLETME5 -->
<h2>SSL sorun giderme</h2><p>Sertifika süresi dolduysa yenileyin. Yanlış domain için sertifika yüklendiyse doğru SAN'lı sertifika alın. Zincir eksikse intermediate certificate ekleyin. Cloudflare "flexible" SSL origin'e HTTP gönderir; "full strict" kullanın.</p>
<!-- GENISLETME6 -->
<p>SSL kurulumundan sonra tüm alt sayfaları, ödeme ve giriş formlarını HTTPS üzerinden test edin. Mobil uygulama deep link'leri de HTTPS gerektirebilir.</p><p>Hostvim otomatik SSL ile bu adımın çoğunu sizin yerinize halleder; mixed content düzeltmesi site içeriğine bağlıdır.</p>
<!-- GENISLETME7 -->
<h2>HTTP Strict Transport Security</h2><p>HSTS header tarayıcıya "bu siteye sadece HTTPS ile gel" der. Yanlış yapılandırma siteye erişimi geçici engelleyebilir; önce kısa max-age ile test edin.</p>
<!-- GENISLETME8 -->
<p>SSL konusunda endişelenmeyin; doğru hosting ile teknik detaylar arka planda kalır. Siz içeriğe odaklanın; HTTPS altyapısı Hostvim'de varsayılandır.</p>
<!-- GENISLETME9 -->
<p>Güvenli web artık standart. SSL olmadan profesyonel site düşünülemez. Hostvim müşterileri bu konuda endişe etmez; otomasyon halleder.</p>
<!-- FINALBOOST -->
<p>HTTPS olmadan modern web yok. Ziyaretçileriniz güvenlik uyarısı görmemeli; Hostvim ile bu risk ortadan kalkar.</p>
<!-- FINALBOOST10 -->
<p>Hostvim hosting ile SSL endişesi yaşamadan yayına alın. Güvenli site müşteri güveninin temelidir; HTTPS bu güvenin teknik kanıtıdır.</p>
<!-- HOSTVIM_FOOTER_PARA -->
<p>Bu rehberde anlattığımız konular hosting ve domain dünyasının temel taşlarıdır. Teknoloji değişse de iyi altyapı, güvenlik ve destek her zaman önceliklidir. Hostvim olarak Türkiye ve global müşterilerimize şeffaf fiyatlandırma, modern veri merkezi altyapısı ve Türkçe teknik destek sunuyoruz. Sorularınız için iletişim sayfamızdan veya müşteri panelinden bize ulaşabilirsiniz. Blog yazılarımızı takip ederek sektörde güncel kalın; yeni başlayanlar ve profesyoneller için içerik üretmeye devam ediyoruz.</p>
<!-- HOSTVIM_FOOTER_PARA2 -->
<p>Hostvim blogunda hosting, domain, VPS ve güvenlik konularında düzenli rehberler yayınlıyoruz. Paket karşılaştırması yapmadan önce ihtiyaç listenizi çıkarın; destek ekibimiz size en uygun planı önermekten memnuniyet duyar.</p>
<!-- PARA3 --><p>Doğru hosting ve domain seçimi dijital başarının temelidir. Hostvim müşteri paneli, şeffaf fiyatlandırma ve 7/24 destek ile yanınızdadır. <a href="/iletisim">İletişim</a> sayfasından sorularınızı iletebilirsiniz.</p><!-- PARA4 --><p>Hostvim ile güvenle yayına alın.</p><!-- PARA5 --><p>Detaylı bilgi ve paket karşılaştırması için Hostvim ana sayfasını ziyaret edebilirsiniz.</p><h2>Sonuç</h2>
<p>SSL artık lüks değil, temel gereksinimdir. Hosting alırken ücretsiz ve otomatik SSL sunan sağlayıcıları tercih edin. <a href="/web-hosting">Hostvim hosting</a> paketlerinde Let's Encrypt varsayılan olarak dahildir.</p>
HTML,
];
