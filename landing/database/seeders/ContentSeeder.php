<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Plan;
use App\Models\SitePage;
use App\Support\PanelFeatureCatalog;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        SitePage::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'setup'],
            [
                'title' => 'Kurulum rehberi',
                'meta_description' => 'Panelze Engine ve panel kurulumu: ön koşullar, güvenlik, ortam değişkenleri ve doğrulama adımları.',
                'is_published' => true,
                'sort_order' => 10,
                'content' => <<<'MD'
## Bu rehberde neler var?

Panelze yığını **iki ana parçadan** oluşur ve üretimde birlikte çalışması gerekir:

| Bileşen | Rol |
| --- | --- |
| **Panelze Engine** | Sunucuda Nginx, PHP-FPM, sertifika ve site düzeyi işlemleri yürüten servis (genelde `127.0.0.1:9090` gibi bir adresten API dinler). |
| **Panel (Laravel)** | Tarayıcıdan yönetim, kullanıcı/rol, lisans ve Engine’e giden API çağrıları. |

Bu sayfa **genel kurulum akışını** özetler; mimari ve ürün özellikleri için [dokümantasyon](/docs) altındaki [Mimari](/docs/architecture) ve [Panelze yetenekleri](/docs/platform-features) sayfalarına bakın.

---

## Ön koşullar

### Sunucu ve sistem

- **İşletim sistemi:** Temiz veya bakımlı bir **Ubuntu 22.04 LTS** önerilir; ekibiniz başka bir LTS dağıtımı onayladıysa ona uygun paket adlarını kullanın.
- **Donanım (kılavuz):** Küçük ekipler için **2 vCPU / 4 GB RAM** genelde yeterli başlangıç değeridir; çok sayıda site veya yoğun PHP iş yükünde kaynakları artırın.
- **Erişim:** `root` veya güvenilir **sudo** yetkisi; uzak SSH için parola yerine **anahtar tabanlı giriş** tercih edin.
- **Saat ve DNS:** Sunucu saatinin doğru olması (NTP); üretim alan adlarınızın **A/AAAA** kayıtları sunucunuzu göstermeli (Let’s Encrypt ve canlı trafik için).

### Güvenlik (kurulum öncesi)

- Sunucuda yalnızca ihtiyaç duyulan portları açın (başlangıçta genelde **22**, **80**, **443**; paneli ayrı bir porttan yayınlıyorsanız onu da tanımlayın).
- Mümkünse paneli yalnızca **VPN**, sabit IP veya **geçici SSH tüneli** üzerinden erişilebilir yapın; en azından yönetim hesaplarında **2FA** ve güçlü oturum politikası kullanın.
- Kurulumdan önce bir **snapshot / yedek** alın; üzerinde önemli veri olan mevcut sunucuları “üstüne yazmadan” önce yedek bulundurun.

---

## Hızlı kurulum

Aşağıdaki **Güncel kurulum komutları** bölümünde deploy betikleriyle uyumlu tüm komutlar listelenir (tek satır, Community, Pro, elle kurulum, güncelleme ve onarım).

> **Üretim:** Betiği çalıştırmadan önce imza / checksum doğrulaması ve betik içeriğinin incelemesi şart sayılmalıdır. Test ortamında önce deneyin. Komutları yalnızca Debian/Ubuntu VPS üzerinde root veya sudo ile çalıştırın.

Kurulum betiği tipik olarak şunları yapar: `git` ile `/var/www/panelze` altına kodu çeker, `deploy/bootstrap/install-production.sh` ile Nginx, PHP, MariaDB, Engine derlemesi ve frontend build çalıştırır. İlk yönetici bilgisi `/root/panelze-admin-login.txt` dosyasına yazılır.

---

## Panel ortam değişkenleri (Engine bağlantısı)

Panel deposundaki `.env` dosyasında Engine ile güvenli iletişim için tipik olarak şu alanlar kullanılır:

- `ENGINE_API_URL` — Engine API taban adresi (örn. `http://127.0.0.1:9090`).
- `ENGINE_INTERNAL_KEY` — Engine ile panel arasında paylaşılan dahili anahtar.
- `ENGINE_API_SECRET` — İmzalı istekler ve web terminal JWT gibi akışlar için Engine `security` yapılandırmasıyla eşleşmelidir.

Bu değerler, aynı sunucudaki **Engine yapılandırması** ile birebir uyumlu olmalı; aksi halde site oluşturma, SSL veya terminal işlemleri başarısız olur.

Lisanslama için `LICENSE_SERVER_URL`, `LICENSE_KEY` vb. alanlar kullanılabilir; birçok kurulumda anahtar **panel içindeki lisans ekranından** girilir.

---

## Kurulum sonrası kontrol listesi

1. Panel ön yüzüne gidin ve ilk **yönetici** hesabını oluşturun (veya dağıtımınızdaki ilk oturum adımını tamamlayın).
2. HTTP(S) sonlandırıcıyı doğrulayın; üretimde **HTTPS zorunlu** olmalı.
3. Engine–panel bağlantısını test edin (ör. staging alan adıyla site açma veya panel üzerinden `GET /api/health` — yanıtta `status: ok` içeren bir JSON beklenir).
4. İlk üretim trafiğini açmadan önce **test subdomain** veya düşük riskli alan adıyla DNS, sertifika ve PHP sürümünü doğrulayın.
5. Yedekleme hedeflerini ve güncelleme planını (Engine + panel) netleştirin.

Sorun giderme: firewall, yanlış `ENGINE_*` değerleri, DNS yayılımı ve saat kayması en sık kök nedenlerdir. [Blog](/blog) ve ana sayfadaki [SSS](/#faq) bölümüne de göz atın.
MD
            ]
        );

        SitePage::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'setup'],
            [
                'title' => 'Installation guide',
                'meta_description' => 'Install Panelze Engine and panel: prerequisites, hardening, environment variables, and post-install verification.',
                'is_published' => true,
                'sort_order' => 10,
                'content' => <<<'MD'
## What this guide covers

The Panelze stack has **two cooperating parts** that must be installed and configured together:

| Component | Role |
| --- | --- |
| **Panelze Engine** | Runs on the server and executes changes for Nginx, PHP-FPM, certificates, and per-site operations (typically exposes an HTTP API, e.g. on `127.0.0.1:9090`). |
| **Panel (Laravel)** | Browser UI, user/role management, licensing, and authenticated calls into the Engine. |

This page walks through the **end-to-end install flow**. For deeper architecture and product depth, read [Architecture](/docs/architecture) and [Platform capabilities](/docs/platform-features) under [Documentation](/docs).

---

## Prerequisites

### Server baseline

- **OS:** A clean, patched **Ubuntu 22.04 LTS** is recommended; other LTS distros are fine if your team already standardised on them (adjust package names and service units accordingly).
- **Sizing (rule of thumb):** **2 vCPU / 4 GB RAM** is a reasonable starting point for small fleets; increase CPU/RAM for heavy PHP workloads or very large numbers of sites.
- **Access:** `root` or passwordless **sudo**; prefer **SSH keys** over passwords for remote administration.
- **Time & DNS:** Accurate system time (NTP); production hostnames must resolve to this server (**A/AAAA**) before you rely on Let’s Encrypt and live traffic.

### Security before you install

- Open only required ports at the edge (typically **22**, **80**, **443**, plus whatever port serves the panel if not behind 443).
- Where practical, restrict the panel to a **VPN**, allow-listed IPs, or short-lived **SSH tunnels**; enforce **2FA** and strong session policy on admin-class accounts.
- Take a **snapshot or offline backup** before bootstrap scripts alter system packages or services.

---

## Quick install

Use the **Current install commands** section below — it lists every supported path (one-liner, Community, Pro, manual git clone, updates, and repair) kept in sync with `deploy/` scripts.

> **Production:** Treat every `curl | bash` as privileged code execution — verify checksums / signatures and review the script before it touches production. Always pilot in staging on Debian/Ubuntu with root or sudo.

The installer typically clones into `/var/www/panelze`, then runs `deploy/bootstrap/install-production.sh` (Nginx, PHP, MariaDB, Engine build, frontend). First admin credentials are written to `/root/panelze-admin-login.txt`.

---

## Panel environment (Engine linkage)

In the panel’s `.env`, the following variables commonly bind the UI to the Engine (names are illustrative but match the project’s layout):

- `ENGINE_API_URL` — Base URL for Engine API calls (e.g. `http://127.0.0.1:9090`).
- `ENGINE_INTERNAL_KEY` — Shared internal key negotiated between Engine and panel.
- `ENGINE_API_SECRET` — Must align with Engine `security` settings for signed flows (e.g. web terminal JWT).

If any of these diverge from the **live Engine configuration**, provisioning, TLS, or terminal sessions will fail mysteriously.

Licensing may involve `LICENSE_SERVER_URL`, `LICENSE_KEY`, etc.; many deployments paste the key in the **in-panel license** screen instead of keeping keys only in `.env`.

---

## Post-install checklist

1. Open the panel, complete bootstrap, and create (or import) the first **administrator** account.
2. Terminate TLS correctly at Nginx/Apache; production user traffic should be **HTTPS-only**.
3. Prove Engine connectivity with a harmless action — e.g. create a **staging site**, issue a certificate, or call `GET /api/health` on the panel (`status` should be `ok` in JSON).
4. Before production cutover, validate DNS, TLS, and PHP versions on a **throwaway subdomain**.
5. Configure backup targets/schedules and document how you will **roll Engine and panel updates**.

Troubleshooting tips: firewall rules, typoed `ENGINE_*` values, DNS/TTL drift, and clock skew are the usual culprits. See the [blog](/blog), the landing [FAQ](/#faq), and nested docs for next steps.
MD
            ]
        );

        SitePage::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'pricing'],
            [
                'title' => 'Fiyatlandırma özeti',
                'meta_description' => 'Freemium, Pro ve Vendor katmanları; limitler, lisans ve ödeme akışı özeti.',
                'is_published' => true,
                'sort_order' => 20,
                'content' => <<<'MD'
Bu metin, **fiyatlandırma** sayfasındaki giriş bölümünü besler. Aşağıdaki plan kartları ise yönetim panelinde tanımlı kayıtlardan **otomatik üretilir**; buradaki kopya ürün yönünü özetler.

## Planların anlamı

- **Freemium** — Tek sunucu, temel site/domain/SSL/terminal akışları ve makul kota ile pilot veya küçük iş yükleri için. Ücret alınmadan başlarsınız; yükseltme aynı panel üzerinden yapılır.
- **Pro lisans** — Ajanslar, yüksek trafik veya sıkı SLA beklentisi olan müşteriler için genişletilmiş limitler, gelişmiş izleme ve öncelikli destek sütunları (kart üzerindeki maddeler veritabanından gelir).
- **Vendor / White-label** — Kendi markanızla hizmet vermek, özel fiyat, hukuki çerçeve ve yol haritası ortaklığı için satış ekibiyle **kurumsal teklif** üzerinden ilerlenir.

## Lisans ve ödeme

- Çevrimiçi ödeme **Stripe** ile yapılabilir; başarılı işlemden sonra lisans anahtarı e-posta ile iletilir.
- Anahtar çoğu zaman **panel → lisans** ekranına yapıştırılır; merkezi doğrulama için `LICENSE_SERVER_URL` yapılandırması kullanılabilir.

**Kesin sayısal limitler** (site adedi, yedek saklama, API hızı vb.) paneldeki **plan / lisans** kayıtlarında tutulur; bu sayfadaki rakamlar yalnızca özet niteliğindedir.
MD
            ]
        );

        SitePage::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'pricing'],
            [
                'title' => 'Pricing overview',
                'meta_description' => 'Freemium, Pro, and Vendor tiers; how cards, licensing, and Stripe checkout fit together.',
                'is_published' => true,
                'sort_order' => 20,
                'content' => <<<'MD'
This copy powers the introductory blurb on the public **pricing** page. Feature bullets on each card are generated from the database rows managed in the landing admin—what you see here is narrative context.

## How the tiers differ

- **Freemium** — One server, core hosting workflows (sites, TLS, databases, limited observability) with conservative quotas. Zero licence fee to start; upgrades keep the same panel tenant.
- **Pro licence** — Higher ceilings for agencies and demanding workloads: richer monitoring, security profiles, and support tiers (exact bullets pull from the `plans` table).
- **Vendor / white-label** — Brand packaging, custom commercials, and roadmap partnership. Reach sales for an enterprise quote when you resell Panelze to your own customers.

## Licensing & payments

- Card checkout can run through **Stripe**; successful orders trigger transactional email with licence material.
- Keys are usually pasted into the in-panel **License** screen. Large deployments can pin a central hub via `LICENSE_SERVER_URL`.

Authoritative numeric limits (sites, backup retention, API throttles) always live beside the licensing module—treat marketing tables as summaries, not contracts.
MD
            ]
        );

        $this->call(DocumentationSeeder::class);

        $catHostingTr = BlogCategory::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'hosting-migration'],
            [
                'name' => 'Hosting ve geçiş',
                'meta_title' => 'Hosting ve geçiş — Panelze blog',
                'meta_description' => 'Paylaşımlı hostingden çıkış, sunucu taşıma ve panel geçişi üzerine yazılar.',
                'sort_order' => 10,
            ]
        );

        $catHostingEn = BlogCategory::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'hosting-migration'],
            [
                'name' => 'Hosting & migration',
                'meta_title' => 'Hosting & migration — Panelze blog',
                'meta_description' => 'Moving off shared hosting, server migrations, and panel transitions.',
                'sort_order' => 10,
            ]
        );

        $catSecurityTr = BlogCategory::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'security'],
            [
                'name' => 'Güvenlik',
                'meta_title' => 'Güvenlik — Panelze blog',
                'meta_description' => 'Panel ve sunucu güvenliği, erişim ve sertifika konuları.',
                'sort_order' => 20,
            ]
        );

        $catSecurityEn = BlogCategory::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'security'],
            [
                'name' => 'Security',
                'meta_title' => 'Security — Panelze blog',
                'meta_description' => 'Panel and server security, access control, and certificates.',
                'sort_order' => 20,
            ]
        );

        $catScaleTr = BlogCategory::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'scaling'],
            [
                'name' => 'Ölçeklendirme',
                'meta_title' => 'Ölçeklendirme ve mimari — Panelze blog',
                'meta_description' => 'Tek sunucudan çoklu düzene geçiş ve mimari notları.',
                'sort_order' => 30,
            ]
        );

        $catScaleEn = BlogCategory::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'scaling'],
            [
                'name' => 'Scaling',
                'meta_title' => 'Scaling & architecture — Panelze blog',
                'meta_description' => 'Growing from one server to multi-node setups.',
                'sort_order' => 30,
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'from-shared-hosting'],
            [
                'blog_category_id' => $catHostingTr->id,
                'title' => 'Shared hosting’den kendi panelime',
                'excerpt' => 'Klasik paylaşımlı hostingden çıkıp kendi sunucunuzda Panelze ile nasıl ilerlersiniz?',
                'is_published' => true,
                'published_at' => now()->subDays(5),
                'content' => <<<'MD'
Paylaşımlı hosting uzun yıllar işinizi görür; ta ki tek panelden onlarca siteyi yönetme ihtiyacı doğana kadar.

## Geçiş stratejisi

1. **DNS TTL** düşürün; taşıma günü kesintiyi azaltır.
2. Veritabanını **mysqldump** veya panel araçlarıyla alın.
3. Dosyaları **rsync** ile senkronize edin.
4. Panelze’de site sihirbazını çalıştırıp SSL’i doğrulayın.

Küçük projelerde önce staging subdomain ile test etmek riski ciddi şekilde azaltır.
MD
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'from-shared-hosting'],
            [
                'blog_category_id' => $catHostingEn->id,
                'title' => 'From shared hosting to your own panel',
                'excerpt' => 'How to move from classic shared hosting to Panelze on your own server.',
                'is_published' => true,
                'published_at' => now()->subDays(5),
                'content' => <<<'MD'
Shared hosting works for years — until you need to run many sites from one panel.

## Migration strategy

1. Lower **DNS TTL** to reduce cutover pain.
2. Export the database with **mysqldump** or your tools.
3. Sync files with **rsync**.
4. Run the Panelze site wizard and verify TLS.

For smaller projects, test on a staging subdomain first.
MD
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'panel-security-basics'],
            [
                'blog_category_id' => $catSecurityTr->id,
                'title' => 'Panel güvenliğinde temel hatalar',
                'excerpt' => 'Yönetim arayüzünü internete açarken sık yapılan hatalar ve pratik önlemler.',
                'is_published' => true,
                'published_at' => now()->subDays(3),
                'content' => <<<'MD'
Panel URL’sini herkese açık bırakmak yerine:

- **İki faktörlü doğrulama** kullanın
- Yönetim yolunu **rate limit** ile koruyun
- Varsayılan portları değiştirin veya **VPN** arkasına alın

Panelze yönetim hesapları için güçlü şifre politikası ve oturum süresi sınırları önerilir.
MD
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'panel-security-basics'],
            [
                'blog_category_id' => $catSecurityEn->id,
                'title' => 'Common panel security mistakes',
                'excerpt' => 'Typical pitfalls when exposing an admin UI to the internet — and practical fixes.',
                'is_published' => true,
                'published_at' => now()->subDays(3),
                'content' => <<<'MD'
Before leaving the panel URL wide open:

- Enable **two-factor authentication**
- Protect admin routes with **rate limiting**
- Change default ports or place the panel behind a **VPN**

Strong password policy and session limits are recommended for Panelze admin accounts.
MD
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'tr', 'slug' => 'single-server-to-cluster'],
            [
                'blog_category_id' => $catScaleTr->id,
                'title' => 'Tek sunucudan çoklu cluster’a',
                'excerpt' => 'Büyüdükçe mimariyi nasıl parçalayabilirsiniz?',
                'is_published' => true,
                'published_at' => now()->subDay(),
                'content' => <<<'MD'
İlk aşamada tek sunucu yeterlidir. Trafik ve ekip büyüdükçe:

- Veritabanını ayrı bir **DB host**’a taşıyın
- Statik ve medya için **CDN** ekleyin
- Engine örneklerini **load balancer** arkasında çoğaltın

Panelze bu aşamalarda aynı panel üzerinden çoklu sunucu yönetimini hedefler; roadmap’i ürün duyurularından takip edin.
MD
            ]
        );

        BlogPost::query()->updateOrCreate(
            ['locale' => 'en', 'slug' => 'single-server-to-cluster'],
            [
                'blog_category_id' => $catScaleEn->id,
                'title' => 'From one server to a multi-node setup',
                'excerpt' => 'How to split the architecture as you grow.',
                'is_published' => true,
                'published_at' => now()->subDay(),
                'content' => <<<'MD'
A single server is enough at first. As traffic and teams grow:

- Move the database to a dedicated **DB host**
- Add a **CDN** for static assets and media
- Run multiple Engine instances behind a **load balancer**

Panelze aims to manage multiple servers from the same panel over time — follow product announcements for the roadmap.
MD
            ]
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'freemium'],
            [
                'name' => 'Community (Freemium)',
                'subtitle' => 'Panelze v'.PanelFeatureCatalog::PANEL_VERSION.' — tek sunucuda çekirdek hosting',
                'price_label' => '₺0',
                'price_note' => '/ay',
                'sort_order' => 10,
                'is_featured' => false,
                'is_active' => true,
                'features' => PanelFeatureCatalog::communityPlanFeatures('tr'),
            ]
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'pro-lisans'],
            [
                'name' => 'Pro Lisans',
                'subtitle' => 'Tüm Pro modüller — panelde lisans anahtarı ile açılır',
                'price_label' => '₺?',
                'price_note' => '/ay · sunucu başına',
                'sort_order' => 20,
                'is_featured' => true,
                'is_active' => true,
                'features' => PanelFeatureCatalog::proPlanFeatures('tr'),
            ]
        );

        Plan::query()->updateOrCreate(
            ['slug' => 'vendor'],
            [
                'name' => 'Vendor / White-label',
                'subtitle' => 'Kendi markanızla sunmak isteyen paneller için',
                'price_label' => 'Özel',
                'price_note' => 'teklif',
                'sort_order' => 30,
                'is_featured' => false,
                'is_active' => true,
                'features' => [
                    'Özel fiyatlandırma ve SLA',
                    'Marka özelleştirme',
                    'Roadmap iş birliği',
                ],
            ]
        );
    }
}
