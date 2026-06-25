<?php

namespace App\Support;

/**
 * Panelze panel (v0.1.x) ile senkron pazarlama ve lisans modül kataloğu.
 * panel/config/panelze_pro_features.php ile aynı modül anahtarlarını kullanın.
 */
final class PanelFeatureCatalog
{
    public const PANEL_VERSION = '0.1.0';

    /** @return list<array{title: string, body: string, icon: string}> */
    public static function coreFeatureCards(string $locale = 'en'): array
    {
        return $locale === 'tr' ? self::CORE_CARDS_TR : self::CORE_CARDS_EN;
    }

    /** @return list<array{key: string, label: string, description: string, sort_order: int}> */
    public static function proModuleDefs(): array
    {
        return [
            [
                'key' => 'phpmyadmin_sso',
                'label' => 'phpMyAdmin tek tık giriş',
                'description' => 'Veritabanı ekranından SSO ile phpMyAdmin; parola kopyalamadan yönetim.',
                'sort_order' => 10,
            ],
            [
                'key' => 'security_pro',
                'label' => 'Gelişmiş güvenlik',
                'description' => 'Rate limit profilleri, ModSecurity site kuralları, tehdit istihbaratı, FIM, SSH sertleştirme, DDoS sysctl.',
                'sort_order' => 20,
            ],
            [
                'key' => 'backups_pro',
                'label' => 'Gelişmiş yedekleme',
                'description' => 'Google Drive hedefi ve uzak sunucudan geri yükleme; yerel yedekler Community’de açık kalır.',
                'sort_order' => 30,
            ],
            [
                'key' => 'monitoring_advanced',
                'label' => 'Sunucu izleme',
                'description' => 'Site sağlığı Community’de; CPU/RAM/disk ve sunucu metrikleri Pro ile açılır.',
                'sort_order' => 40,
            ],
            [
                'key' => 'ai_advisor',
                'label' => 'PanelZeka (AI)',
                'description' => 'Sohbet, dosya düzenleme ve deploy önerileri — panel içi AI asistan.',
                'sort_order' => 50,
            ],
            [
                'key' => 'curious_tools',
                'label' => 'Meraklısına',
                'description' => 'Ookla hız testi ve SEO analizi araçları.',
                'sort_order' => 55,
            ],
            [
                'key' => 'stripe_billing',
                'label' => 'Stripe faturalama',
                'description' => 'Panel içi Pro checkout ve ödeme akışı (Stripe).',
                'sort_order' => 60,
            ],
            [
                'key' => 'vendor_panel',
                'label' => 'Vendor kontrol düzlemi',
                'description' => 'Çok kiracılı vendor profili, tenant ve lisans yönetimi API’si.',
                'sort_order' => 70,
            ],
        ];
    }

    /** @return array<string, string> */
    public static function proModuleMarketingBullets(string $locale = 'tr'): array
    {
        $bullets = [];
        foreach (self::proModuleDefs() as $mod) {
            $bullets[$mod['key']] = $locale === 'tr'
                ? $mod['label'].' — '.$mod['description']
                : self::proModuleLabelEn($mod['key']).' — '.self::proModuleDescriptionEn($mod['key']);
        }

        return $bullets;
    }

    /** @return list<string> */
    public static function communityPlanFeatures(string $locale = 'tr'): array
    {
        if ($locale === 'en') {
            return [
                'Up to 5 hosted sites (Community license)',
                'Domains, DNS (BIND), redirects, PHP 7.4–8.4',
                'MySQL/PostgreSQL, file manager, FTP, email & Roundcube',
                'Let\'s Encrypt SSL, local backups & cron',
                'Git deploy, Node/PM2 apps, WP & OpenCart installer',
                'Site health monitoring, reseller & white-label branding',
            ];
        }

        return [
            'En fazla 5 barındırılan site (Community lisansı)',
            'Alan adı, DNS (BIND), yönlendirme, PHP 7.4–8.4',
            'MySQL/PostgreSQL, dosya yöneticisi, FTP, e-posta & Roundcube',
            'Let\'s Encrypt SSL, yerel yedek & cron',
            'Git deploy, Node/PM2 uygulamaları, WP & OpenCart kurucu',
            'Site sağlık izleme, bayi & white-label marka',
        ];
    }

    /** @return list<string> */
    public static function proPlanFeatures(string $locale = 'tr'): array
    {
        $core = $locale === 'en'
            ? 'Everything in Community, up to 500 sites per server'
            : 'Community’deki her şey, sunucu başına 500 siteye kadar';

        $modules = array_map(
            fn (array $m): string => $locale === 'tr' ? $m['label'] : self::proModuleLabelEn($m['key']),
            self::proModuleDefs()
        );

        return array_merge([$core], $modules);
    }

