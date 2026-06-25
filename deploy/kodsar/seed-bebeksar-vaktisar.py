#!/usr/bin/env python3
"""Insert Bebeksar and Vaktisar products into kodsar DB (run on server)."""
import subprocess
import time

TS = int(time.time())

PRODUCTS = [
    {
        "category": {
            "name": "Bebek & Ebeveyn Uygulamaları",
            "slug": "bebek-ebeveyn-uygulamalari",
            "description": "Bebek izleme, ebeveyn takip ve bebek bakım mobil uygulama kaynak kodları",
        },
        "images": {
            "featured_src": "/tmp/app_icon.jpg",
            "featured_name": f"bebeksar-featured-{TS}.jpg",
            "gallery": [
                ("/tmp/Icon-App-1024x1024@1x.png", f"bebeksar-icon-{TS}.png", "Bebeksar uygulama ikonu"),
            ],
        },
        "product": {
            "name": "Bebeksar - Flutter Bebek İzleme Kamerası Kaynak Kodu",
            "slug": "bebeksar-flutter-bebek-izleme-kamerasi-kaynak-kodu",
            "sku": "BEBEKSARV1",
            "price": "9999.00",
            "discount_price": "8499.00",
            "short_description": "Telefonu bebek kamerası veya ebeveyn monitörü olarak kullanan WebRTC + QR kod tabanlı Flutter uygulaması. Bebek günlüğü, yüz takibi ve AdMob dahil. Sürüm 1.0.0.",
            "meta_title": "Bebeksar Flutter Bebek İzleme Kamerası Kaynak Kodu | Kodsar",
            "meta_description": "Bebeksar: WebRTC canlı izleme, QR bağlantı, bebek günlüğü, uyku takibi, yüz algılama ve AdMob içeren Flutter bebek monitörü kaynak kodu.",
            "meta_keywords": "bebeksar, bebek kamerası, bebek monitörü, flutter kaynak kod, webrtc, ebeveyn uygulaması",
            "sort_order": 3,
            "description": """
<p><strong>Bebeksar</strong>, eski adıyla <em>Bebe Kamera</em>, telefonunuzu ek donanım gerektirmeden bebek izleme kamerasına veya ebeveyn monitörüne dönüştüren Flutter tabanlı profesyonel bir mobil uygulama kaynak kodudur.</p>
<p><br></p>
<p><strong>Canlı İzleme (WebRTC)</strong></p>
<ul>
<li><strong>Kamera modu</strong> — Telefonu yayın kamerası olarak kullanın, QR kod ile oda kodu paylaşın</li>
<li><strong>Ebeveyn modu</strong> — QR kod tarayarak canlı video ve ses akışına bağlanın</li>
<li><strong>MQTT sinyalizasyon</strong> — WebRTC bağlantı kurulumu için hafif MQTT altyapısı</li>
<li><strong>Anlık görüntü</strong> — Canlı yayın sırasında ekran görüntüsünü galeriye kaydetme</li>
<li><strong>Wakelock</strong> — Kamera modunda ekranın kapanmasını engelleme</li>
</ul>
<p><br></p>
<p><strong>Bebek Günlüğü &amp; Takip</strong></p>
<ul>
<li>Uyku başlangıç/bitiş kaydı ve günlük uyku özeti</li>
<li>Emzirme / beslenme kaydı</li>
<li>Bez değişimi kaydı</li>
<li>Serbest not ekleme</li>
<li>Canlı izleme ekranından uyku kaydı önerisi</li>
</ul>
<p><br></p>
<p><strong>Yüz Algılama İzleme</strong></p>
<ul>
<li>Google ML Kit yüz algılama ile bebek yüzü kaybolduğunda uyarı</li>
<li>Canlı izleme sırasında titreşimli bildirim</li>
</ul>
<p><br></p>
<p><strong>Rahatlatıcı Sesler</strong></p>
<ul>
<li>Ninni, yağmur, beyaz gürültü ve sakinleştirici sesler</li>
<li>Kamera modunda arka planda çalma</li>
</ul>
<p><br></p>
<p><strong>Diğer Özellikler</strong></p>
<ul>
<li>Çoklu dil desteği (Türkçe, İngilizce, Arapça, Almanca ve daha fazlası)</li>
<li>Aydınlık / karanlık / sistem teması</li>
<li>Google AdMob banner ve native reklamlar</li>
<li>Material 3 arayüz, Provider state management</li>
<li>Android &amp; iOS — Sürüm <strong>1.0.0</strong></li>
</ul>
<p><br></p>
<p><strong>Satın Alma Sonrası</strong></p>
<p>Tam Flutter kaynak kodu teslim edilir. Kendi markanızla mağazalara yayınlayabilir, AdMob ile gelir elde edebilirsiniz.</p>
<p><br></p>
<p><em>Bebeksar markası ve mağaza hesapları dahil değildir; yalnızca kaynak kod lisansı satılmaktadır.</em></p>
""",
        },
    },
    {
        "category": {
            "name": "Dini & Namaz Uygulamaları",
            "slug": "dini-namaz-uygulamalari",
            "description": "Namaz vakitleri, kıble, Kur'an ve İslami içerik mobil uygulama kaynak kodları",
        },
        "images": {
            "featured_src": "/tmp/vaktisar_logo.png",
            "featured_name": f"vaktisar-featured-{TS}.png",
            "gallery": [
                ("/tmp/ic_launcher.png", f"vaktisar-icon-{TS}.png", "Vaktisar uygulama ikonu"),
            ],
        },
        "product": {
            "name": "Vaktisar - Flutter Namaz Vakitleri & İslami Yaşam Uygulaması Kaynak Kodu",
            "slug": "vaktisar-flutter-namaz-vakitleri-islami-yasam-uygulamasi-kaynak-kodu",
            "sku": "VAKTISARV1",
            "price": "14999.00",
            "discount_price": "12499.00",
            "short_description": "Namaz vakitleri, kıble pusulası, ezan bildirimleri, Kur'an, hatim, tesbih, radyo ve premium abonelik içeren kapsamlı Flutter uygulaması + PHP admin paneli. Sürüm 1.0.24.",
            "meta_title": "Vaktisar Flutter Namaz Vakitleri Uygulaması Kaynak Kodu | Kodsar",
            "meta_description": "Vaktisar: namaz vakitleri, kıble, ezan alarmı, Kur'an, hatim takibi, tesbih, soru-cevap, premium ve AdMob içeren Flutter + PHP admin panel kaynak kodu.",
            "meta_keywords": "vaktisar, namaz vakitleri, kıble, ezan, kuran uygulaması, flutter kaynak kod, islami uygulama",
            "sort_order": 4,
            "description": """
<p><strong>Vaktisar</strong>, namaz vakitlerinden Kur'an okumaya, hatim takibinden premium aboneliğe kadar geniş bir İslami yaşam ekosistemi sunan, Flutter ile geliştirilmiş kapsamlı bir mobil uygulama kaynak kodudur. PHP tabanlı admin paneli (dua.kodsar.com) ile birlikte teslim edilir.</p>
<p><br></p>
<p><strong>Namaz &amp; Vakitler</strong></p>
<ul>
<li>Günlük ve haftalık namaz vakitleri (Diyanet / konum bazlı)</li>
<li>Sonraki vakte geri sayım ve canlı güncelleme</li>
<li>Gelişmiş şehir / ilçe seçimi</li>
<li>Ezan sesi ve bildirim ayarları (Türkiye &amp; Dubai ezan sesleri)</li>
<li>Arka plan ezan planlayıcı (Android Alarm Manager)</li>
<li>Ana ekran widget desteği</li>
</ul>
<p><br></p>
<p><strong>Kıble &amp; Konum</strong></p>
<ul>
<li>Pusula ile kıble yönü göstergesi</li>
<li>GPS konum ve izin yönetimi</li>
</ul>
<p><br></p>
<p><strong>Kur'an &amp; Manevi İçerik</strong></p>
<ul>
<li>Kur'an sureleri, arama ve okuma ekranı</li>
<li>Hatim takibi ve ilerleme kaydı</li>
<li>Esma-ül Hüsna</li>
<li>İlm-i Göster (günlük ilim kartları)</li>
<li>Tesbih sayacı</li>
<li>Kişisel notlar ve hedefler</li>
<li>İslami radyo</li>
</ul>
<p><br></p>
<p><strong>Topluluk &amp; Eğlence</strong></p>
<ul>
<li>Soru-cevap modülü</li>
<li>İslami oyunlar bölümü</li>
<li>Boykot ürün listesi</li>
<li>Özellik talebi gönderme</li>
</ul>
<p><br></p>
<p><strong>Kullanıcı &amp; Premium</strong></p>
<ul>
<li>Kayıt, giriş, şifre sıfırlama</li>
<li>Premium abonelik (In-App Purchase)</li>
<li>Admin panelden premium özellik yönetimi</li>
<li>Profil, istatistikler, bildirim ayarları</li>
<li>Çoklu dil (API'den dinamik çeviri)</li>
<li>Aydınlık / karanlık tema (V4 modern arayüz)</li>
</ul>
<p><br></p>
<p><strong>Bildirim &amp; Senkronizasyon</strong></p>
<ul>
<li>Firebase Cloud Messaging push bildirimleri</li>
<li>Yerel bildirimler ve timezone desteği</li>
<li>SQLite offline veritabanı + API senkronizasyonu</li>
</ul>
<p><br></p>
<p><strong>Gelir Modeli</strong></p>
<ul>
<li>Google AdMob (banner, native, sayfa bazlı konumlandırma)</li>
<li>Premium abonelik ile reklamsız / ek özellikler</li>
<li>Admin panelden AdMob birim ID yönetimi</li>
</ul>
<p><br></p>
<p><strong>Teknik Altyapı</strong></p>
<ul>
<li><strong>Flutter</strong> mobil uygulama (SDK 3.4+) — Android &amp; iOS</li>
<li><strong>PHP admin paneli</strong> — kullanıcı, ayar, AdMob, branding yönetimi</li>
<li>Provider, SQLite, Firebase, audio_service, flutter_tts</li>
<li>Sürüm: <strong>1.0.24</strong> (production-ready)</li>
</ul>
<p><br></p>
<p><strong>Satın Alma Sonrası</strong></p>
<p>Flutter kaynak kodu + PHP admin panel kaynak kodu teslim edilir. Kendi domain'inizde yayınlayabilir, premium ve AdMob gelir modellerini aktif kullanabilirsiniz.</p>
<p><br></p>
<p><em>Vaktisar markası, Play Store hesabı ve canlı sunucu dahil değildir; yalnızca kaynak kod lisansı satılmaktadır.</em></p>
""",
        },
    },
]

