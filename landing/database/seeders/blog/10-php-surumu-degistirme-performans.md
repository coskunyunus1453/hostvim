## PHP sürümü değiştirme ve performans: hosting panelinde pratik rehber

PHP 7.4 resmi desteği sona erdi; PHP 8.x ise belirgin performans kazanımları ve modern dil özellikleri sunuyor. Ancak gerçek dünyada onlarca müşteri sitesi aynı sunucuda çalışır ve hepsi aynı anda PHP 8’e hazır değildir. Eski WordPress eklentisi, legacy Laravel 5 projesi veya özelleştirilmiş kod tabanı yükseltmeyi geciktirir. Çözüm: **site başına PHP sürümü** seçmek. Panelze bu ihtiyacı panel arayüzünden karşılar; bu yazıda sürüm değiştirme adımlarını, PHP 8 performans ayarlarını, OPcache’i ve PHP 7’den güvenli geçiş stratejisini anlatıyoruz.

### Neden site başına farklı PHP sürümü?

Paylaşımlı hosting geçmişinde “hesaptaki tüm siteler aynı PHP” kuralı yaygındı. VPS ve panel ile bu kısıt kalkar:

- Yeni müşteri projesi PHP 8.3 ile açılır
- Eski müşteri sitesi PHP 7.4’te kalır (geçici, güvenlik riski ile)
- Staging ortamında bir üst sürümle uyumluluk testi yapılır

Bu model ajanslar ve çoklu site yönetimi için kritiktir ([ajans rehberi](/blog/web-ajansi-coklu-site-yonetimi)). Toplu yükseltme yerine kontrollü, site site geçiş yapılır.

### PHP 8.x performans: rakamlar ve beklenti

PHP 8.0, JIT (Just-In-Time derleyici) ve tip sistemi iyileştirmeleri getirdi. PHP 8.1–8.3 ise JIT’i olgunlaştırdı ve çekirdek optimizasyonlar ekledi. Tipik web uygulamalarında (WordPress, Laravel, özel CMS) PHP 7.4’e kıyasla **%10–30 CPU süresi azalması** görülebilir; workload’a göre değişir. JIT her projede fayda sağlamaz; I/O ağırlıklı sitelerde OPcache ve veritabanı sorguları daha belirleyicidir.

PHP 8’in getirdiği dil değişiklikleri de önemli: named arguments, union types, `match` ifadesi, readonly properties (8.1+). Yeni kod yazarken bunlar verimliliği artırır; asıl geçiş zorluğu eski kodun uyumluluğundadır.

### Panelze’de PHP sürümü değiştirme

Genel akış (panel sürümüne göre küçük farklar olabilir; [panel rehberi](/docs/panel-guide) güncel ekranları gösterir):

1. Panelde siteyi seçin
2. PHP ayarları / sürüm bölümüne gidin
3. Hedef sürümü seçin (ör. 8.2, 8.3)
4. Kaydedin; PHP-FPM pool yeniden yüklenir

Değişiklik genelde saniyeler içinde yansır; önbellek temizliği gerekebilir. [Site ve domain](/docs/sites-and-domains) dokümantasyonunda site bazlı ayarların diğer örnekleri de vardır.

**Önemli:** Sürüm değiştirmeden önce yedek alın ([yedekleme](/docs/backups)). Kritik sitelerde önce staging’de aynı sürüme geçin.

### OPcache: ücretsiz performans katmanı

OPcache, derlenmiş PHP bytecode’unu bellekte tutar; her istekte dosyayı yeniden parse etmez. Üretimde mutlaka açık olmalı:

```ini
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
```

`validate_timestamps=0` üretimde maksimum hız sağlar; deploy sonrası PHP-FPM reload gerekir (`sudo systemctl reload php8.3-fpm`). Geliştirme ortamında `validate_timestamps=1` bırakın ki kod değişikliği anında yansısın.

Panelze site başına `php.ini` override destekliyorsa OPcache ayarlarını yoğun WordPress sitelerinde `memory_consumption=256` yapmayı düşünün. [WordPress hosting rehberi](/blog/wordpress-hosting-nasil-secilir-kurulum) cache eklentileri ile OPcache’in birlikte çalışmasını önerir.

### PHP-FPM pool ayarları

Site başına pool ile kaynak izolasyonu sağlanır. Temel parametreler:

```ini
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 6
pm.max_requests = 500
```

`pm.max_children` RAM’e göre hesaplanır: ortalama PHP süreç boyutu × max_children ≈ ayrılan bellek. 4 GB RAM’li VPS’te on site agresif `max_children` ile swap’a düşebilirsiniz. [Sunucu kurulum checklist](/blog/ubuntu-22-04-web-sunucu-kurulum-checklist) RAM ve swap planlamasını hatırlatır.

### PHP 7’den 8’e geçiş: uyumluluk kontrolü

**1. Resmi uyumluluk araçları**

```bash
composer require --dev phpcompatibility/php-compatibility
# veya PHP_CodeSniffer ile PHPCompatibility standardı
```

