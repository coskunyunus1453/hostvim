<?php

namespace App\Support;

/**
 * /bulut-sunucu sayfasının varsayılan, SEO odaklı içeriği.
 * Panelze engine ile VPS/VDS provizyon; isteğe bağlı Panelze panel.
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
                'badge' => 'NVMe · Root Erişim · Panelze Engine',
                'title' => 'Yüksek Performanslı Bulut Sunucu',
                'subtitle' => 'Paylaşımsız işlemci, ayrılmış RAM ve kurumsal NVMe disklerle tam root erişimli VPS/VDS. İsteğe bağlı Panelze hosting paneli ile dakikalar içinde yayına alın.',
                'primary_label' => 'Sunucuları İncele',
                'primary_url' => '#paketler',
                'secondary_label' => 'Bize Ulaşın',
                'secondary_url' => '/iletisim',
            ],
            'seo' => [
                'title' => 'Bulut Sunucu (VPS/VDS) — NVMe, Root, Panelze',
                'description' => 'Hostvim bulut sunucu: paylaşımsız kaynaklar, NVMe SSD, tam root erişim, AlmaLinux/Ubuntu/Debian. İsteğe bağlı Panelze panel kurulumu ve ölçeklenebilir VPS paketleri.',
            ],
            'platform' => [
                ['icon' => '🔑', 'title' => 'Tam Root', 'text' => 'Sunucunuzun tam yönetimi sizde; dilediğiniz yazılımı kurun.'],
                ['icon' => '🎛️', 'title' => 'Panelze Panel', 'text' => 'Siparişte isteğe bağlı — hosting paneli otomatik kurulur.'],
                ['icon' => '💽', 'title' => 'NVMe SSD', 'text' => 'Yüksek IOPS ile veritabanı ve uygulama performansı.'],
                ['icon' => '🛡️', 'title' => 'DDoS Koruması', 'text' => 'Ağ katmanında temel saldırı filtreleme.'],
            ],
            'intro' => [
                'title' => 'İhtiyacınıza göre ölçeklenen sunucular',
                'text' => 'Geliştirme ortamından yüksek trafikli uygulamalara kadar esnek VPS/VDS paketleri. Root erişim, hızlı kurulum ve dilediğiniz an kaynak yükseltme.',
            ],
            'features' => [
                ['icon' => '🔑', 'title' => 'Tam Root Erişim', 'text' => 'SSH ile tam kontrol; Docker, Node.js, özel API — özgürce yapılandırın.'],
                ['icon' => '🎛️', 'title' => 'Opsiyonel Panelze', 'text' => 'Sipariş sırasında Panelze hosting paneli kurulumunu seçebilirsiniz.'],
                ['icon' => '💽', 'title' => 'Kurumsal NVMe', 'text' => 'NVMe SSD diskler ile yüksek okuma/yazma hızı.'],
                ['icon' => '📈', 'title' => 'Ölçeklenebilir', 'text' => 'CPU, RAM ve disk kaynaklarını ihtiyaç halinde yükseltin.'],
                ['icon' => '⚡', 'title' => '%99.9 Uptime', 'text' => 'Yedekli veri merkezi altyapısı.'],
                ['icon' => '🛡️', 'title' => 'DDoS Koruması', 'text' => 'Layer 3/4 ağ koruması ile temel saldırı filtreleme.'],
                ['icon' => '⏱️', 'title' => 'Hızlı Kurulum', 'text' => 'Sipariş onayından kısa süre sonra sunucu erişiminiz hazır.'],
                ['icon' => '🎧', 'title' => 'Teknik Destek', 'text' => '7/24 temel destek; SLA paketleriyle yönetim desteği.'],
            ],
            'tech' => [
                ['title' => 'Panelze Engine', 'text' => 'Sunucu provizyon, nginx vhost, SSL ve PanelKafes yapılandırması Panelze engine ile otomatik yönetilir.'],
                ['title' => 'İşletim Sistemleri', 'text' => 'AlmaLinux, Rocky Linux, Ubuntu ve Debian. Kurulum sırasında seçim yapılır.'],
                ['title' => 'Panelze Panel (Opsiyonel)', 'text' => 'Siparişte "Panelze hosting paneli kurulsun" seçeneği ile sunucuya panel otomatik kurulur; panelsiz root sunucu da tercih edilebilir.'],
                ['title' => 'Ağ ve Güvenlik', 'text' => 'Dedicated IPv4, opsiyonel ek IP, firewall yapılandırması ve DDoS filtreleme.'],
            ],
            'details' => [
                [
                    'title' => 'Paylaşımsız kaynak modeli',
                    'body' => "Bulut sunucularımızda CPU ve RAM size ayrılmıştır; paylaşımlı hostingdeki komşu etkisi yaşanmaz. NVMe SSD diskler veritabanı ve yoğun I/O gerektiren uygulamalar için idealdir.\n\nVPS esnek ve ekonomik; VDS tamamen ayrılmış kaynaklarla sabit performans sunar.",
                ],
                [
                    'title' => 'Panelze ile yönetim kolaylığı',
                    'body' => "Root sunucu yönetimi teknik bilgi gerektirir. Sipariş sırasında Panelze hosting paneli kurulumunu seçerseniz, sunucunuz teslim edildiğinde site oluşturma, SSL, veritabanı ve dosya yönetimi hazır olur.\n\nPanelsiz tercih ederseniz sunucu yalnızca işletim sistemi ile gelir; tam özgürlük sizde kalır.",
                ],
                [
                    'title' => 'Ölçekleme ve yükseltme',
                    'body' => "Trafik ve iş yükünüz arttıkça CPU, RAM ve disk kapasitesini üst pakete geçerek yükseltebilirsiniz. Yükseltme kısa bir bakım penceresinin ardından tamamlanır.\n\nBüyüme planınızı önceden paylaşırsanız doğru paket önerisi sunarız.",
                ],
                [
                    'title' => 'Sunucu taşıma desteği',
                    'body' => "Mevcut VPS veya dedicated sunucunuzu Hostvim'e taşımak için destek ekibimizle iletişime geçin. Dosya rsync, veritabanı dump ve DNS geçişi planlı şekilde yapılır.\n\nPanelze panel kullanan sunuculardan geçiş süreci hızlandırılabilir.",
                ],
            ],
            'faqs' => [
                ['q' => 'Bulut sunucu nedir?', 'a' => 'Fiziksel sunucu kaynaklarının sanallaştırılarak size özel ayrılmasıdır. Paylaşımlı hostinge göre tam root erişim ve daha yüksek performans sağlar.'],
                ['q' => 'VPS ile VDS farkı nedir?', 'a' => 'VPS esnek kaynak tahsisi sunar; VDS\'te CPU ve RAM tamamen size ayrılır. Sabit yük için VDS, esnek bütçe için VPS uygundur.'],
                ['q' => 'Panelze panel kurulur mu?', 'a' => 'Evet, sipariş sırasında isteğe bağlı olarak Panelze hosting paneli otomatik kurulabilir. Panelsiz sunucu da tercih edilebilir.'],
                ['q' => 'Hangi kontrol paneli kullanılıyor?', 'a' => 'Hostvim kendi Panelze altyapısını kullanır. Site, SSL, veritabanı ve dosya yönetimi Panelze panel üzerinden yapılır; üçüncü parti panel lisansı gerekmez.'],
                ['q' => 'Hangi işletim sistemleri desteklenir?', 'a' => 'AlmaLinux, Rocky Linux, Ubuntu ve Debian. Windows Server için destek ekibimize danışın.'],
                ['q' => 'Sunucu yönetimi bana mı ait?', 'a' => 'Evet, tam root erişim sizdedir. Yönetim desteği için SLA paketi alabilirsiniz.'],
                ['q' => 'Kaynakları sonradan yükseltebilir miyim?', 'a' => 'Evet. CPU, RAM ve disk paket yükseltmesi ile artırılabilir.'],
                ['q' => 'Yedekleme var mı?', 'a' => 'Sunucu düzeyinde düzenli yedekleme yapılır. Kritik veriler için ek snapshot veya kendi yedek stratejinizi öneririz.'],
            ],
        ];
    }
}