    /** @return array{ui_paths: list<string>, api_route_prefixes: list<string>} */
    public static function moduleIntegration(string $key): array
    {
        return SaasModuleDefaults::integration($key);
    }

    private static function proModuleLabelEn(string $key): string
    {
        return match ($key) {
            'phpmyadmin_sso' => 'phpMyAdmin one-click SSO',
            'security_pro' => 'Advanced security',
            'backups_pro' => 'Advanced backups',
            'monitoring_advanced' => 'Server monitoring',
            'ai_advisor' => 'PanelZeka (AI advisor)',
            'curious_tools' => 'Curious tools',
            'stripe_billing' => 'Stripe billing',
            'vendor_panel' => 'Vendor control plane',
            default => $key,
        };
    }

    private static function proModuleDescriptionEn(string $key): string
    {
        return match ($key) {
            'phpmyadmin_sso' => 'SSO from the database screen into phpMyAdmin without copying passwords.',
            'security_pro' => 'Rate limits, ModSecurity site rules, threat intel, FIM, SSH hardening, DDoS sysctl.',
            'backups_pro' => 'Google Drive targets and remote restore; local backups stay on Community.',
            'monitoring_advanced' => 'Site health on Community; CPU/RAM/disk server metrics unlock with Pro.',
            'ai_advisor' => 'In-panel AI chat, file edits, and deploy suggestions.',
            'curious_tools' => 'Ookla speed test and SEO analysis tools.',
            'stripe_billing' => 'In-panel Pro checkout via Stripe.',
            'vendor_panel' => 'Multi-tenant vendor profile and license management API.',
            default => '',
        };
    }

    /** @var list<array{title: string, body: string, icon: string}> */
    private const CORE_CARDS_TR = [
        ['title' => 'Site & alan adı', 'body' => 'Nginx/Apache/OpenLiteSpeed vhost, alt alan adı, alias, PHP sürümü ve yönlendirmeler tek panelden.', 'icon' => 'globe'],
        ['title' => 'Veritabanı & phpMyAdmin', 'body' => 'MySQL ve PostgreSQL kullanıcı/izin yönetimi; Pro ile tek tık phpMyAdmin SSO.', 'icon' => 'database'],
        ['title' => 'SSL & güvenlik', 'body' => 'Let\'s Encrypt otomasyonu, fail2ban, ModSecurity ve ClamAV; gelişmiş profiller Pro modülünde.', 'icon' => 'shield'],
        ['title' => 'Node & deploy', 'body' => 'PM2 ile Node uygulamaları, Git deploy, webhook ve rollback akışları.', 'icon' => 'terminal'],
        ['title' => 'E-posta & DNS', 'body' => 'Posta kutuları, yönlendiriciler, Roundcube webmail ve BIND9 zone senkronu.', 'icon' => 'users'],
        ['title' => 'Kurucu & yedek', 'body' => 'WordPress ve OpenCart tek tık kurulum; zamanlanmış yerel yedekler, Pro’da Drive/uzak restore.', 'icon' => 'layers'],
    ];

    /** @var list<array{title: string, body: string, icon: string}> */
    private const CORE_CARDS_EN = [
        ['title' => 'Sites & domains', 'body' => 'Nginx/Apache/OpenLiteSpeed vhosts, subdomains, aliases, PHP versions, and redirects from one panel.', 'icon' => 'globe'],
        ['title' => 'Databases & phpMyAdmin', 'body' => 'MySQL and PostgreSQL users/privileges; Pro adds one-click phpMyAdmin SSO.', 'icon' => 'database'],
        ['title' => 'SSL & security', 'body' => 'Let\'s Encrypt automation, fail2ban, ModSecurity, and ClamAV; advanced profiles in the Pro security module.', 'icon' => 'shield'],
        ['title' => 'Node & deploy', 'body' => 'PM2 Node apps, Git deploy, webhooks, and rollback flows.', 'icon' => 'terminal'],
        ['title' => 'Email & DNS', 'body' => 'Mailboxes, forwarders, Roundcube webmail, and BIND9 zone sync.', 'icon' => 'users'],
        ['title' => 'Installer & backups', 'body' => 'One-click WordPress and OpenCart; scheduled local backups, Drive/remote restore on Pro.', 'icon' => 'layers'],
    ];