**2. WordPress**

- Çekirdek, tema ve eklentilerin PHP 8 uyumlu sürümlerini kontrol edin
- Staging’de `WP_DEBUG` açık test
- Sorunlu eklentiyi güncelleyin veya alternatif bulun

**3. Laravel**

- `composer.json` içinde `"php": "^8.2"` gereksinimi
- Laravel sürümünün hedef PHP’yi desteklediğini doğrulayın (Laravel 10+ PHP 8.1+)
- `php artisan config:clear` ve cache komutları geçiş sonrası

**4. Kırılan özellikler (özet)**

- `each()` kaldırıldı
- String offset süslü parantez `{}` kaldırıldı
- Bazı implicit conversion’lar uyarı/exception üretir
- Eski mysql_* fonksiyonları zaten yok; mysqli/PDO kullanın

Geçişi tek gecede tüm sitelere uygulamayın. Haftalık plan: 2–3 site staging → production.

### JIT: açmalı mıyım?

PHP 8’de `opcache.jit_buffer_size` ve `opcache.jit` ayarları JIT’i kontrol eder. Web istekleri için JIT kazancı çoğu zaman OPcache kadar dramatik değildir; CPU-bound hesaplama yoğun kodda faydalı olabilir. Başlangıç önerisi: önce OPcache’i optimize edin; JIT’i staging’de `128M` buffer ile test edin, production’a ölçümle taşıyın.

### Sürüm değişince 500 hatası

Yaygın nedenler:

- Eski syntax (PHP 7 kodu 8’de fatal error)
- Eksik PHP uzantısı (yeni sürümde `php8.3-xml` kurulu değil)
- `open_basedir` veya path kısıtlaması
- Opcache eski bytecode tutuyor — FPM reload

Hata logları: `/var/log/nginx/error.log`, site `storage/logs/laravel.log`, PHP-FPM log. Panelde genelde log görüntüleyici vardır.

### Güvenlik: eski PHP sürümünü çalışır tutmak

PHP 7.4 artık güvenlik yamaları almıyor. Müşteriye “henüz hazır değil” diye 7.4’te bırakmak kısa vadeli çözüm; orta vadede sözleşmeye geçiş tarihi koyun. Mümkünse 8.1 minimum hedefleyin; 8.2 veya 8.3 yeni projeler için ideal.

Sunucuda kullanılmayan PHP sürümlerini kaldırmak saldırı yüzeyini küçültür; ancak hâlâ o sürümü kullanan site varsa paketi tutun.

### Performans ölçümü

Geçiş öncesi ve sonrası:

- TTFB (Time To First Byte) — tarayıcı veya `curl -w`
- `ab` veya `wrk` ile basit yük testi (düşük trafikli saatte)
- Query monitor (WordPress) veya Laravel Debugbar (staging)

Subjektif “daha hızlı” yetmez; müşteriye basit metrik göstermek güven oluşturur.

### Panelze ve çoklu sürüm mimarisi

Panelze, sistemde birden fazla PHP-FPM sürümünü paralel çalıştırır (8.1, 8.2, 8.3). Her site Nginx config’inde doğru socket’e yönlendirilir (`unix:/run/php/php8.3-fpm.sock`). Bu mimari [sunucu kurulumu](/docs/server-setup) sırasında PPA ile eklenen paketlere dayanır.

Yeni sunucu kurarken en az iki sürüm kurun: biri legacy, biri güncel. [Kurulum](/setup) script’i bunu otomatikleştirebilir.

### Checklist: PHP yükseltme günü

| Adım | Yapıldı |
|------|---------|
| Tam yedek (dosya + DB) | ☐ |
| Staging’de hedef sürüm testi | ☐ |
| Uyumluluk taraması (composer/phpcs) | ☐ |
| Eklenti/tema güncellemeleri | ☐ |
| Production sürüm değişimi | ☐ |
| OPcache / FPM reload | ☐ |
| Smoke test (giriş, ödeme, form) | ☐ |
| İzleme 24 saat | ☐ |

### İlgili konular

- Deploy sonrası sürüm uyumu: [Git deploy](/blog/git-deploy-ile-canli-site-guncelleme)
- Veritabanı charset ve PHP: [veritabanları](/docs/databases)
- SSL ve HTTP/2: [SSL rehberi](/docs/ssl-dns-email)

PHP sürümü yönetimi, modern hosting operasyonunun standart parçasıdır. Panelze ile site başına birkaç tıklamayla sürüm değiştirir, OPcache ve FPM ile performansı ayarlarsınız; asıl iş planlı uyumluluk testi ve müşteri iletişimindedir. PHP 8’e geçiş hem hız hem güvenlik getirir; legacy siteleri ise staging ve yedekle disipliniyle taşıyın.

Sorular için [SSS](/#faq); plan karşılaştırması için [fiyatlandırma](/pricing). Henüz panel kurmadıysanız [başlangıç](/docs/getting-started) ile ilk adımı atın.
