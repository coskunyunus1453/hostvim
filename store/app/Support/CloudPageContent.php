<?php

namespace App\Support;

/**
 * /sunucu (bulut sunucu) sayfasinin varsayilan, SEO odakli icerigi.
 * Admin panelinden (Sayfa Icerikleri) duzenlenince site_settings.cloud_page
 * JSON kaydi bu varsayilanlarin yerine gecer.
 */
class CloudPageContent
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'hero' => [
                'badge' => 'NVMe · Tam Root · Anlık Kurulum',
                'title' => 'Yüksek Performanslı Bulut Sunucu',
                'subtitle' => 'Paylaşımsız işlemci, ayrılmış RAM ve kurumsal NVMe disklerle tam root erişimli bulut sunucular. İhtiyacınıza göre ölçeklenen, hızlı kurulan VPS/VDS çözümleri.',
                'primary_label' => 'Sunucuları İncele',
                'primary_url' => '#paketler',
                'secondary_label' => 'Bize Ulaşın',
                'secondary_url' => '/iletisim',
            ],
            'seo' => [
                'title' => 'Bulut Sunucu (VPS/VDS) — NVMe, Tam Root Erişim',
                'description' => 'HostVim bulut sunucu hizmetleri: paylaşımsız işlemci, ayrılmış RAM, kurumsal NVMe diskler ve tam root erişim. AlmaLinux, Ubuntu, Debian ve Windows Server desteği, hızlı kurulum ve ölçeklenebilir VPS/VDS paketleri.',
            ],
            'intro' => [
                'title' => 'İhtiyacınıza göre ölçeklenen sunucular',
                'text' => 'Geliştirme ortamlarından yüksek trafikli uygulamalara kadar her ihtiyaca uygun bulut sunucu paketleri. Tam root erişim, hızlı kurulum ve dilediğiniz an yükseltme imkânı.',
            ],
            'features' => [
                ['icon' => '🎧', 'title' => 'Teknik Destek', 'text' => '7/24 temel seviye teknik destek. Dilediğiniz zaman SLA paketiyle destek seviyenizi artırabilirsiniz.'],
                ['icon' => '📈', 'title' => 'Ölçeklenebilir', 'text' => 'İşiniz büyüdükçe CPU, RAM ve disk kaynaklarınızı kolayca yükseltin; ihtiyacınıza göre büyüyün.'],
                ['icon' => '🚚', 'title' => 'Sunucu Transfer', 'text' => 'Mevcut sunucunuzu HostVim’e taşıyalım. cPanel ve DirectAdmin panellerinde taşıma desteği sunuyoruz.'],
                ['icon' => '⚡', 'title' => '%99.9 Uptime', 'text' => 'Yedekli kurumsal altyapı ile bulut sunucunuz kesintisiz çalışır, iş sürekliliğiniz korunur.'],
                ['icon' => '🔑', 'title' => 'Tam Root Erişim', 'text' => 'Sunucunuzun tam yönetimi sizde. Dilediğiniz yazılımı kurun, dilediğiniz gibi yapılandırın.'],
                ['icon' => '💽', 'title' => 'Kurumsal NVMe Disk', 'text' => 'Yüksek hızlı NVMe SSD diskler ile uygulamalarınız verilere çok daha hızlı erişir.'],
                ['icon' => '⏱️', 'title' => 'Hızlı Kurulum', 'text' => 'Siparişinizin ardından sunucunuz kısa sürede hazırlanır ve erişim bilgileri size iletilir.'],
                ['icon' => '🛡️', 'title' => 'DDoS Koruması', 'text' => 'Layer 3/4 saldırı koruması ile sunucularınız ağ tabanlı saldırılara karşı korunur.'],
            ],
            'tech' => [
                ['title' => 'İşletim Sistemleri', 'text' => 'AlmaLinux, Rocky Linux, Ubuntu, Debian, CloudLinux ve Windows Server işletim sistemlerini destekliyoruz. Kurulumda dilediğinizi seçebilirsiniz.'],
                ['title' => 'Kontrol Panelleri', 'text' => 'cPanel ve DirectAdmin kontrol panellerini kurabilir veya panelsiz olarak da kullanabilirsiniz. Lisansını temin ettiğiniz dilediğiniz paneli de kurabilirsiniz.'],
                ['title' => 'Yazılımlar & Diller', 'text' => 'PHP, Python, Node.js gibi diller; Laravel, Symfony, React, Vue gibi framework’ler ve WordPress’ten Opencart’a birçok yazılımı özgürce barındırabilirsiniz.'],
            ],
            'details' => [
                [
                    'title' => 'Fiziksel Sunucuya Yakın Performans',
                    'body' => "HostVim bulut sunucu altyapısında paylaşımsız işlemci ve ayrılmış RAM modeli kullanır. Kaynaklarınız size özeldir; başka kullanıcıların yoğunluğundan etkilenmezsiniz.\n\nKurumsal NVMe SSD diskler sayesinde disk okuma/yazma performansı yüksektir; veritabanı ağırlıklı uygulamalar ve yoğun trafikli web siteleri için ideal bir ortam sunar.",
                ],
                [
                    'title' => 'Tam Root Erişim ve Özgürlük',
                    'body' => "Bulut sunucularınızda tam root (yönetici) erişimine sahip olursunuz. Dilediğiniz işletim sistemini, kontrol panelini ve yazılımları kurabilir, sunucunuzu ihtiyaçlarınıza göre uçtan uca yapılandırabilirsiniz.\n\nGeliştirme ortamları, uygulama sunucuları, oyun sunucuları veya özel web barındırma gibi paylaşımlı hostingin yetmediği tüm senaryolar için esnek bir altyapı elde edersiniz.",
                ],
                [
                    'title' => 'İhtiyacınıza Göre Ölçeklenin',
                    'body' => "Bulut sunucularınızın CPU, RAM ve disk kaynaklarını ihtiyaç duydukça yükseltebilirsiniz. İşiniz büyüdükçe altyapınız da sizinle birlikte büyür; baştan büyük bir yatırım yapmak zorunda kalmazsınız.\n\nYükseltme talebinizde, kaynaklarınız kısa bir bakım penceresinin ardından artırılır ve sunucunuz yeni kapasitesiyle çalışmaya devam eder.",
                ],
                [
                    'title' => 'Otomatik Panel Kurulumu',
                    'body' => "İsteğe bağlı olarak yeni sunucunuz kurulurken otomatik kontrol paneli kurulumu yapılabilir. Böylece sunucunuza erişir erişmez yönetim paneliniz hazır olur ve siteler oluşturmaya hemen başlayabilirsiniz.\n\nKurulum ve panel bilgileri e-posta ile iletilir; teknik kurulumla uğraşmadan üretime odaklanırsınız.",
                ],
            ],
            'faqs' => [
                ['q' => 'Bulut sunucu nedir?', 'a' => 'Bulut sunucu (VPS/VDS), bir fiziksel sunucunun kaynaklarının (işlemci, RAM, disk) sanallaştırma teknolojisiyle bölümlendirilerek size özel ayrılmış bir sunucu olarak sunulmasıdır. Paylaşımlı hostinge göre çok daha fazla kontrol ve performans sağlar.'],
                ['q' => 'VPS ile VDS arasındaki fark nedir?', 'a' => 'VPS’te kaynaklar genellikle paylaşımlı bir model üzerinden tahsis edilirken, VDS’te işlemci ve RAM gibi kaynaklar tamamen size ayrılır (paylaşımsız). Yüksek ve sabit performans gerektiren projeler için VDS, esnek ve ekonomik çözümler için VPS uygundur.'],
                ['q' => 'Sunucu kurulumu ne kadar sürer?', 'a' => 'Siparişiniz onaylandıktan sonra sunucunuz kısa süre içinde hazırlanır. Ek konfigürasyon gerektirmeyen standart kurulumlar genellikle çok hızlı tamamlanır; özel ihtiyaçlarda süre değişebilir.'],
                ['q' => 'Sunucu yönetimi bana mı ait?', 'a' => 'Bulut sunucular tam root erişimiyle sizin yönetiminizdedir. Yönetimsel destek için SLA paketi alabilirsiniz; bu durumda paket seviyenize göre teknik ekibimiz yönetim desteği sağlar.'],
                ['q' => 'Hangi işletim sistemlerini kurabilirim?', 'a' => 'AlmaLinux, Rocky Linux, CloudLinux, Ubuntu, Debian ve Windows Server gibi yaygın işletim sistemlerini destekliyoruz. Kurulum sırasında ihtiyacınıza uygun olanı seçebilirsiniz.'],
                ['q' => 'Hangi kontrol panellerini kullanabilirim?', 'a' => 'cPanel ve DirectAdmin kurulumlarını destekliyoruz. Bunların dışında lisansını kendiniz temin ettiğiniz dilediğiniz kontrol panelini de kurabilir veya panelsiz çalışabilirsiniz.'],
                ['q' => 'Sunucumu yedekliyor musunuz?', 'a' => 'Olası teknik sorunlara karşı sunucuları düzenli olarak yedekliyoruz. Yine de kendi verileriniz için ek snapshot/yedek hizmeti almanızı veya kendi yedeğinizi almanızı öneririz.'],
                ['q' => 'Ek IP adresi alabilir miyim?', 'a' => 'Evet. Sunucunuzun standart IP adresinin yanında, ihtiyaçlarınıza göre ek IP adresleri talep edebilirsiniz. Detaylar için destek ekibimizle iletişime geçebilirsiniz.'],
                ['q' => 'Sunucu özelliklerimi sonradan yükseltebilir miyim?', 'a' => 'Evet. CPU, RAM ve disk kaynaklarınızı dilediğiniz zaman üst bir pakete geçerek yükseltebilirsiniz. Yükseltme işlemi kısa bir bakım penceresinin ardından tamamlanır.'],
            ],
        ];
    }
}
