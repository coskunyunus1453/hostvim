<?php

namespace App\Support;

/**
 * /web-hosting sayfasının varsayılan, SEO odaklı içeriği.
 * Hostvim altyapısı: Panelze panel + PanelKafes + Nginx + PHP-FPM.
 */
class HostingPageContent
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'hero' => [
                'badge' => 'Panelze · PanelKafes · Nginx · NVMe',
                'title' => 'Hızlı ve Güvenli Web Hosting',
                'subtitle' => 'Kendi geliştirdiğimiz Panelze kontrol paneli ve PanelKafes izolasyonu ile sitenizi güvenle barındırın. Nginx, PHP 8.x, NVMe disk ve otomatik Let\'s Encrypt SSL standarttır.',
                'primary_label' => 'Paketleri İncele',
                'primary_url' => '#paketler',
                'secondary_label' => 'Alan Adı Sorgula',
                'secondary_url' => '/domain',
            ],
            'seo' => [
                'title' => 'Web Hosting — Panelze Panel & PanelKafes Altyapısı',
                'description' => 'Hostvim web hosting: Panelze panel, PanelKafes site izolasyonu, Nginx, PHP 8.x FPM, NVMe SSD ve ücretsiz SSL. WordPress ve PHP siteleri için hızlı, güvenli Linux hosting.',
            ],
            'platform' => [
                ['icon' => '🎛️', 'title' => 'Panelze Panel', 'text' => 'Kendi hosting kontrol panelimiz — site, domain, SSL, veritabanı ve dosya yönetimi tek arayüzde.'],
                ['icon' => '🔐', 'title' => 'PanelKafes', 'text' => 'Her site ayrı Linux kullanıcısı ve PHP-FPM havuzu ile izole; paket bazlı CPU/RAM limiti.'],
                ['icon' => '⚡', 'title' => 'Nginx + PHP-FPM', 'text' => 'Yüksek performanslı web sunucusu ve site başına optimize PHP-FPM yapılandırması.'],
                ['icon' => '💾', 'title' => 'NVMe SSD', 'text' => 'Kurumsal NVMe diskler ile düşük gecikme ve hızlı dosya erişimi.'],
            ],
            'intro' => [
                'title' => 'İhtiyacınıza uygun hosting paketi',
                'text' => 'Kişisel blogdan kurumsal e-ticarete kadar her ölçek için Panelze altyapısıyla optimize paketler. Tüm paketlerde Panelze panel, PanelKafes izolasyon, ücretsiz SSL ve otomatik yedekleme.',
            ],
            'features' => [
                ['icon' => '🎛️', 'title' => 'Panelze Panel', 'text' => 'Dosya yöneticisi, veritabanı, e-posta, SSL ve domain yönetimi — hepsi kendi panelimizde, Türkçe arayüz.'],
                ['icon' => '🔐', 'title' => 'PanelKafes İzolasyon', 'text' => 'Site başına ayrı sistem kullanıcısı ve kaynak limiti; komşu sitelerden etkilenmezsiniz.'],
                ['icon' => '🔒', 'title' => 'Ücretsiz SSL', 'text' => "Let's Encrypt ile otomatik kurulum ve yenileme; HTTPS her zaman aktif."],
                ['icon' => '🚚', 'title' => 'Ücretsiz Taşıma', 'text' => 'Mevcut hosting firmanızdan sitenizi ve e-postalarınızı ücretsiz taşıyoruz.'],
                ['icon' => '💾', 'title' => 'Otomatik Yedekleme', 'text' => 'Düzenli sunucu yedekleri; kritik verileriniz için panelden manuel yedek de alabilirsiniz.'],
                ['icon' => '⚡', 'title' => '%99.9 Uptime', 'text' => 'Yedekli altyapı ve izleme ile siteniz kesintisiz çalışır.'],
                ['icon' => '🧩', 'title' => 'Hızlı Kurulum', 'text' => 'WordPress ve popüler CMS\'leri Panelze üzerinden dakikalar içinde kurun.'],
                ['icon' => '🎧', 'title' => '7/24 Destek', 'text' => 'Türkçe teknik destek ekibimiz haftanın her günü yanınızda.'],
            ],
            'tech' => [
                ['title' => 'Panelze Hosting Paneli', 'text' => 'Hostvim ve Panelze ekosisteminin parçası olan özel kontrol paneli. Site oluşturma, dosya yönetimi, MySQL veritabanları, e-posta hesapları, SSL ve DNS işlemlerini tek panelden yönetin.'],
                ['title' => 'PanelKafes (Site Kafesi)', 'text' => 'Her domain ayrı Linux kullanıcısında ve ayrı PHP-FPM soketinde çalışır. Hosting paketinizdeki CPU ve RAM limitleri cgroup ile uygulanır; komşu sitelerden tam izolasyon.'],
                ['title' => 'Nginx Web Sunucusu', 'text' => 'Üretim ortamlarımızda Nginx reverse proxy ve statik dosya sunumu. HTTP/2, gzip/brotli sıkıştırma ve güvenli TLS yapılandırması.'],
                ['title' => 'PHP 8.x FPM', 'text' => 'Güncel PHP sürümleri (8.2/8.3), OPcache açık. Site başına PHP-FPM pool ile stabil performans.'],
                ['title' => 'MariaDB / MySQL', 'text' => 'Her hosting hesabı için ayrı veritabanı oluşturma; phpMyAdmin SSO ile güvenli erişim.'],
                ['title' => 'Let\'s Encrypt SSL', 'text' => 'Yeni site açıldığında SSL otomatik tanımlanır ve yenilenir; manuel işlem gerekmez.'],
            ],
            'details' => [
                [
                    'title' => 'Panelze ile site yönetimi',
                    'body' => "Panelze, Hostvim'in kendi geliştirdiği modern hosting kontrol panelidir. Altyapıyı uçtan uca kendimiz yönetiyoruz; üçüncü parti panel lisansına ihtiyaç duymazsınız.\n\nPanelze üzerinden yeni site oluşturabilir, dosyalarınızı yönetebilir, MySQL veritabanı açabilir, e-posta hesabı tanımlayabilir ve SSL sertifikanızı tek tıkla aktif edebilirsiniz. Müşteri paneliniz (hostvim.com) ile hosting paneliniz entegre çalışır.",
                ],
                [
                    'title' => 'PanelKafes: gerçek site izolasyonu',
                    'body' => "PanelKafes, paylaşımlı hostingde her müşteri sitesini ayrı bir \"kafes\" içinde çalıştıran izolasyon sistemimizdir. Her site kendi Linux kullanıcısına, kendi web kök dizinine ve kendi PHP-FPM havuzuna sahiptir.\n\nHosting paketinizde tanımlı CPU ve RAM limitleri cgroup ile uygulanır; bir sitenin kaynak tüketimi diğer müşterileri etkilemez. Bu mimari güvenlik ve performans açısından klasik paylaşımlı hostingden belirgin şekilde daha güvenlidir.",
                ],
                [
                    'title' => 'Nginx ve PHP performansı',
                    'body' => "Web hosting sunucularımızda Nginx ve PHP-FPM kombinasyonu kullanıyoruz. OPcache ile PHP bytecode önbellekleme, NVMe SSD ile düşük disk gecikmesi ve site başına optimize edilmiş FPM pool ayarları sayesinde WordPress ve PHP uygulamaları hızlı yanıt verir.\n\nStatik dosyalar doğrudan Nginx üzerinden sunulur; dinamik istekler izole PHP-FPM süreçlerine yönlendirilir.",
                ],
                [
                    'title' => 'Ücretsiz site taşıma',
                    'body' => "Mevcut hosting sağlayıcınızdaki web sitenizi ve e-postalarınızı Hostvim'e ücretsiz taşıyoruz. Dosya ve veritabanı aktarımını teknik ekibimiz planlı şekilde gerçekleştirir.\n\nTaşıma sonrası Panelze panelinizde site aktif olur, SSL otomatik tanımlanır ve DNS yönlendirmesiyle yayına geçersiniz.",
                ],
            ],
            'faqs' => [
                ['q' => 'Web hosting nedir?', 'a' => 'Web hosting, sitenizin dosyalarının 7/24 erişilebilir bir sunucuda barındırılmasıdır. Hostvim\'de bu hizmet Panelze panel ve PanelKafes izolasyonu ile sunulur.'],
                ['q' => 'Panelze panel nedir?', 'a' => 'Panelze, Hostvim\'in kendi geliştirdiği hosting kontrol panelidir. Site, domain, SSL, veritabanı ve e-posta yönetimini tek arayüzden yaparsınız.'],
                ['q' => 'PanelKafes ne işe yarar?', 'a' => 'PanelKafes, her sitenin ayrı Linux kullanıcısı ve PHP-FPM havuzunda çalışmasını sağlar. Paketinizdeki CPU/RAM limiti uygulanır; komşu sitelerden izole olursunuz.'],
                ['q' => 'Hangi hosting paketini seçmeliyim?', 'a' => 'Site sayısı, disk ihtiyacı ve trafiğe göre Başlangıç, Profesyonel veya Kurumsal paketi seçin. Emin değilseniz destek ekibimiz yardımcı olur.'],
                ['q' => 'Sitemi Hostvim\'e nasıl taşırım?', 'a' => 'Destek talebi açmanız yeterli. Dosya ve veritabanı aktarımını biz yaparız; çoğu taşıma kesintisiz tamamlanır.'],
                ['q' => 'Ücretsiz SSL var mı?', 'a' => "Evet. Tüm paketlerde Let's Encrypt SSL otomatik kurulur ve yenilenir."],
                ['q' => 'Hangi PHP sürümlerini destekliyorsunuz?', 'a' => 'PHP 8.2 ve 8.3 aktif olarak desteklenir. OPcache varsayılan olarak açıktır.'],
                ['q' => 'SSH erişimi var mı?', 'a' => 'Paylaşımlı hostingde güvenlik nedeniyle SSH sınırlıdır. Tam root erişim için bulut sunucu (VPS) paketlerimizi tercih edin.'],
                ['q' => 'İade garantiniz var mı?', 'a' => 'Hosting hizmetlerinde 7 gün koşulsuz iade garantisi sunuyoruz. Alan adı kayıtları iade kapsamı dışındadır.'],
            ],
        ];
    }
}