STORAGE = "/var/www/hostvim/data/www/kodsar.com/public_html/storage/app/public/products"


def sql_escape(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "\\'")


def main():
    statements = []
    for item in PRODUCTS:
        cat = item["category"]
        prod = item["product"]
        imgs = item["images"]

        statements.append(
            f"""INSERT INTO categories (name, slug, description, parent_id, is_active, sort_order, created_at, updated_at)
SELECT '{sql_escape(cat['name'])}', '{cat['slug']}', '{sql_escape(cat['description'])}', 4, 1, {prod['sort_order']}, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = '{cat['slug']}');"""
        )
        statements.append(f"SET @cat_{cat['slug'].replace('-', '_')} = (SELECT id FROM categories WHERE slug = '{cat['slug']}' LIMIT 1);")

        feat_path = f"products/{imgs['featured_name']}"
        feat_url = f"https://kodsar.com/storage/{feat_path}"

        statements.append(
            f"""INSERT INTO products (
  name, slug, short_description, description, type,
  sku, price, discount_price, tax_rate,
  stock_quantity, manage_stock, stock_status,
  status, featured_image,
  meta_title, meta_description, meta_keywords,
  is_digital, download_limit, download_expiry_days,
  sort_order, is_featured, created_at, updated_at
) SELECT
  '{sql_escape(prod['name'])}',
  '{prod['slug']}',
  '{sql_escape(prod['short_description'])}',
  '{sql_escape(prod['description'].strip())}',
  'simple',
  '{prod['sku']}',
  {prod['price']},
  {prod['discount_price']},
  20.00,
  99, 0, 'in_stock',
  'published',
  '{feat_url}',
  '{sql_escape(prod['meta_title'])}',
  '{sql_escape(prod['meta_description'])}',
  '{prod['meta_keywords']}',
  1, 5, 365,
  {prod['sort_order']}, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug = '{prod['slug']}');"""
        )

        var = f"@prod_{prod['sku'].lower()}"
        statements.append(f"SET {var} = (SELECT id FROM products WHERE slug = '{prod['slug']}' LIMIT 1);")

        cat_var = f"@cat_{cat['slug'].replace('-', '_')}"
        statements.append(
            f"""INSERT INTO product_categories (product_id, category_id, is_primary, created_at, updated_at)
SELECT {var}, {cat_var}, 1, NOW(), NOW()
FROM DUAL
WHERE {var} IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM product_categories WHERE product_id = {var} AND category_id = {cat_var});"""
        )
        statements.append(
            f"""INSERT INTO product_categories (product_id, category_id, is_primary, created_at, updated_at)
SELECT {var}, 4, 0, NOW(), NOW()
FROM DUAL
WHERE {var} IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM product_categories WHERE product_id = {var} AND category_id = 4);"""
        )

        for idx, (_, fname, alt) in enumerate(imgs["gallery"], start=1):
            statements.append(
                f"""INSERT INTO product_images (product_id, image_path, alt_text, sort_order, created_at, updated_at)
SELECT {var}, 'products/{fname}', '{sql_escape(alt)}', {idx}, NOW(), NOW()
FROM DUAL
WHERE {var} IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM product_images WHERE product_id = {var} AND image_path = 'products/{fname}');"""
            )

    sql = "\n".join(statements)
    sql += "\nSELECT id, name, slug, price, discount_price FROM products WHERE slug IN ('bebeksar-flutter-bebek-izleme-kamerasi-kaynak-kodu', 'vaktisar-flutter-namaz-vakitleri-islami-yasam-uygulamasi-kaynak-kodu');\n"

    # Copy images on server
    shell = ["ssh", "-i", "/Users/coskunuygun/.ssh/hostvim_aapanel", "-o", "StrictHostKeyChecking=no", "root@207.180.237.13"]
    import shlex

    copy_cmds = []
    for item in PRODUCTS:
        imgs = item["images"]
        copy_cmds.append(f"cp {shlex.quote(imgs['featured_src'])} {STORAGE}/{imgs['featured_name']}")
        for src, fname, _ in imgs["gallery"]:
            copy_cmds.append(f"cp {shlex.quote(src)} {STORAGE}/{fname}")
    copy_cmds.append(f"chown www-data:www-data {STORAGE}/*{TS}* 2>/dev/null || true")
    copy_cmds.append(f"chmod 644 {STORAGE}/*{TS}* 2>/dev/null || true")

    remote = "\n".join(copy_cmds) + "\n"
    remote += f"mysql -u ps_1_tmtwuas5 -p'1453@Cskn' ps_1_kodsar <<'EOSQL'\n{sql}\nEOSQL\n"

    proc = subprocess.run(shell + ["bash -s"], input=remote, capture_output=True, text=True)
    print(proc.stdout)
    if proc.stderr:
        print(proc.stderr, file=__import__("sys").stderr)
    proc.check_returncode()


if __name__ == "__main__":
    main()
