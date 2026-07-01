<?php

namespace App\Support;

/**
 * Panelze /docs içeriği — TR ve EN; admin panelden düzenlenebilir (DocumentationSeeder ile senkron).
 */
final class DocumentationCatalog
{
    /**
     * @return list<array{
     *     slug: string,
     *     parent: ?string,
     *     sort_order: int,
     *     tr: array{title: string, meta: string, content: string},
     *     en: array{title: string, meta: string, content: string}
     * }>
     */
    public static function pages(): array
    {
        return array_merge(
            self::corePages(),
            self::installationPages(),
            self::panelGuidePages(),
            self::moduleSectionPages(),
            self::licensingPages(),
            self::integrationPages(),
            self::troubleshootingPages(),
        );
    }

    /** @return list<array<string, mixed>> */
    private static function corePages(): array
    {
        return [
            self::def('getting-started', null, 0,
                'Başlangıç',
                'Getting started',
                'Panelze dokümantasyonuna giriş: kurulum, panel kullanımı, modüller ve lisans.',
                'Introduction to Panelze docs: install, panel usage, modules, and licensing.',
                self::gettingStartedContent('tr'),
                self::gettingStartedContent('en'),
            ),
            self::def('architecture', null, 5,
                'Mimari genel bakış',
                'Architecture overview',
                'Engine, panel ve müşteri veritabanları; güven sınırları ve lisans akışı.',
                'Engine vs panel vs tenant DBs; trust boundaries and licensing flow.',
                self::architectureContent('tr'),
                self::architectureContent('en'),
            ),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function installationPages(): array
    {
        return [
            self::def('installation', null, 10,
                'Kurulum',
                'Installation',
                'Sunucu hazırlığı, kurulum yöntemleri, ortam değişkenleri ve güncelleme.',
                'Server prep, install methods, environment variables, and updates.',
                self::installationHubContent('tr'),
                self::installationHubContent('en'),
            ),
            self::def('server-setup', 'installation', 10,
                'Sunucu kurulumu',
                'Server setup',
                'Ubuntu hazırlığı, firewall, bootstrap ve ilk doğrulama.',
                'Ubuntu prep, firewall, bootstrap, and first validation.',
                self::serverSetupContent('tr'),
                self::serverSetupContent('en'),
            ),
            self::def('install-commands', 'installation', 15,
                'Kurulum komutları',
                'Install commands',
                'Tek satır, Community, Pro ve güncelleme komutları.',
                'One-liner, Community, Pro, and update commands.',
                self::installCommandsContent('tr'),
                self::installCommandsContent('en'),
            ),
            self::def('post-install', 'installation', 20,
                'Kurulum sonrası',
                'Post-install checklist',
                'İlk giriş, sağlık kontrolleri ve üretime geçiş.',
                'First login, health checks, and production cutover.',
                self::postInstallContent('tr'),
                self::postInstallContent('en'),
            ),
            self::def('environment-variables', 'installation', 25,
                'Ortam değişkenleri',
                'Environment variables',
                'ENGINE_*, LICENSE_* ve panel .env alanları.',
                'ENGINE_*, LICENSE_*, and panel .env fields.',
                self::envVarsContent('tr'),
                self::envVarsContent('en'),
            ),
            self::def('updating-panel', 'installation', 30,
                'Panel güncelleme',
                'Updating the panel',
                'Community/Pro güncelleme betikleri ve sürüm kontrolü.',
                'Community/Pro update scripts and release channels.',
                self::updatingContent('tr'),
                self::updatingContent('en'),
            ),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function panelGuidePages(): array
    {
        return [
            self::def('panel-guide', null, 20,
                'Panel kullanımı',
                'Using the panel',
                'Site, veritabanı, SSL, yedek, deploy ve kullanıcı yönetimi rehberi.',
                'Guide to sites, databases, SSL, backups, deploy, and users.',
                self::panelGuideHubContent('tr'),
                self::panelGuideHubContent('en'),
            ),
            self::def('sites-and-domains', 'panel-guide', 10,
                'Siteler ve alan adları',
                'Sites and domains',
                'Site oluşturma, PHP sürümü, alt alan adı ve askıya alma.',
                'Create sites, PHP version, subdomains, and suspension.',
                self::sitesContent('tr'),
                self::sitesContent('en'),
            ),
            self::def('databases', 'panel-guide', 20,
                'Veritabanları',
                'Databases',
                'MySQL/PostgreSQL oluşturma, içe/dışa aktarma ve phpMyAdmin.',
                'MySQL/PostgreSQL provisioning, import/export, and phpMyAdmin.',
                self::databasesContent('tr'),
                self::databasesContent('en'),
            ),
            self::def('ssl-dns-email', 'panel-guide', 30,
                'SSL, DNS ve e-posta',
                'SSL, DNS, and email',
                'Let\'s Encrypt, BIND zone ve posta kutuları.',
                'Let\'s Encrypt, BIND zones, and mailboxes.',
                self::sslDnsEmailContent('tr'),
                self::sslDnsEmailContent('en'),
            ),
            self::def('files-ftp', 'panel-guide', 40,
                'Dosyalar ve FTP',
                'Files and FTP',
                'Dosya yöneticisi, izinler ve FTP hesapları.',
                'File manager, permissions, and FTP accounts.',
                self::filesFtpContent('tr'),
                self::filesFtpContent('en'),
            ),
            self::def('backups-and-cron', 'panel-guide', 50,
                'Yedekleme ve cron',
                'Backups and cron',
                'Yerel yedekler, zamanlama ve cron işleri.',
                'Local backups, schedules, and cron jobs.',
                self::backupsCronContent('tr'),
                self::backupsCronContent('en'),
            ),
            self::def('git-and-node', 'panel-guide', 60,
                'Git deploy ve Node',
                'Git deploy and Node',
                'Git webhook, rollback ve PM2 uygulamaları.',
                'Git webhooks, rollback, and PM2 apps.',
                self::gitNodeContent('tr'),
                self::gitNodeContent('en'),
            ),
            self::def('users-roles-reseller', 'panel-guide', 70,
                'Kullanıcılar ve bayi',
                'Users and reseller',
                'Roller, paketler ve white-label marka.',
                'Roles, packages, and white-label branding.',
                self::usersResellerContent('tr'),
                self::usersResellerContent('en'),
            ),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function moduleSectionPages(): array
    {
        $pages = [
            self::def('modules', null, 30,
                'Pro modüller',
                'Pro modules',
                'Lisans ile açılan gelişmiş özellikler ve modül anahtarları.',
                'License-gated advanced features and module keys.',
                self::modulesHubContent('tr'),
                self::modulesHubContent('en'),
            ),
            self::def('platform-features', 'modules', 5,
                'Platform yetenekleri özeti',
                'Platform capabilities overview',
                'Community ve Pro karşılaştırması; tüm modül listesi.',
                'Community vs Pro comparison and full module list.',
                PanelFeatureCatalog::platformFeaturesMarkdown('tr'),
                PanelFeatureCatalog::platformFeaturesMarkdown('en'),
            ),
        ];

        foreach (PanelFeatureCatalog::proModuleDefs() as $mod) {
            $slug = 'module-'.str_replace('_', '-', $mod['key']);
            $pages[] = self::def(
                $slug,
                'modules',
                $mod['sort_order'],
                $mod['label'],
                PanelFeatureCatalog::proModuleLabelEn($mod['key']),
                $mod['description'],
                PanelFeatureCatalog::proModuleDescriptionEn($mod['key']),
                self::moduleDetailContent('tr', $mod['key']),
                self::moduleDetailContent('en', $mod['key']),
            );
        }

        return $pages;
    }

    /** @return list<array<string, mixed>> */
    private static function licensingPages(): array
    {
        return [
            self::def('licensing', null, 40,
                'Lisans ve planlar',
                'Licensing and plans',
                'Community, Pro, lisans anahtarı ve merkezi doğrulama.',
                'Community, Pro, license keys, and central verification.',
                self::licensingHubContent('tr'),
                self::licensingHubContent('en'),
            ),
            self::def('license-activation', 'licensing', 10,
                'Lisans aktivasyonu',
                'License activation',
                'Anahtarı panele girme, yenileme ve modül açma.',
                'Paste keys, renewal, and module entitlements.',
                self::licenseActivationContent('tr'),
                self::licenseActivationContent('en'),
            ),
            self::def('community-vs-pro', 'licensing', 20,
                'Community ve Pro',
                'Community vs Pro',
                'Limitler, site kotası ve hangi özellik nerede açılır.',
                'Limits, site quotas, and where features unlock.',
                self::communityVsProContent('tr'),
                self::communityVsProContent('en'),
            ),
            self::def('license-server-hub', 'licensing', 30,
                'Lisans sunucusu (hub)',
                'License server hub',
                'LICENSE_SERVER_URL, API ve panelze.com entegrasyonu.',
                'LICENSE_SERVER_URL, API, and panelze.com integration.',
                self::licenseHubContent('tr'),
                self::licenseHubContent('en'),
            ),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function integrationPages(): array
    {
        return [
            self::def('integrations', null, 50,
                'Entegrasyonlar',
                'Integrations',
                'Google Drive, ödeme sağlayıcıları ve harici servisler.',
                'Google Drive, payment providers, and external services.',
                self::integrationsHubContent('tr'),
                self::integrationsHubContent('en'),
            ),
            self::def('google-drive-backups', 'integrations', 10,
                'Google Drive yedekleme',
                'Google Drive backups',
                'OAuth, panelze.com ayarları ve müşteri bağlantısı.',
                'OAuth, panelze.com settings, and customer linking.',
                self::googleDriveContent('tr'),
                self::googleDriveContent('en'),
            ),
            self::def('payments-stripe-paytr', 'integrations', 20,
                'Ödeme (Stripe / PayTR)',
                'Payments (Stripe / PayTR)',
                'Landing ödeme akışı ve panel içi Stripe modülü.',
                'Landing checkout and in-panel Stripe module.',
                self::paymentsContent('tr'),
                self::paymentsContent('en'),
            ),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function troubleshootingPages(): array
    {
        return [
            self::def('troubleshooting', null, 60,
                'Sorun giderme',
                'Troubleshooting',
                'Sık karşılaşılan hatalar, loglar ve destek.',
                'Common errors, logs, and support.',
                self::troubleshootingHubContent('tr'),
                self::troubleshootingHubContent('en'),
            ),
            self::def('common-issues', 'troubleshooting', 10,
                'Sık sorunlar',
                'Common issues',
                'Engine bağlantısı, SSL, DNS ve lisans hataları.',
                'Engine connectivity, SSL, DNS, and license errors.',
                self::commonIssuesContent('tr'),
                self::commonIssuesContent('en'),
            ),
            self::def('logs-and-health', 'troubleshooting', 20,
                'Loglar ve sağlık kontrolü',
                'Logs and health checks',
                'Panel logları, Engine journal ve /api/health.',
                'Panel logs, Engine journal, and /api/health.',
                self::logsHealthContent('tr'),
                self::logsHealthContent('en'),
            ),
        ];
    }

    /**
     * @return array{slug: string, parent: ?string, sort_order: int, tr: array{title: string, meta: string, content: string}, en: array{title: string, meta: string, content: string}}
     */
    private static function def(
        string $slug,
        ?string $parent,
        int $sortOrder,
        string $titleTr,
        string $titleEn,
        string $metaTr,
        string $metaEn,
        string $contentTr,
        string $contentEn,
    ): array {
        return [
            'slug' => $slug,
            'parent' => $parent,
            'sort_order' => $sortOrder,
            'tr' => ['title' => $titleTr, 'meta' => $metaTr, 'content' => $contentTr],
            'en' => ['title' => $titleEn, 'meta' => $metaEn, 'content' => $contentEn],
        ];
    }

    private static function gettingStartedContent(string $locale): string
    {
        if ($locale === 'en') {
            return <<<'MD'
# Welcome to Panelze documentation

Panelze is a **Linux hosting control stack** made of two cooperating parts:

| Part | Role |
| --- | --- |
| **Panelze Engine (Go)** | Applies vhosts, PHP-FPM, TLS, DNS, mail, and file operations on the server |
| **Panel (Laravel + React)** | Browser UI, users/roles, licensing, and authenticated calls into the Engine |

## Documentation map

| Section | What you will learn |
| --- | --- |
| [Installation](/docs/installation) | Server prep, install scripts, `.env` wiring, updates |
| [Using the panel](/docs/panel-guide) | Day-to-day hosting workflows |
| [Pro modules](/docs/modules) | License-gated features (`security_pro`, `backups_pro`, …) |
| [Licensing](/docs/licensing) | Community vs Pro, keys, central hub |
| [Integrations](/docs/integrations) | Google Drive OAuth, Stripe/PayTR |
| [Architecture](/docs/architecture) | Trust boundaries and data stores |
| [Troubleshooting](/docs/troubleshooting) | Logs, health checks, common fixes |

> **Quick path:** New server → [Server setup](/docs/server-setup) → [Post-install](/docs/post-install) → create your first site under [Sites and domains](/docs/sites-and-domains).

All pages exist in **Turkish and English**. Switch language with the site language picker or `?lang=en` / `?lang=tr` on any URL.
MD;
        }

        return <<<'MD'
# Panelze dokümantasyonuna hoş geldiniz

Panelze, birbirine bağlı **iki ana bileşenden** oluşan bir **Linux hosting kontrol yığınıdır**:

| Bileşen | Görev |
| --- | --- |
| **Panelze Engine (Go)** | Sunucuda vhost, PHP-FPM, TLS, DNS, posta ve dosya işlemlerini uygular |
| **Panel (Laravel + React)** | Tarayıcı arayüzü, kullanıcı/rol, lisans ve Engine API çağrıları |

## Dokümantasyon haritası

| Bölüm | Ne öğrenirsiniz? |
| --- | --- |
| [Kurulum](/docs/installation) | Sunucu hazırlığı, kurulum betikleri, `.env`, güncelleme |
| [Panel kullanımı](/docs/panel-guide) | Günlük hosting iş akışları |
| [Pro modüller](/docs/modules) | Lisanslı özellikler (`security_pro`, `backups_pro`, …) |
| [Lisans ve planlar](/docs/licensing) | Community vs Pro, anahtar, merkezi hub |
| [Entegrasyonlar](/docs/integrations) | Google Drive OAuth, Stripe/PayTR |
| [Mimari](/docs/architecture) | Güven sınırları ve veri katmanları |
| [Sorun giderme](/docs/troubleshooting) | Loglar, sağlık kontrolü, sık hatalar |

> **Hızlı yol:** Yeni sunucu → [Sunucu kurulumu](/docs/server-setup) → [Kurulum sonrası](/docs/post-install) → [Siteler ve alan adları](/docs/sites-and-domains) ile ilk siteyi oluşturun.

Tüm sayfalar **Türkçe ve İngilizce** mevcuttur. Dil değiştirmek için site dil seçicisini veya her URL’de `?lang=tr` / `?lang=en` kullanın.
MD;
    }

    private static function architectureContent(string $locale): string
    {
        if ($locale === 'en') {
            return <<<'MD'
## High-level components

| Layer | Responsibility |
| --- | --- |
| **Panelze Engine** | Nginx/Apache/OpenLiteSpeed vhosts, PHP-FPM pools, TLS, BIND DNS, mail stack, quotas |
| **Panel (Laravel)** | HTTP UI + API: auth, RBAC, Stripe checkout, license verification |
| **Panel database** | Tenants, sites, service metadata — **not** customer site databases |
| **Customer DBs** | MySQL/PostgreSQL created via Engine; backups from the panel |

## Request path

1. Operator opens the panel over HTTPS (cookies, optional 2FA).
2. Actions become Engine calls protected by `ENGINE_INTERNAL_KEY` / `ENGINE_API_SECRET`.
3. Engine performs privileged host changes; audit via panel `storage/logs` and `journalctl`.

## Licensing

- Keys may be pasted in **Panel → License** or validated against a hub (`LICENSE_SERVER_URL`).
- **Community** allows up to **5 sites** per server; **Pro** raises limits and unlocks modules.

See [Pro modules](/docs/modules) and [License server hub](/docs/license-server-hub).
MD;
        }

        return <<<'MD'
## Üst düzey bileşenler

| Katman | Sorumluluk |
| --- | --- |
| **Panelze Engine** | Nginx/Apache/OpenLiteSpeed vhost, PHP-FPM, TLS, BIND DNS, posta, kota |
| **Panel (Laravel)** | HTTP arayüz + API: kimlik, RBAC, Stripe, lisans doğrulama |
| **Panel veritabanı** | Kiracı, site, meta veri — **müşteri site veritabanları değil** |
| **Müşteri DB** | Engine ile oluşturulan MySQL/PostgreSQL; yedek panelden |

## İstek yolu

1. Operatör panele HTTPS ile girer (çerez, isteğe bağlı 2FA).
2. İşlemler `ENGINE_INTERNAL_KEY` / `ENGINE_API_SECRET` ile korunan Engine çağrılarına dönüşür.
3. Engine ayrıcalıklı işlemi yapar; denetim: panel `storage/logs` ve `journalctl`.

## Lisanslama

- Anahtar **Panel → Lisans** ekranına yapıştırılabilir veya hub ile doğrulanır (`LICENSE_SERVER_URL`).
- **Community** sunucu başına **5 site**; **Pro** limitleri ve modülleri açar.

Bkz. [Pro modüller](/docs/modules) ve [Lisans sunucusu](/docs/license-server-hub).
MD;
    }

    private static function installationHubContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Installation hub\n\nFollow these guides in order:\n\n1. [Server setup](/docs/server-setup) — OS, firewall, bootstrap\n2. [Install commands](/docs/install-commands) — one-liner, Community, Pro\n3. [Post-install checklist](/docs/post-install)\n4. [Environment variables](/docs/environment-variables)\n5. [Updating the panel](/docs/updating-panel)\n\nExtended narrative also lives on the [Installation guide](/setup) site page."
            : "## Kurulum merkezi\n\nSırayla bu rehberleri izleyin:\n\n1. [Sunucu kurulumu](/docs/server-setup) — işletim sistemi, firewall, bootstrap\n2. [Kurulum komutları](/docs/install-commands) — tek satır, Community, Pro\n3. [Kurulum sonrası](/docs/post-install)\n4. [Ortam değişkenleri](/docs/environment-variables)\n5. [Panel güncelleme](/docs/updating-panel)\n\nGeniş anlatım ayrıca [Kurulum rehberi](/setup) sayfasında yer alır.";
    }

    private static function serverSetupContent(string $locale): string
    {
        if ($locale === 'en') {
            return <<<'MD'
## Before you run the installer

1. Patch the host: `sudo apt update && sudo apt upgrade -y`
2. Confirm **NTP** is active (`timedatectl status`)
3. Open **22, 80, 443** (and panel port if not behind 443) via UFW or your firewall
4. Prefer **SSH keys** over password login

## Bootstrap

Use the **Install commands** block at the bottom of this page or see [Install commands](/docs/install-commands).

After bootstrap:

- `sudo systemctl status panelze-engine` → **active**
- `sudo cat /root/panelze-admin-login.txt` → first admin credentials
- Open the panel URL in your browser

## Wire Engine secrets

`ENGINE_API_URL`, `ENGINE_INTERNAL_KEY`, and `ENGINE_API_SECRET` in the panel `.env` must match the Engine config on the same node.

Next: [Post-install checklist](/docs/post-install).
MD;
        }

        return <<<'MD'
## Betiği çalıştırmadan önce

1. Sunucuyu güncelleyin: `sudo apt update && sudo apt upgrade -y`
2. **NTP** aktif olsun (`timedatectl status`)
3. **22, 80, 443** portlarını açın (panel ayrı porttaysa onu da)
4. **SSH anahtarı** kullanın

## Bootstrap

Sayfanın altındaki **Kurulum komutları** veya [Kurulum komutları](/docs/install-commands) sayfasına bakın.

Betik sonrası:

- `sudo systemctl status panelze-engine` → **active**
- `sudo cat /root/panelze-admin-login.txt` → ilk yönetici bilgisi
- Panel URL’sini tarayıcıda açın

## Engine gizli anahtarları

Panel `.env` içindeki `ENGINE_API_URL`, `ENGINE_INTERNAL_KEY`, `ENGINE_API_SECRET` aynı sunucudaki Engine yapılandırmasıyla eşleşmeli.

Sonraki: [Kurulum sonrası](/docs/post-install).
MD;
    }

    private static function installCommandsContent(string $locale): string
    {
        $one = \App\Services\InstallGuide::oneLiner();
        $community = \App\Services\InstallGuide::community();
        $pro = \App\Services\InstallGuide::pro();

        if ($locale === 'en') {
            return <<<MD
## Supported install paths

| Method | When to use |
| --- | --- |
| **One-liner** | Fastest trial on a clean Ubuntu VPS |
| **Community script** | Open-source stack without a license key |
| **Pro script** | Pass `PANELZE_LICENSE_KEY` during install |
| **Manual git** | Air-gapped or custom paths — see [Installation guide](/setup) |

### One-liner

```bash
{$one}
```

### Community

```bash
{$community}
```

### Pro (with license)

```bash
{$pro}
```

> Review any `curl | bash` script in staging before production. Commands are maintained in `deploy/` and synced via admin **Appearance → Install commands**.

After install, complete [Post-install](/docs/post-install).
MD;
        }

        return <<<MD
## Desteklenen kurulum yolları

| Yöntem | Ne zaman? |
| --- | --- |
| **Tek satır** | Temiz Ubuntu VPS’te hızlı deneme |
| **Community betiği** | Lisans anahtarı olmadan açık kaynak kurulum |
| **Pro betiği** | Kurulumda `PANELZE_LICENSE_KEY` ile |
| **Elle git** | Özel ortam — [Kurulum rehberi](/setup) |

### Tek satır

```bash
{$one}
```

### Community

```bash
{$community}
```

### Pro (lisanslı)

```bash
{$pro}
```

> Üretimde `curl | bash` betiğini önce staging’de inceleyin. Komutlar `deploy/` ile senkron; admin **Görünüm → Kurulum komutları** sekmesinden düzenlenir.

Kurulumdan sonra [Kurulum sonrası](/docs/post-install) listesini tamamlayın.
MD;
    }

    private static function postInstallContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Post-install checklist\n\n1. Log in as admin; enforce **strong passwords** and **2FA** where available\n2. Call `GET /api/health` — expect JSON with `status: ok`\n3. Create a **staging site**; verify DNS, TLS, and PHP version\n4. Paste or verify **license key** under [License activation](/docs/license-activation)\n5. Configure **local backups** — see [Backups and cron](/docs/backups-and-cron)\n6. Document break-glass SSH access for your team"
            : "## Kurulum sonrası kontrol listesi\n\n1. Yönetici olarak giriş yapın; **güçlü parola** ve mümkünse **2FA**\n2. `GET /api/health` — JSON içinde `status: ok` bekleyin\n3. **Staging site** oluşturun; DNS, TLS ve PHP sürümünü doğrulayın\n4. [Lisans aktivasyonu](/docs/license-activation) ile anahtarı girin veya doğrulayın\n5. **Yerel yedek** planlayın — [Yedekleme ve cron](/docs/backups-and-cron)\n6. Ekibiniz için acil SSH erişim prosedürünü yazın";
    }

    private static function envVarsContent(string $locale): string
    {
        if ($locale === 'en') {
            return <<<'MD'
## Engine linkage (panel `.env`)

| Variable | Purpose |
| --- | --- |
| `ENGINE_API_URL` | Base URL, often `http://127.0.0.1:9090` on single-node installs |
| `ENGINE_INTERNAL_KEY` | Shared secret between panel and Engine |
| `ENGINE_API_SECRET` | Signed requests (e.g. web terminal JWT) |

Mismatch causes silent failures when creating sites or renewing certificates.

## Licensing

| Variable | Purpose |
| --- | --- |
| `LICENSE_SERVER_URL` | Central hub (e.g. `https://panelze.com`) for online validation |
| `LICENSE_KEY` | Optional in `.env`; many teams paste in the UI instead |
| `LICENSE_SERVER_API_SECRET` | Bearer token matching the hub |

See [License server hub](/docs/license-server-hub).
MD;
        }

        return <<<'MD'
## Engine bağlantısı (panel `.env`)

| Değişken | Amaç |
| --- | --- |
| `ENGINE_API_URL` | Taban URL; tek düğümde sıkça `http://127.0.0.1:9090` |
| `ENGINE_INTERNAL_KEY` | Panel ve Engine arası paylaşılan anahtar |
| `ENGINE_API_SECRET` | İmzalı istekler (ör. web terminal JWT) |

Uyumsuzluk site oluşturma veya sertifika yenilemede sessiz hatalara yol açar.

## Lisanslama

| Değişken | Amaç |
| --- | --- |
| `LICENSE_SERVER_URL` | Merkezi hub (ör. `https://panelze.com`) |
| `LICENSE_KEY` | İsteğe bağlı `.env`; çoğu ekip arayüzden girer |
| `LICENSE_SERVER_API_SECRET` | Hub ile eşleşen Bearer token |

Bkz. [Lisans sunucusu](/docs/license-server-hub).
MD;
    }

    private static function updatingContent(string $locale): string
    {
        $upd = \App\Services\InstallGuide::updateCommunity();

        if ($locale === 'en') {
            return <<<MD
## Update channels

Panelze publishes **panel releases** on the license hub. Customer panels poll the update API when configured.

### Community update script

```bash
{$upd}
```

### Best practices

1. Snapshot the server or take backups before upgrading
2. Upgrade **Engine and panel** together when release notes require it
3. Verify `GET /api/health` after upgrade
4. Read release notes on [Panel releases](/docs/install-commands) admin area on panelze.com

Pro customers receive update notifications in-panel when a new version is published.
MD;
        }

        return <<<MD
## Güncelleme kanalları

Panelze, lisans hub üzerinden **panel sürümleri** yayınlar. Müşteri panelleri yapılandırıldığında güncelleme API’sini sorgular.

### Community güncelleme betiği

```bash
{$upd}
```

### En iyi uygulamalar

1. Yükseltmeden önce snapshot veya yedek alın
2. Sürüm notları gerektiriyorsa **Engine ve paneli** birlikte güncelleyin
3. Yükseltme sonrası `GET /api/health` doğrulayın
4. panelze.com admin **Panel sürümleri** bölümündeki notları okuyun

Pro müşteriler yeni sürüm yayınlandığında panel içi bildirim alır.
MD;
    }

    private static function panelGuideHubContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Using the panel\n\n| Guide | Topics |\n| --- | --- |\n| [Sites and domains](/docs/sites-and-domains) | Create sites, PHP, subdomains |\n| [Databases](/docs/databases) | MySQL/PostgreSQL, imports |\n| [SSL, DNS, and email](/docs/ssl-dns-email) | Let's Encrypt, BIND, mailboxes |\n| [Files and FTP](/docs/files-ftp) | File manager, FTP accounts |\n| [Backups and cron](/docs/backups-and-cron) | Schedules, cron jobs |\n| [Git deploy and Node](/docs/git-and-node) | Webhooks, PM2 |\n| [Users and reseller](/docs/users-roles-reseller) | RBAC, packages, branding |"
            : "## Panel kullanımı\n\n| Rehber | Konular |\n| --- | --- |\n| [Siteler ve alan adları](/docs/sites-and-domains) | Site, PHP, alt alan adı |\n| [Veritabanları](/docs/databases) | MySQL/PostgreSQL, içe aktarma |\n| [SSL, DNS ve e-posta](/docs/ssl-dns-email) | Let's Encrypt, BIND, posta |\n| [Dosyalar ve FTP](/docs/files-ftp) | Dosya yöneticisi, FTP |\n| [Yedekleme ve cron](/docs/backups-and-cron) | Zamanlama, cron |\n| [Git deploy ve Node](/docs/git-and-node) | Webhook, PM2 |\n| [Kullanıcılar ve bayi](/docs/users-roles-reseller) | RBAC, paket, marka |";
    }

    private static function sitesContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Create a website\n\n1. **Panel → Websites → Create**\n2. Enter domain name (must resolve to this server for TLS)\n3. Choose **PHP version** (7.4–8.4) and document root if needed\n4. Click **Create** — Engine provisions the vhost\n\n## Subdomains and aliases\n\nAdd subdomains from the site detail page. **Suspend** stops traffic without deleting files.\n\n## Limits\n\n**Community** license: up to **5 sites** per server. **Pro** raises the ceiling — see [Community vs Pro](/docs/community-vs-pro)."
            : "## Web sitesi oluşturma\n\n1. **Panel → Siteler → Oluştur**\n2. Alan adını girin (TLS için DNS bu sunucuya işaret etmeli)\n3. **PHP sürümü** (7.4–8.4) ve gerekirse document root seçin\n4. **Oluştur** — Engine vhost’u hazırlar\n\n## Alt alan adı ve alias\n\nSite detayından alt alan adı ekleyin. **Askıya alma** dosyaları silmeden trafiği durdurur.\n\n## Limitler\n\n**Community** lisansı: sunucu başına **5 site**. **Pro** üst sınırı yükseltir — [Community ve Pro](/docs/community-vs-pro).";
    }

    private static function databasesContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Databases\n\n1. **Panel → Databases → Create**\n2. Choose **MySQL/MariaDB** or **PostgreSQL**\n3. Assign a user and password; grant privileges to the site\n\n## Import / export\n\nUse **phpMyAdmin** (Community) or **one-click SSO** with the [phpMyAdmin SSO](/docs/module-phpmyadmin-sso) Pro module.\n\n## Security\n\n- Use strong passwords and least privilege\n- Do not expose database ports publicly"
            : "## Veritabanları\n\n1. **Panel → Veritabanları → Oluştur**\n2. **MySQL/MariaDB** veya **PostgreSQL** seçin\n3. Kullanıcı ve parola atayın; siteye yetki verin\n\n## İçe / dışa aktarma\n\n**phpMyAdmin** (Community) veya Pro [phpMyAdmin SSO](/docs/module-phpmyadmin-sso) modülü ile tek tık giriş.\n\n## Güvenlik\n\n- Güçlü parola ve en az yetki\n- Veritabanı portlarını internete açmayın";
    }

    private static function sslDnsEmailContent(string $locale): string
    {
        return $locale === 'en'
            ? "## SSL (Let's Encrypt)\n\nFrom the site **SSL** tab: issue, renew, or upload a manual certificate. HTTP-01 requires port 80 reachable from the internet.\n\n## DNS (BIND)\n\nPanel syncs zones via `panelze-bind-sync`. Edit records from **DNS** section per domain.\n\n## Email\n\nCreate mailboxes, forwarders, and open **Roundcube** webmail from the mail UI."
            : "## SSL (Let's Encrypt)\n\nSite **SSL** sekmesinden sertifika çıkarın, yenileyin veya manuel yükleyin. HTTP-01 için 80 portu internetten erişilebilir olmalı.\n\n## DNS (BIND)\n\nPanel `panelze-bind-sync` ile zone senkronlar. **DNS** bölümünden kayıt düzenleyin.\n\n## E-posta\n\nPosta kutusu ve yönlendirici oluşturun; **Roundcube** webmail arayüzünü kullanın.";
    }

    private static function filesFtpContent(string $locale): string
    {
        return $locale === 'en'
            ? "## File manager\n\nBrowse site roots, upload zips, edit permissions (chmod), and use trash restore.\n\n## FTP\n\nCreate FTP accounts scoped to a site directory for legacy workflows or IDE deployment."
            : "## Dosya yöneticisi\n\nSite köklerinde gezin, zip yükleyin, izin (chmod) düzenleyin, çöp kutusundan geri yükleyin.\n\n## FTP\n\nSite dizinine kısıtlı FTP hesabı oluşturun; IDE veya eski iş akışları için.";
    }

    private static function backupsCronContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Local backups (Community)\n\n**Backups → Create** for on-demand snapshots. Schedule recurring jobs per site.\n\n## Pro extensions\n\n[Google Drive backups](/docs/google-drive-backups) and remote restore require the `backups_pro` module.\n\n## Cron\n\n**Cron** UI lets customers define PHP/curl/shell jobs with standard crontab syntax."
            : "## Yerel yedek (Community)\n\n**Yedekler → Oluştur** ile anlık snapshot. Site başına zamanlanmış iş tanımlayın.\n\n## Pro uzantıları\n\n[Google Drive yedekleme](/docs/google-drive-backups) ve uzak geri yükleme için `backups_pro` modülü gerekir.\n\n## Cron\n\n**Cron** arayüzü müşterilerin PHP/curl/shell işleri tanımlamasını sağlar.";
    }

    private static function gitNodeContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Git deploy\n\nConnect a repository, configure branch and deploy path, enable webhooks for push-to-deploy. Use **rollback** to restore the previous release.\n\n## Node / PM2\n\nDetect `package.json`, install dependencies, and manage PM2 processes from the **Node apps** section."
            : "## Git deploy\n\nDepo bağlayın, dal ve deploy yolu ayarlayın, push ile deploy için webhook açın. **Geri al** ile önceki sürüme dönün.\n\n## Node / PM2\n\n`package.json` algılayın, bağımlılıkları kurun, **Node uygulamaları** bölümünden PM2 süreçlerini yönetin.";
    }

    private static function usersResellerContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Users and roles\n\nAdmins create **users** with role-based abilities (sites, databases, billing, etc.).\n\n## Reseller / packages\n\n**Packages** define quotas (sites, disk, bandwidth). **White-label** branding customizes logo and colors for end customers.\n\nVendor-scale multi-tenancy uses the [Vendor control plane](/docs/module-vendor-panel) module."
            : "## Kullanıcılar ve roller\n\nYöneticiler **kullanıcı** oluşturur; roller yetenekleri sınırlar (site, veritabanı, faturalama vb.).\n\n## Bayi / paketler\n\n**Paketler** kota tanımlar (site, disk, bant genişliği). **White-label** ile logo ve renk özelleştirilir.\n\nÇok kiracılı vendor ölçeği için [Vendor kontrol düzlemi](/docs/module-vendor-panel) modülü kullanılır.";
    }

    private static function modulesHubContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Pro modules\n\nModules are **license entitlements** identified by keys such as `security_pro`. The hub returns enabled modules; the panel hides UI and API routes when a module is off.\n\nStart with [Platform capabilities overview](/docs/platform-features), then open each module page below for step-by-step usage."
            : "## Pro modüller\n\nModüller `security_pro` gibi anahtarlarla tanımlanan **lisans haklarıdır**. Hub açık modülleri döner; kapalı modülde arayüz ve API gizlenir.\n\n[Platform yetenekleri özeti](/docs/platform-features) ile başlayın; her modül sayfasında adım adım kullanım var.";
    }

    private static function moduleDetailContent(string $locale, string $key): string
    {
        $mod = collect(PanelFeatureCatalog::proModuleDefs())->firstWhere('key', $key);
        if (! $mod) {
            return '';
        }

        $integration = SaasModuleDefaults::integration($key);
        $ui = $integration['ui_paths'] ? implode(', ', $integration['ui_paths']) : ($locale === 'en' ? '(API only / embedded actions)' : '(yalnızca API / gömülü aksiyonlar)');
        $api = $integration['api_route_prefixes'] ? implode(', ', $integration['api_route_prefixes']) : '—';

        $label = $locale === 'tr' ? $mod['label'] : PanelFeatureCatalog::proModuleLabelEn($key);
        $desc = $locale === 'tr' ? $mod['description'] : PanelFeatureCatalog::proModuleDescriptionEn($key);

        if ($locale === 'en') {
            return <<<MD
## What is it?

**{$label}** (`{$key}`) — {$desc}

## Who can use it?

Requires a **Pro license** that includes this module key. Community installs do not expose these screens.

## How to enable

1. Purchase or receive a Pro key from [panelze.com](https://panelze.com)
2. **Panel → License** — paste the key or rely on `LICENSE_SERVER_URL`
3. Confirm the module appears in the entitlement list
4. Refresh the panel; new menu entries unlock

## Where in the panel?

- **UI paths:** {$ui}
- **API prefixes:** `{$api}`

## Typical workflow

{$mod['description']}

Open the related section in the left menu after the module is active. If menus stay hidden, verify the license hub lists `{$key}` and clear config cache.

## Related

- [Platform capabilities](/docs/platform-features)
- [License activation](/docs/license-activation)
MD;
        }

        return <<<MD
## Nedir?

**{$label}** (`{$key}`) — {$desc}

## Kimler kullanır?

Bu modül anahtarını içeren **Pro lisans** gerekir. Community kurulumlarda bu ekranlar görünmez.

## Nasıl açılır?

1. [panelze.com](https://panelze.com) üzerinden Pro anahtar alın
2. **Panel → Lisans** — anahtarı yapıştırın veya `LICENSE_SERVER_URL` kullanın
3. Hak listesinde modülün göründüğünü doğrulayın
4. Paneli yenileyin; menü öğeleri açılır

## Panelde nerede?

- **Arayüz yolları:** {$ui}
- **API önekleri:** `{$api}`

## Tipik iş akışı

{$mod['description']}

Modül aktif olduktan sonra sol menüden ilgili bölüme girin. Menü görünmüyorsa hub listesinde `{$key}` olduğunu ve önbelleğin temizlendiğini kontrol edin.

## İlgili sayfalar

- [Platform yetenekleri](/docs/platform-features)
- [Lisans aktivasyonu](/docs/license-activation)
MD;
    }

    private static function licensingHubContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Licensing hub\n\n| Page | Description |\n| --- | --- |\n| [License activation](/docs/license-activation) | Paste keys, renewal |\n| [Community vs Pro](/docs/community-vs-pro) | Quotas and features |\n| [License server hub](/docs/license-server-hub) | Central validation API |"
            : "## Lisans merkezi\n\n| Sayfa | Açıklama |\n| --- | --- |\n| [Lisans aktivasyonu](/docs/license-activation) | Anahtar girme, yenileme |\n| [Community ve Pro](/docs/community-vs-pro) | Kota ve özellikler |\n| [Lisans sunucusu](/docs/license-server-hub) | Merkezi doğrulama API |";
    }

    private static function licenseActivationContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Activate a license\n\n1. Open **Panel → License** (or **Settings → License**)\n2. Paste the key received by email after checkout\n3. Save — the panel calls the hub if `LICENSE_SERVER_URL` is set\n4. Enabled **modules** and **site limits** apply immediately\n\n## Regenerate / rotate\n\nUse the hub admin (panelze.com) to regenerate keys if compromised. Update the panel within the grace period documented in your contract."
            : "## Lisans aktivasyonu\n\n1. **Panel → Lisans** ekranını açın\n2. Ödeme sonrası e-postadaki anahtarı yapıştırın\n3. Kaydedin — `LICENSE_SERVER_URL` varsa hub çağrılır\n4. **Modül** ve **site limitleri** hemen uygulanır\n\n## Yenileme / rotate\n\nAnahtar sızdıysa panelze.com admin üzerinden yenileyin. Panelde güncel anahtarı grace süresi içinde girin.";
    }

    private static function communityVsProContent(string $locale): string
    {
        $community = implode("\n", array_map(fn ($f) => '- '.$f, PanelFeatureCatalog::communityPlanFeatures($locale)));
        $pro = implode("\n", array_map(fn ($f) => '- '.$f, PanelFeatureCatalog::proPlanFeatures($locale)));

        return $locale === 'en'
            ? "## Community (Freemium)\n\n{$community}\n\n## Pro license\n\n{$pro}\n\nExact numbers may change — authoritative limits live in your license record on the hub."
            : "## Community (Freemium)\n\n{$community}\n\n## Pro lisans\n\n{$pro}\n\nKesin rakamlar değişebilir; bağlayıcı limitler hub’daki lisans kaydınızdadır.";
    }

    private static function licenseHubContent(string $locale): string
    {
        if ($locale === 'en') {
            return <<<'MD'
## Central license hub (panelze.com)

Customer panels validate keys against:

```
POST https://panelze.com/api/license/validate
{"key":"hv_..."}
```

Set in panel `.env`:

```
LICENSE_SERVER_URL=https://panelze.com
LICENSE_SERVER_API_SECRET=<matches hub>
```

Admins manage customers, products, modules, and keys in **panelze.com/admin**.

See also the in-admin **License summary** page for API endpoints and update URLs.
MD;
        }

        return <<<'MD'
## Merkezi lisans hub (panelze.com)

Müşteri panelleri anahtarı şu uç noktada doğrular:

```
POST https://panelze.com/api/license/validate
{"key":"hv_..."}
```

Panel `.env`:

```
LICENSE_SERVER_URL=https://panelze.com
LICENSE_SERVER_API_SECRET=<hub ile aynı>
```

Müşteri, ürün, modül ve anahtarlar **panelze.com/admin** üzerinden yönetilir.

API uç noktaları için admin **Lisans özeti** sayfasına bakın.
MD;
    }

    private static function integrationsHubContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Integrations\n\n| Guide | Topic |\n| --- | --- |\n| [Google Drive backups](/docs/google-drive-backups) | OAuth via panelze.com |\n| [Payments](/docs/payments-stripe-paytr) | Stripe checkout on landing + panel module |"
            : "## Entegrasyonlar\n\n| Rehber | Konu |\n| --- | --- |\n| [Google Drive yedekleme](/docs/google-drive-backups) | panelze.com OAuth |\n| [Ödeme](/docs/payments-stripe-paytr) | Landing Stripe/PayTR + panel modülü |";
    }