    public static function platformFeaturesMarkdown(string $locale = 'tr'): string
    {
        $v = self::PANEL_VERSION;
        $proList = implode("\n", array_map(
            fn (array $m): string => '- **'.($locale === 'tr' ? $m['label'] : self::proModuleLabelEn($m['key'])).'** (`'.$m['key'].'`) — '.($locale === 'tr' ? $m['description'] : self::proModuleDescriptionEn($m['key'])),
            self::proModuleDefs()
        ));

        if ($locale === 'en') {
            return <<<MD
## Overview (Panelze v{$v})

The **customer panel** is a Laravel 11 + React SPA that talks to the **Go Engine** on loopback (typically `:9090`). The engine applies Nginx/Apache/OpenLiteSpeed vhosts, PHP-FPM pools, TLS, BIND DNS, mail stack, and file operations.

---

## Community (included)

| Area | Capabilities |
| --- | --- |
| **Sites** | Multi-site, subdomains, aliases, suspend/resume, PHP 7.4–8.4, document root |
| **DNS** | BIND9 zone sync (`panelze-bind-sync`) |
| **Databases** | MySQL/MariaDB & PostgreSQL provision, import/export |
| **Files & FTP** | File manager (zip, trash, chmod), FTP accounts |
| **Email** | Mailboxes, forwarders, Roundcube webmail signon |
| **SSL** | Let's Encrypt issue/renew/revoke, manual certs |
| **Backups** | On-demand & scheduled **local** backups |
| **Cron** | Customer cron jobs + system scheduler |
| **Monitoring** | Site health summaries |
| **Security UI** | fail2ban, ModSecurity, ClamAV (advanced actions need Pro) |
| **Deploy** | Git deploy, webhooks, rollback |
| **Node apps** | PM2 detect/start/stop/build |
| **Installer** | One-click **WordPress** & **OpenCart** only |
| **Plugins** | Plesk / cPanel / aaPanel migration plugins |
| **Reseller** | Users, packages, roles, white-label branding |

**Community license limit:** up to **5 sites** per server.

---

## Pro modules (license-gated)

{$proList}

Default Pro bundle also enables `phpmyadmin_sso` and `security_pro` when the hub feature list is empty.

---

## Admin & vendor

- **Admin:** system settings, web/PHP/DNS/mail stack, terminal, users, roles, packages, WHMCS module, license screen.
- **Vendor profile** (`vendor_panel` module): tenants, billing, support — separate `APP_PROFILE=vendor`.

For install steps see [Installation guide](/setup). For architecture see [Architecture](/docs/architecture).
MD;
        }

        return <<<MD
## Genel bakış (Panelze v{$v})

**Müşteri paneli** Laravel 11 + React SPA; **Go Engine** ile loopback üzerinden (genelde `:9090`) konuşur. Engine; Nginx/Apache/OpenLiteSpeed vhost, PHP-FPM, TLS, BIND DNS, posta yığını ve dosya işlemlerini uygular.

---

## Community (dahil)

| Alan | Yetenekler |
| --- | --- |
| **Siteler** | Çoklu site, alt alan adı, alias, askıya alma, PHP 7.4–8.4, document root |
| **DNS** | BIND9 zone senkronu (`panelze-bind-sync`) |
| **Veritabanları** | MySQL/MariaDB & PostgreSQL oluşturma, içe/dışa aktarma |
| **Dosya & FTP** | Dosya yöneticisi (zip, çöp, chmod), FTP hesapları |
| **E-posta** | Posta kutuları, yönlendiriciler, Roundcube webmail |
| **SSL** | Let's Encrypt çıkarma/yenileme/iptal, manuel sertifika |
| **Yedek** | Anlık ve zamanlanmış **yerel** yedekler |
| **Cron** | Müşteri cron + sistem zamanlayıcı |
| **İzleme** | Site sağlık özetleri |
| **Güvenlik** | fail2ban, ModSecurity, ClamAV (gelişmiş aksiyonlar Pro) |
| **Deploy** | Git deploy, webhook, rollback |
| **Node** | PM2 ile uygulama yönetimi |
| **Kurucu** | Yalnızca **WordPress** & **OpenCart** tek tık |
| **Eklentiler** | Plesk / cPanel / aaPanel taşıma eklentileri |
| **Bayi** | Kullanıcı, paket, rol, white-label marka |

**Community lisans limiti:** sunucu başına en fazla **5 site**.

---

## Pro modüller (lisans ile)

{$proList}

Hub özellik listesi boşsa Pro varsayılanı `phpmyadmin_sso` ve `security_pro` modüllerini açar.

---

## Admin & vendor

- **Admin:** sistem, web/PHP/DNS/posta yığını, terminal, kullanıcılar, roller, paketler, WHMCS modülü, lisans ekranı.
- **Vendor profili** (`vendor_panel` modülü): tenant, faturalama, destek — ayrı `APP_PROFILE=vendor`.

Kurulum için [Kurulum rehberi](/setup). Mimari için [Mimari](/docs/architecture).
MD;
    }
}
