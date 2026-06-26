# HostVim — Hosting & Domain Satış Platformu

Laravel 12 tabanlı, Filament 5 admin panelli hosting/VPS/VDS/domain satış sitesi.

## Gereksinimler

- PHP 8.2+ (Laravel 13 için PHP 8.3+ gerekir)
- Composer
- Node.js 18+
- MySQL / MariaDB veya SQLite (geliştirme)

## Kurulum

```bash
composer install
cp .env.example .env
php artisan key:generate

# MySQL için .env dosyasını düzenleyin:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=hostvim
# DB_USERNAME=root
# DB_PASSWORD=

php artisan migrate --seed
npm install && npm run build
php artisan serve
```

## Varsayılan Admin

- **URL:** `/admin`
- **E-posta:** coskunuygun@hotmail.com
- **Şifre:** `.env` içindeki `ADMIN_PASSWORD` (seed sonrası)

## SEO Özellikleri

- Dinamik **sitemap.xml** (`/sitemap.xml`)
- **robots.txt** (`/robots.txt`)
- Schema.org: Organization, WebSite, Product, Article, FAQ, BreadcrumbList
- Open Graph & Twitter Card meta etiketleri
- Breadcrumb navigasyon (görsel + yapılandırılmış veri)
- Admin → **SEO Ayarları** sayfasından tüm genel SEO ayarları
- Ürün, blog, sayfa ve kategori bazında meta/OG/noindex alanları

## Özellikler

### Admin Panel (`/admin`)
- **İçerik:** Sayfalar, blog, hero, özellikler, SSS, menüler, referanslar
- **Ürünler:** Kategoriler, paketler, fiyatlandırma, özellikler
- **Siparişler:** Sipariş takibi, ödeme durumu
- **Ödeme:** PayTR, iyzico, Havale/EFT yapılandırması
- **Ayarlar:** Site adı, iletişim, SEO, e-posta şablonları
- **Zengin metin editörü:** Tiptap tabanlı (sayfa, blog, e-posta)

### Ön Yüz
- Modern landing page (beyaz, koyu turuncu #C2410C, koyu yeşil #166534)
- Ürün katalog, sepet, checkout
- Blog, iletişim formu, dinamik menüler

### Ödeme Entegrasyonları
- **PayTR:** Kart ödemeleri (iframe)
- **iyzico:** Checkout form
- **Havale/EFT:** Banka talimatları admin panelinden düzenlenir

API anahtarlarını Admin → Ödeme Yöntemleri bölümünden girin.

## Geliştirme

```bash
npm run dev
php artisan serve
```

## Güvenlik Notları (Production)

- `APP_DEBUG=false` ve `APP_ENV=production` ayarlayın
- `ADMIN_PASSWORD` değerini mutlaka değiştirin
- `SESSION_ENCRYPT=true` ve HTTPS için `SESSION_SECURE_COOKIE=true`
- Ödeme callback URL'leri CSRF dışında bırakılmıştır (PayTR/iyzico gereksinimi)
- Sipariş sonuç sayfaları imzalı URL ile korunur (`signed` middleware)
