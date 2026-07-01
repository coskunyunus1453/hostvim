<?php

return [
    'title' => 'Domain Nedir? Nasıl Alınır?',
    'slug' => 'domain-nedir-nasil-alinir',
    'category_slug' => 'domain-dns',
    'image' => 'blog-domain-nedir.png',
    'days_ago' => 5,
    'excerpt' => 'Domain (alan adı) nedir, nasıl çalışır ve ilk domaininizi nasıl kayıt edersiniz? DNS, WHOIS ve pratik adımlar.',
    'meta_title' => 'Domain Nedir? Nasıl Alınır? | Alan Adı Rehberi',
    'meta_description' => 'Domain nedir, nasıl alınır? Alan adı kaydı, DNS ayarları, WHOIS gizliliği ve hostinge bağlama adımları — başlangıç rehberi.',
    'meta_keywords' => 'domain nedir, alan adı nasıl alınır, domain kaydı, dns nedir',
    'content' => <<<'HTML'
<h2>Domain basitçe nedir?</h2>
<p><strong>Domain</strong> (alan adı), internetteki adresinizdir: <em>ornek.com</em> gibi. İnsanların IP adreslerini (207.180.237.13 gibi) ezberlemek yerine okunabilir isimler kullanmasını sağlar. Domain Sistemi (DNS), bu ismi doğru sunucunun IP'sine çeviren küresel bir rehberdir.</p>

<h2>Domain yapısı nasıl okunur?</h2>
<p><strong>ornek.com.tr</strong> adresini parçalayalım:</p>
<ul>
<li><strong>ornek</strong> — SLD (Second Level Domain), sizin seçtiğiniz isim</li>
<li><strong>.com</strong> — gTLD (genel üst seviye domain)</li>
<li><strong>.tr</strong> — ülke kodu (ccTLD), Türkiye</li>
</ul>
<p>Uzantı seçimi marka algınızı ve SEO'yu etkiler. Detay için <a href="/blog/domain-uzantilari-rehberi">domain uzantıları rehberi</a> yazımıza bakın.</p>

<h2>Domain nasıl alınır? Adım adım</h2>
<ol>
<li><strong>İsim belirleyin:</strong> Kısa, telaffuzu kolay, markanızla uyumlu olsun. Tire (-) kullanımını minimumda tutun.</li>
<li><strong>Müsaitlik kontrolü:</strong> <a href="/domain">Hostvim domain sorgulama</a> aracıyla adresin boşta olup olmadığına bakın.</li>
<li><strong>Uzantı seçin:</strong> .com global erişim için popüler; .com.tr Türkiye odaklı projelerde güven verir.</li>
<li><strong>Kayıt süresi:</strong> En az 1 yıl; uzun süreli kayıt bazen indirimli olur.</li>
<li><strong>WHOIS gizliliği:</strong> Kişisel bilgilerinizi kamuya açmamak için privacy koruması aktif edin.</li>
<li><strong>Ödeme ve onay:</strong> Kayıt tamamlandığında domain panelinizde görünür.</li>
</ol>

<h2>DNS kayıtları ne işe yarar?</h2>
<h3>A kaydı</h3>
<p>Domaini sunucunun IPv4 adresine yönlendirir. Hosting aldığınızda sağlayıcının verdiği IP'yi A kaydına yazarsınız.</p>

<h3>CNAME kaydı</h3>
<p>Bir alt alanı başka bir adrese yönlendirir. Örneğin <em>www</em> için CNAME kullanılabilir.</p>

<h3>MX kaydı</h3>
<p>E-posta sunucusunu tanımlar. Google Workspace kullanıyorsanız MX kayıtları Google'a işaret eder.</p>

<h3>NS (nameserver) kaydı</h3>
<p>Domainin DNS yönetimini hangi sunucuların yaptığını belirler. Hosting ve domain aynı firmadaysa nameserver'ları hosting sağlayıcısına çevirmek yeterlidir.</p>

<h2>Domain ve hosting ilişkisi</h2>
<p>Domain ile hosting birbirinden bağımsız satın alınabilir. Önemli olan DNS'in doğru yapılandırılmasıdır. Domain Hostvim'de, hosting başka firmadaysa A kaydı veya nameserver güncellemesi yapılır. Tersi de geçerlidir.</p>

<h2>Domain yenileme ve süre</h2>
<p>Domainler süreli kiralanır; süre bitince "grace period" ve ardından "redemption" dönemine girer. Süresi dolmuş domain başkası tarafından alınabilir. Otomatik yenilemeyi açık tutun ve kayıtlı e-postanızı güncel tutun.</p>

<h2>Sık yapılan hatalar</h2>
<ul>
<li>Yanlış IP ile A kaydı — site açılmaz</li>
<li>SSL öncesi www/non-www tutarsızlığı — SEO duplicate content</li>
<li>WHOIS'te eski e-posta — yenileme hatırlatması kaçırılır</li>
<li>Marka ihlali riski taşıyan isim seçimi</li>
</ul>

<h2>Subdomain (alt alan adı) nedir?</h2>
<p><strong>blog.ornek.com</strong> veya <strong>shop.ornek.com</strong> birer alt alan adıdır. Ana domain kaydı altında DNS panelinden A veya CNAME kaydı ile oluşturulur. Ek ücret çoğu zaman gerekmez; hosting paketinizin subdomain limitine dikkat edin.</p>

<h2>Domain park etme ve satış</h2>
<p>Henüz site açmadan domain alıp "park" sayfasına yönlendirebilirsiniz. Domain yatırımı (domaining) ayrı bir iştir; ticari marka ihlali yapmadan genel isimler tescil edilebilir. Satış için Sedo, Afternic gibi pazar yerleri kullanılır.</p>

<h2>ICANN ve TRABİS düzenlemeleri</h2>
<p>Küresel domainler ICANN politikalarına tabidir. .tr uzantılı alan adları TRABİS (BTK) kurallarına uyar. WHOIS bilgilerinin doğru tutulması zorunludur; sahte bilgi domain iptaline yol açabilir.</p>

<h2>Sık sorulan sorular</h2>
<h3>Domain satın almak marka tescili midir?</h3>
<p>Hayır. Domain kaydı ile marka tescili farklı hukuki süreçlerdir.</p>
<h3>Transfer ne kadar sürer?</h3>
<p>Registrar'lar arası transfer genelde 5–7 gün; transfer kilidi kapalı ve auth code hazır olmalıdır.</p>

<h2>DNS yayılma süresi (propagation)</h2>
<p>Nameserver veya A kaydı değiştirdiğinizde değişikliğin dünya genelinde yayılması 1–48 saat sürebilir. TTL (Time To Live) düşükse yayılma hızlanır. whatsmydns.net gibi araçlarla farklı ülkelerden kontrol edebilirsiniz. Taşıma sırasında eski ve yeni sunucu paralel çalışabilir; bu normaldir.</p>

<h2>Domain ve marka uyumu</h2>
<p>Seçeceğiniz isim telaffuzu kolay, yazımı net ve sosyal medya kullanıcı adlarıyla uyumlu olmalı. Rakip markalara benzer isimler hukuki sorun çıkarır. Türkçe karakter içeren domainler (IDN) mümkündür ancak e-posta uyumluluğunda dikkatli olun.</p>

<!-- GENISLETME_ISARETI -->
<h2>Domain ve e-posta itibarı</h2>
<p>Yeni alınan domainler e-posta gönderiminde "yeni domain" filtresine takılabilir. Isınma (warm-up) süreci: ilk günlerde az sayıda mail gönderin, SPF/DKIM/DMARC'ı doğru yapılandırın. Toplu mail için domain itibarını riske atmayın; ayrı subdomain (mail.sirket.com) kullanın.</p>
<h2>Whois ve gizlilik</h2>
<p>WHOIS sorgusu domain sahibinin iletişim bilgilerini gösterir. Privacy protection bu bilgileri gizler; spam ve kimlik avı aramalarını azaltır. Yasal zorunluluk halinde registrar gerçek bilgiyi yetkili makamlara verir.</p><!-- GENISLETME2 -->
<h2>Domain hikayesi: kısa vaka</h2>
<p>Bir yerel restoran zinciri önce uzun tireli domain aldı; müşteriler adresi yanlış yazdı. Kısa .com.tr'ye geçince telefon siparişleri %15 arttı. Kısa ve akılda kalıcı isim, offline pazarlamada da önemlidir.</p><!-- GENISLETME3 -->
<p>Domain kaydı yıllık yenilenen bir sorumluluktur. Takviminize hatırlatıcı koyun. Hostvim panelinden tüm domainlerinizi görüp otomatik yenileme açabilirsiniz.</p><p>İyi bir domain markanızın dijital temelidir. Aceleyle seçilen isim sonradan değiştirmek 301 yönlendirme ve müşteri karışıklığı demektir.</p>
<!-- GENISLETME4 -->
<h2>Domain ve sosyal medya uyumu</h2><p>Domain seçerken Instagram, X ve TikTok kullanıcı adlarının da müsait olup olmadığını kontrol edin. Tutarlı marka kimliği müşterinin sizi her kanalda tanımasını kolaylaştırır. Namechk.com gibi araçlar toplu sorgu yapmanıza yardımcı olur.</p>
<!-- GENISLETME5 -->
<h2>Domain transfer checklist</h2><p>Transfer kilidi kapalı mı? Auth code alındı mı? 60 gün iç kuralı geçti mi? E-posta onayı yapıldı mı? DNS kayıtları yedeklendi mi? Transfer sırasında site kesintisiz kalır; sadece yönetim paneli değişir.</p>
<!-- GENISLETME6 -->
<p>Domain yönetimini hosting ile aynı panelde toplamak operasyonel hatayı azaltır. Yenileme tarihlerini kaçırmamak için otomatik yenileme ve güncel iletişim bilgisi kritiktir.</p><p>Hostvim müşteri panelinde domain ve hosting tek hesapta yönetilir; DNS değişikliği için ayrı panele girmeniz gerekmez.</p>
<!-- GENISLETME7 -->
<h2>Domain güvenlik ipuçları</h2><p>2FA açın, transfer kilidini unutmayın, registrar şifresini password manager'da saklayın. Phishing mail ile auth code isteyen sahte maillere dikkat edin.</p>
<!-- GENISLETME8 -->
<p>Domain dünyası geniş; bu rehber temelleri kapsar. İleri DNS konuları için ayrı yazılarımızı takip edebilirsiniz. Hostvim destek ekibi A ve MX kaydı konusunda yönlendirme yapar.</p>
<!-- GENISLETME9 -->
<p>Alan adı dijital varlığınızın tapusudur. Doğru registrar seçimi yıllarca sorunsuz yönetim demektir. Hostvim şeffaf fiyat ve Türkçe panel sunar. Domain ve hosting bir arada yönetildiğinde DNS hataları minimize olur. Yeni projenize bugün isim arayarak başlayın.</p>
<!-- FINALBOOST -->
<p>Domain kaydı ilk adımdır; hosting ve SSL ile tamamlayın. Projenizi bugün başlatmak için domain sorgulama sayfasını ziyaret edin.</p>
<!-- FINALBOOST10 -->
<p>Hostvim domain ve hosting paketlerini birlikte değerlendirin; DNS yönetimi tek panelde kolaylaşır. İlk domaininizi bugün kaydederek dijital yolculuğunuza başlayın.</p>
<!-- HOSTVIM_FOOTER_PARA -->
<p>Bu rehberde anlattığımız konular hosting ve domain dünyasının temel taşlarıdır. Teknoloji değişse de iyi altyapı, güvenlik ve destek her zaman önceliklidir. Hostvim olarak Türkiye ve global müşterilerimize şeffaf fiyatlandırma, modern veri merkezi altyapısı ve Türkçe teknik destek sunuyoruz. Sorularınız için iletişim sayfamızdan veya müşteri panelinden bize ulaşabilirsiniz. Blog yazılarımızı takip ederek sektörde güncel kalın; yeni başlayanlar ve profesyoneller için içerik üretmeye devam ediyoruz.</p>
<!-- HOSTVIM_FOOTER_PARA2 -->
<p>Hostvim blogunda hosting, domain, VPS ve güvenlik konularında düzenli rehberler yayınlıyoruz. Paket karşılaştırması yapmadan önce ihtiyaç listenizi çıkarın; destek ekibimiz size en uygun planı önermekten memnuniyet duyar.</p>
<h2>Sonuç</h2>
<p>Domain, dijital kimliğinizin temelidir. Doğru isim, güvenilir registrar ve düzenli yenileme uzun vadede sorun çıkarmaz. <a href="/domain">Hostvim'den domain sorgulayın</a>, hosting paketinizle birlikte yönetin.</p>
HTML,
];
