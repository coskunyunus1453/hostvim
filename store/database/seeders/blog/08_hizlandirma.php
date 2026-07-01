<?php

return [
    'title' => 'Web Sitesi Hızlandırma Rehberi',
    'slug' => 'web-sitesi-hizlandirma-rehberi',
    'category_slug' => 'hosting-rehberi',
    'image' => 'blog-site-hizlandirma.png',
    'days_ago' => 2,
    'excerpt' => 'Site hızı SEO ve dönüşümü doğrudan etkiler. Önbellek, CDN, görsel optimizasyonu ve hosting seçimiyle hızlandırma rehberi.',
    'meta_title' => 'Web Sitesi Hızlandırma Rehberi | SEO ve Performans',
    'meta_description' => 'Web sitesi nasıl hızlandırılır? Önbellek, CDN, görsel sıkıştırma, PHP optimizasyonu ve hosting etkisi — pratik hız rehberi.',
    'meta_keywords' => 'site hızlandırma, web sitesi hızı, core web vitals, sayfa hızı seo',
    'content' => <<<'HTML'
<h2>Site hızı neden önemli?</h2>
<p>Araştırmalar 3 saniyeden uzun yüklenen sayfalarda ziyaretçilerin yarısından fazlasının ayrıldığını gösteriyor. Google <strong>Core Web Vitals</strong> (LCP, INP, CLS) metriklerini sıralama faktörü olarak kullanır. Hızlı site hem SEO hem dönüşüm oranı hem de kullanıcı memnuniyeti demektir.</p>

<h2>Önce ölçün, sonra optimize edin</h2>
<p>Google PageSpeed Insights, GTmetrix ve WebPageTest ücretsiz analiz sunar. TTFB (Time To First Byte) yüksekse sorun hosting veya sunucu yapılandırmasındadır. LCP yüksekse büyük görseller veya yavaş CSS/JS kaynaklıdır.</p>

<h2>Hosting ve sunucu katmanı</h2>
<h3>Doğru paket seçimi</h3>
<p>Paylaşımlı hostingde sıkışan bir site, VPS'e geçince tek başına %40–60 hızlanabilir. PHP 8.2+, OPcache açık ve NVMe SSD disk temel beklentilerdir.</p>

<h3>Önbellek (cache)</h3>
<p>WordPress için LiteSpeed Cache, WP Rocket veya W3 Total Cache sayfa önbelleği oluşturur. Statik HTML sunulduğunda PHP ve veritabanı yükü düşer.</p>

<h3>CDN kullanımı</h3>
<p>Cloudflare, BunnyCDN gibi servisler statik dosyaları ziyaretçiye yakın sunuculardan iletir. Global kitleye hitap ediyorsanız CDN neredeyse zorunludur.</p>

<h2>Görsel optimizasyonu</h2>
<ul>
<li>WebP veya AVIF formatı kullanın (JPEG'e göre %30–50 küçük)</li>
<li>Gereksiz büyük dosyaları yeniden boyutlandırın</li>
<li>Lazy loading ile ekran dışı görselleri geciktirin</li>
<li><code>width</code> ve <code>height</code> attribute ile CLS'i önleyin</li>
</ul>

<h2>Kod ve veritabanı</h2>
<p>Gereksiz WordPress eklentilerini kaldırın. Autoloaded options tablosunu temizleyin. Veritabanı sorgularını Query Monitor ile profilleyin. Minify ve birleştirme (CSS/JS) dikkatli uygulanmalı; bazen HTTP/2 ile ayrı dosyalar daha hızlıdır.</p>

<h2>Core Web Vitals hedefleri</h2>
<table>
<thead><tr><th>Metrik</th><th>İyi</th><th>Açıklama</th></tr></thead>
<tbody>
<tr><td>LCP</td><td>&lt; 2.5 sn</td><td>Ana içeriğin yüklenme süresi</td></tr>
<tr><td>INP</td><td>&lt; 200 ms</td><td>Etkileşim yanıt süresi</td></tr>
<tr><td>CLS</td><td>&lt; 0.1</td><td>Görsel kayma (layout shift)</td></tr>
</tbody>
</table>

<h2>Hızlı kontrol listesi</h2>
<ol>
<li>HTTPS ve HTTP/2 aktif mi?</li>
<li>Gzip/Brotli sıkıştırma açık mı?</li>
<li>OPcache ve object cache kullanılıyor mu?</li>
<li>Kritik CSS inline, geri kalanı defer?</li>
<li>3. parti scriptler (chat widget, analytics) geciktirilmiş mi?</li>
</ol>

<h2>Mobil hız optimizasyonu</h2>
<p>Google önce mobil indeksleme (mobile-first) kullanır. Responsive tasarım, dokunma hedefleri için yeterli buton boyutu ve mobil ağda küçük payload kritiktir. AMP artık zorunlu değil; hızlı, temiz HTML+CSS yeterlidir.</p>

<h2>Üçüncü parti script yönetimi</h2>
<p>Canlı destek widget'ı, Facebook pikseli, heatmap araçları sayfayı şişirir. Mümkünse geciktirilmiş (defer/async) yükleyin veya sadece dönüşüm sayfalarında kullanın. Tag Manager ile tek noktadan yönetim kolaylık sağlar.</p>

<h2>Sunucu tarafı optimizasyon</h2>
<p>Nginx gzip/brotli, PHP OPcache, MySQL slow query log ve Redis object cache birlikte kullanıldığında WordPress sitelerinde belirgin hız artışı görülür. Hosting sağlayıcınız LiteSpeed kullanıyorsa LSCache eklentisi ücretsiz ve güçlü bir seçenektir.</p>

<h2>HTTP/2 ve HTTP/3 etkisi</h2>
<p>HTTP/2 çoklu isteği tek bağlantıda paralel taşır; CSS/JS dosya sayısı fazla sitelerde belirgin hızlanma sağlar. HTTP/3 (QUIC) paket kaybında daha dayanıklıdır. Hosting sağlayıcınızın bu protokolleri desteklediğinden emin olun; genelde nginx 1.18+ ve TLS 1.3 ile gelir.</p>

<h2>Veritabanı indeksleme</h2>
<p>Yavaş sorguların çoğu eksik indeksten kaynaklanır. WooCommerce'de <code>wp_postmeta</code> üzerinde meta_key indeksi kritiktir. Slow query log açıp 1 saniyeden uzun sorguları optimize edin. Gereksiz autoload=yes option'ları başlangıç yükünü artırır.</p>

<h2>Gerçek ölçüm örneği</h2>
<p>Önbellek öncesi LCP 4.2 saniye olan bir WordPress sitesinde LiteSpeed Cache + WebP dönüşümü sonrası LCP 1.8 saniyeye indi. Hosting paketi aynı kaldı; yani önce yazılım optimizasyonu, sonra hosting yükseltmesi mantıklı sıradır.</p>

<!-- GENISLETME_ISARETI -->
<h2>Önbellek katmanları derinlemesine</h2>
<p>Tarayıcı önbelleği (Cache-Control header), CDN edge cache, sayfa cache (HTML), object cache (Redis), opcode cache (OPcache) ve veritabanı sorgu cache farklı katmanlardır. Hepsi birlikte çalıştığında TTFB ve LCP dramatik iyileşir. Hangi katmanın eksik olduğunu waterfall grafiğinden okuyun.</p>
<h2>Görsel CDN ve lazy load</h2>
<p>Büyük hero görselleri LCP'yi bozar. Responsive srcset ile mobilde küçük dosya sunun. fetchpriority="high" sadece LCP adayı görsele verin. Diğer görsellerde loading="lazy" kullanın. Video için poster görseli ve preload dikkatli planlayın.</p><!-- GENISLETME2 -->
<h2>Hosting yükseltme kararı</h2>
<p>Tüm yazılım optimizasyonlarına rağmen TTFB 800 ms üzerindeyse hosting kaynakları darboğazdır. Bir üst pakete geçiş veya VPS'e taşıma gündeme gelir. Önce ölçün, sonra yükseltin; körlemesine pahalı paket almak gereksizdir.</p><!-- GENISLETME3 -->
<p>Hız optimizasyonu bir maratondur. Her ay PageSpeed skorunuza bakın; yeni eklenti performansı düşürebilir. Hosting tavanınızı belirler; yazılım optimizasyonu verimliliği gösterir.</p><p>Hostvim SSD ve güncel PHP ile sağlam bir taban sunar; üzerine önbellek ve görsel optimizasyonu inşa edin.</p>
<!-- GENISLETME4 -->
<h2>Ölçüm araçları karşılaştırması</h2><p>PageSpeed Insights lab verisi + CrUX field verisi sunar. GTmetrix farklı lokasyonlardan test yapar. WebPageTest waterfall ile darboğazı gösterir. Tek araç yerine ikisini birlikte kullanın; lab ile gerçek kullanıcı verisini ayırt edin.</p>
<!-- GENISLETME5 -->
<h2>WordPress hız eklentileri</h2><p>LiteSpeed Cache (LiteSpeed sunucuda), WP Rocket (ücretli, popüler), Autoptimize (CSS/JS minify) sık kullanılır. Birden fazla cache eklentisi aynı anda çakışır; birini seçin. Object cache için Redis Object Cache eklentisi + sunucuda Redis gerekir.</p>
<!-- GENISLETME6 -->
<p>Hız iyileştirmesi asla bitmez; yeni içerik ve özellikler ekledikçe tekrar ölçüm yapın. Performans bütçesi (performance budget) belirleyin: örneğin ana sayfa 1.5 MB altı kalsın.</p><p>Hostvim altyapısı üzerinde cache ve CDN ile birlikte Core Web Vitals hedeflerinize ulaşmak mümkündür.</p>
<!-- GENISLETME7 -->
<h2>Kritik CSS ve font yükleme</h2><p>Above-the-fold CSS inline, geri kalanı defer. Google Fonts yerine self-hosted font kullanımı GDPR ve hız açısından avantajlıdır. font-display: swap ile metin hemen görünür.</p>
<!-- GENISLETME8 -->
<p>Hız bir yolculuktur; bugün yaptığınız küçük iyileştirme yarın sıralamanıza yansır. Sabırlı olun ve veriye güvenin.</p>
<!-- GENISLETME9 -->
<p>Performans rekabet avantajıdır. Rakibinizden hızlı açılan site daha fazla müşteri tutar. Hostvim hızlı altyapı + sizin optimizasyonunuz = kazanma formülü.</p>
<!-- FINALBOOST -->
<p>Hız optimizasyonu yatırım getirisi sunar: daha hızlı site, daha fazla dönüşüm. Sonuçları Analytics ile ölçün.</p>
<!-- FINALBOOST10 -->
<p>Hostvim altyapısı ve bu rehberdeki adımlarla sitenizi hızlandırın. Performans iyileştirmesi sürekli bir süreçtir; bugün başlayın.</p>
<!-- HOSTVIM_FOOTER_PARA -->
<p>Bu rehberde anlattığımız konular hosting ve domain dünyasının temel taşlarıdır. Teknoloji değişse de iyi altyapı, güvenlik ve destek her zaman önceliklidir. Hostvim olarak Türkiye ve global müşterilerimize şeffaf fiyatlandırma, modern veri merkezi altyapısı ve Türkçe teknik destek sunuyoruz. Sorularınız için iletişim sayfamızdan veya müşteri panelinden bize ulaşabilirsiniz. Blog yazılarımızı takip ederek sektörde güncel kalın; yeni başlayanlar ve profesyoneller için içerik üretmeye devam ediyoruz.</p>
<!-- HOSTVIM_FOOTER_PARA2 -->
<p>Hostvim blogunda hosting, domain, VPS ve güvenlik konularında düzenli rehberler yayınlıyoruz. Paket karşılaştırması yapmadan önce ihtiyaç listenizi çıkarın; destek ekibimiz size en uygun planı önermekten memnuniyet duyar.</p>
<!-- PARA3 --><p>Doğru hosting ve domain seçimi dijital başarının temelidir. Hostvim müşteri paneli, şeffaf fiyatlandırma ve 7/24 destek ile yanınızdadır. <a href="/iletisim">İletişim</a> sayfasından sorularınızı iletebilirsiniz.</p><!-- PARA4 --><p>Hostvim ile güvenle yayına alın.</p><!-- PARA5 --><p>Detaylı bilgi ve paket karşılaştırması için Hostvim ana sayfasını ziyaret edebilirsiniz.</p><!-- PARA6 --><p>Hızlı site, mutlu ziyaretçi demektir.</p><h2>Sonuç</h2>
<p>Hız optimizasyonu tek seferlik değil, sürekli izleme gerektirir. Sağlam hosting altyapısı üzerine önbellek ve görsel optimizasyonu inşa edin. <a href="/web-hosting">Hostvim hosting</a> paketleri SSD ve modern PHP ile hızlı başlangıç sunar.</p>
HTML,
];