    private static function googleDriveContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Google Drive (backups_pro)\n\n1. **panelze.com admin → Integrations** — enter Google OAuth client ID/secret\n2. Redirect URI: `https://panelze.com/backups/google-callback`\n3. On the customer panel with `backups_pro`: **Backups → Connect Google Drive**\n4. OAuth returns via panelze.com; tokens are forwarded to the panel\n\nNo per-server `.env` OAuth keys required when using the central hub."
            : "## Google Drive (backups_pro)\n\n1. **panelze.com admin → Entegrasyonlar** — Google OAuth client ID/secret girin\n2. Yönlendirme URI: `https://panelze.com/backups/google-callback`\n3. `backups_pro` açık müşteri panelinde: **Yedekler → Google Drive bağla**\n4. OAuth panelze.com üzerinden döner; token panele iletilir\n\nMerkezi hub kullanıldığında sunucu başına `.env` OAuth anahtarı gerekmez.";
    }

    private static function paymentsContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Payments\n\n**Landing (panelze.com):** Stripe and PayTR checkout for license purchases — configured in admin **Payment methods**.\n\n**In-panel (`stripe_billing` module):** Customers upgrade to Pro via Stripe inside their own panel.\n\nSee [License activation](/docs/license-activation) after successful payment."
            : "## Ödemeler\n\n**Landing (panelze.com):** Lisans satın alma için Stripe ve PayTR — admin **Ödeme yöntemleri**.\n\n**Panel içi (`stripe_billing` modülü):** Müşteriler kendi panelinden Pro’ya Stripe ile yükselir.\n\nÖdeme sonrası [Lisans aktivasyonu](/docs/license-activation).";
    }

    private static function troubleshootingHubContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Troubleshooting\n\n| Page | Focus |\n| --- | --- |\n| [Common issues](/docs/common-issues) | Engine, SSL, DNS, license |\n| [Logs and health](/docs/logs-and-health) | Where to look when things break |"
            : "## Sorun giderme\n\n| Sayfa | Odak |\n| --- | --- |\n| [Sık sorunlar](/docs/common-issues) | Engine, SSL, DNS, lisans |\n| [Loglar ve sağlık](/docs/logs-and-health) | Arıza anında bakılacak yerler |";
    }

    private static function commonIssuesContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Common issues\n\n| Symptom | Likely cause | Fix |\n| --- | --- | --- |\n| Site create fails | `ENGINE_*` mismatch | Align panel `.env` with Engine config |\n| SSL won't issue | DNS / port 80 blocked | Fix A record; open firewall |\n| License invalid | Wrong key or hub URL | Check `LICENSE_SERVER_URL` and secret |\n| phpMyAdmin SSO missing | Module not entitled | Upgrade to Pro with `phpmyadmin_sso` |\n| Panel 502 | PHP-FPM or Nginx down | `systemctl status` nginx/php-fpm |"
            : "## Sık sorunlar\n\n| Belirti | Olası neden | Çözüm |\n| --- | --- | --- |\n| Site oluşmuyor | `ENGINE_*` uyumsuz | Panel `.env` ile Engine eşleştirin |\n| SSL çıkmıyor | DNS / 80 kapalı | A kaydı; firewall |\n| Lisans geçersiz | Yanlış anahtar veya hub | `LICENSE_SERVER_URL` ve secret |\n| phpMyAdmin SSO yok | Modül yok | Pro + `phpmyadmin_sso` |\n| Panel 502 | PHP-FPM / Nginx | `systemctl status` |";
    }

    private static function logsHealthContent(string $locale): string
    {
        return $locale === 'en'
            ? "## Logs\n\n| Source | Path / command |\n| --- | --- |\n| Panel Laravel | `storage/logs/laravel.log` |\n| Engine | `journalctl -u panelze-engine -f` |\n| Nginx | `/var/log/nginx/error.log` |\n\n## Health\n\n```bash\ncurl -s https://your-panel.example/api/health\n```\n\nExpect JSON containing `\"status\":\"ok\"`. Run after every upgrade or Engine restart."
            : "## Loglar\n\n| Kaynak | Yol / komut |\n| --- | --- |\n| Panel Laravel | `storage/logs/laravel.log` |\n| Engine | `journalctl -u panelze-engine -f` |\n| Nginx | `/var/log/nginx/error.log` |\n\n## Sağlık\n\n```bash\ncurl -s https://panel-adresiniz/api/health\n```\n\nJSON içinde `\"status\":\"ok\"` bekleyin. Her yükseltme veya Engine yeniden başlatmasından sonra çalıştırın.";
    }
}
