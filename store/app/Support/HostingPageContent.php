<?php

namespace App\Support;

/**
 * /hosting sayfasinin varsayilan, SEO odakli icerigi.
 * Admin panelinden (Sayfa Icerikleri) duzenlenince site_settings.hosting_page
 * JSON kaydi bu varsayilanlarin yerine gecer.
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
                'badge' => 'cPanel · LiteSpeed · NVMe',
                'title' => 'Hızlı ve Güvenli Web Hosting',
                'subtitle' => 'Yüksek performanslı LiteSpeed sunucular, NVMe diskler ve ücretsiz SSL ile web sitenizi saniyeler içinde yayına alın. cPanel kontrol paneli ve 7/24 Türkçe destek standarttır.',
                'primary_label' => 'Paketleri İncele',
                'primary_url' => '#paketler',
                'secondary_label' => 'Alan Adı Sorgula',
                'secondary_url' => '/domain',
            ],
            'seo' => [
                'title' => 'Web Hosting — cPanel, LiteSpeed & NVMe Linux Hosting',
                'description' => 'HostVim web hosting paketleri: LiteSpeed web sunucusu, NVMe diskler, CloudLinux, cPanel ve ücretsiz SSL. WordPress ve PHP siteleri için hızlı, güvenli ve uygun fiyatlı Linux hosting. Ücretsiz taşıma ve 7/24 destek.',
            ],
            'intro' => [
                'title' => 'İhtiyacınıza uygun hosting paketi',
                'text' => 'Kişisel bloglardan kurumsal e-ticarete kadar her ölçekte web sitesi için optimize edilmiş paketler. Tüm paketlerde cPanel, LiteSpeed, ücretsiz SSL ve günlük yedekleme.',
            ],
            'features' => [
                ['icon' => '🔒', 'title' => 'Ücretsiz SSL', 'text' => "Let's Encrypt ile tüm web siteleriniz için otomatik yenilenen ücretsiz SSL sertifikası."],
                ['icon' => '🚚', 'title' => 'Ücretsiz Taşıma', 'text' => 'cPanel kullanan mevcut hosting firmanızdan sitenizi ücretsiz ve sorunsuz taşıyoruz.'],
                ['icon' => '💾', 'title' => 'Otomatik Yedekleme', 'text' => 'Web sitenizi düzenli olarak yedekleyerek olası veri kayıplarına karşı koruyoruz.'],
                ['icon' => '⚡', 'title' => '%99.9 Uptime', 'text' => 'Kurumsal altyapı ve yedekli sistemlerle web siteniz kesintisiz çalışır.'],
                ['icon' => '🚀', 'title' => 'LiteSpeed + LSCache', 'text' => 'Apache ve Nginx’e göre çok daha hızlı LiteSpeed web sunucusu ve WordPress için LSCache.'],
                ['icon' => '🧩', 'title' => 'Tek Tıkla Kurulum', 'text' => 'WordPress, Opencart, Joomla ve onlarca yazılımı tek tıkla saniyeler içinde kurun.'],
                ['icon' => '🎧', 'title' => '7/24 Destek', 'text' => 'Gerçek insanlardan oluşan teknik destek ekibimiz haftanın her günü yanınızda.'],
                ['icon' => '↩️', 'title' => 'İade Garantisi', 'text' => 'Hosting hizmetinden memnun kalmazsanız ilk 7 gün içinde koşulsuz iade.'],
            ],
            'tech' => [
                ['title' => 'cPanel Kontrol Paneli', 'text' => 'Dünyada en çok tercih edilen hosting kontrol paneli cPanel ile dosya, veritabanı, e-posta ve alan adı yönetimini Türkçe arayüzden kolayca yapın.'],
                ['title' => 'LiteSpeed Web Server', 'text' => 'LSAPI ve LSCache desteğiyle PHP tabanlı sitelerde Apache ve Nginx’e göre belirgin biçimde daha yüksek hız ve daha düşük kaynak tüketimi.'],
                ['title' => 'CloudLinux & CageFS', 'text' => 'Her hesap CloudLinux ile izole edilir; CageFS sayesinde diğer kullanıcıların sitelerinden etkilenmezsiniz. Kaynaklar adil paylaştırılır.'],
                ['title' => 'PHP 5.6 – 8.3', 'text' => 'Eski yazılımlardan en güncel sürümlere kadar geniş PHP desteği. Her alan adı için ayrı PHP sürümü seçebilirsiniz.'],
                ['title' => 'Imunify360 Güvenlik', 'text' => 'WAF, antivirüs ve proaktif koruma sağlayan Imunify360 ile siteleriniz zararlı yazılımlara ve saldırılara karşı korunur.'],
                ['title' => 'Ücretsiz SSL Sertifikası', 'text' => "Let's Encrypt SSL sertifikaları otomatik kurulur ve 90 günde bir otomatik yenilenir; siteleriniz her zaman HTTPS ile korunur."],
            ],
            'details' => [
                [
                    'title' => 'LiteSpeed ile Yüksek PHP Performansı',
                    'body' => "HostVim, web hosting sunucularında Apache + PHP-FPM veya Nginx + PHP-FPM ikilileri yerine LiteSpeed Web Server’ı tercih eder. LSAPI ve LSCache desteğiyle PHP işlemleri çok daha optimize biçimde işlenir, işlemci ve RAM kaynakları verimli kullanılır.\n\nÖzellikle WordPress, Opencart ve diğer PHP tabanlı sitelerde LiteSpeed; daha hızlı yükleme süreleri, daha yüksek PageSpeed skorları ve daha iyi kullanıcı deneyimi sağlar. LSCache eklentisiyle WordPress siteleriniz ek bir maliyet olmadan ciddi şekilde hızlanır.",
                ],
                [
                    'title' => 'cPanel ile Kontrol Sizde',
                    'body' => "Web hosting paketlerinde dünyanın en yaygın kontrol paneli cPanel’i kullanıyoruz. Dosya yöneticisi, veritabanları, e-posta hesapları, yedekleme ve alan adı yönetimi gibi tüm işlemleri tek bir Türkçe arayüzden kolayca gerçekleştirebilirsiniz.\n\nDerin Linux sunucu bilgisine ihtiyaç duymadan sitenizi yönetebilir, FTP ve Git ile dosyalarınızı yükleyebilir, PhpMyAdmin üzerinden veritabanlarınızı düzenleyebilirsiniz.",
                ],
                [
                    'title' => 'Birkaç Tıkla Hızlı Kurulum',
                    'body' => "cPanel üzerindeki tek tıkla kurulum aracıyla WordPress, Opencart, Joomla, PrestaShop ve onlarca popüler yazılımı bir dakikadan kısa sürede kurabilirsiniz.\n\nHerhangi bir teknik bilgiye ihtiyaç duymadan, yalnızca kullanıcı adı ve şifre belirleyerek sitenizi yönetime hazır hale getirir, zamandan tasarruf edersiniz.",
                ],
                [
                    'title' => 'Ücretsiz ve Sorunsuz Website Taşıma',
                    'body' => "HostVim teknik ekibi, mevcut hosting firmanızdaki web sitenizi ve e-postalarınızı ücretsiz olarak taşır. cPanel kullanıyorsanız, tüm konfigürasyonlarınızla birlikte eksiksiz biçimde aktarım yapılır.\n\nFarklı bir kontrol paneli kullanıyorsanız, destek ekibimizle iletişime geçerek taşıma süreci hakkında net bilgi alabilirsiniz. Geçiş sürecinde sitenizin kesintiye uğramaması için planlı çalışırız.",
                ],
            ],
            'faqs' => [
                ['q' => 'Web hosting nedir?', 'a' => 'Web hosting, web sitenizi oluşturan dosyaların (HTML, CSS, görseller, veritabanı vb.) depolandığı ve ziyaretçilerin 7/24 erişebilmesini sağlayan barındırma hizmetidir. Sitenizin internette yayında ve erişilebilir olmasını sağlar.'],
                ['q' => 'Hangi hosting paketini seçmeliyim?', 'a' => 'Paket seçimi; barındıracağınız site sayısı, disk alanı, beklenen ziyaretçi trafiği ile yazılımınızın ihtiyaç duyduğu işlemci ve RAM miktarına göre değişir. Başlangıç ve orta ölçekli WordPress/PHP siteleri için giriş paketleri yeterlidir; e-ticaret ve yoğun trafikli siteler için üst paketleri öneririz. Emin değilseniz destek ekibimiz size yardımcı olur.'],
                ['q' => 'Sitemi HostVim’e nasıl taşırım?', 'a' => 'Mevcut firmanızda cPanel kullanıyorsanız, web sitenizi ve e-postalarınızı ücretsiz olarak biz taşırız. Farklı bir panel kullanıyorsanız manuel taşıma için destek ekibimizle iletişime geçebilirsiniz. Çoğu durumda taşıma süreci kesintisiz tamamlanır.'],
                ['q' => 'Hosting paketim yetersiz gelirse ne olur?', 'a' => 'Paketiniz yetersiz kalırsa, daha önce ödediğiniz ücret yanmaz. Kalan süre hesaplanarak yalnızca fark ücretini ödeyip dilediğiniz üst pakete kolayca yükseltebilirsiniz.'],
                ['q' => 'Ücretsiz SSL sertifikası veriyor musunuz?', 'a' => "Evet. Tüm hosting paketlerinde Let's Encrypt ücretsiz SSL sertifikası sunuyoruz. Sertifikalar otomatik kurulur ve 90 günde bir otomatik yenilenir; siteleriniz her zaman HTTPS ile güvende olur."],
                ['q' => 'Hangi PHP sürümlerini destekliyorsunuz?', 'a' => 'PHP 5.6’dan en güncel PHP sürümlerine kadar geniş bir destek sunuyoruz. cPanel üzerinden her alan adı veya alt alan adı için ayrı PHP sürümü seçebilir, eski ve yeni yazılımları aynı pakette barındırabilirsiniz.'],
                ['q' => 'Web sitemi yedekliyor musunuz?', 'a' => 'Hosting sunucularımız düzenli olarak yedeklenir. Yine de en iyi uygulama olarak, kritik verileriniz için kendi yedeğinizi de almanızı öneririz. cPanel üzerinden dilediğiniz zaman manuel yedek alabilirsiniz.'],
                ['q' => 'SSH (komut satırı) erişimi var mı?', 'a' => 'Paylaşımlı web hosting hizmetlerinde güvenlik nedeniyle SSH erişimi sınırlıdır. Tam root erişimi ve komut satırına ihtiyacınız varsa bulut sunucu (VPS) hizmetlerimizi tercih etmenizi öneririz.'],
                ['q' => 'İade garantiniz var mı?', 'a' => 'Hosting hizmetlerinde 7 gün koşulsuz iade garantisi sunuyoruz. Hizmetten memnun kalmazsanız ilk 7 gün içinde ücret iadenizi alabilirsiniz. Alan adı kayıtları iade kapsamı dışındadır.'],
            ],
        ];
    }
}
