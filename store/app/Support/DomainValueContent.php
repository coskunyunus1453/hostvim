<?php

namespace App\Support;

/**
 * /domain-deger-sorgulama sayfasının SEO odaklı içeriği.
 */
class DomainValueContent
{
    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return [
            'hero' => [
                'badge' => 'AI destekli · Jenerik kelime analizi · Ücretsiz',
                'title' => 'Domain Değer Sorgulama',
                'subtitle' => 'haber.com, yemek.com gibi premium jenerik alan adlarını doğru fiyatlayın. Yapay zeka ve piyasa sözlüğü ile gerçeğe yakın tahmin.',
            ],
            'sections' => self::articleSections(),
        ];
    }

    /**
     * @return list<array{title: string, body: string}>
     */
    protected static function articleSections(): array
    {
        return [
            [
                'title' => 'Domain değeri nedir?',
                'body' => <<<'HTML'
<p><strong>Domain değeri</strong>, bir alan adının ikincil piyasada alıcıların ödemeye razı olduğu tahmini fiyattır. Kayıt ücreti ile piyasa değeri farklı kavramlardır: bir .com alan adını yıllık birkaç yüz liraya tescil edebilirsiniz; ancak kısa, akılda kalıcı ve ticari potansiyeli yüksek bir isim milyonlarca liraya satılabilir.</p>
<p>Hostvim <strong>domain değer sorgulama</strong> aracı, bu iki uç arasındaki konumu anlamanıza yardımcı olur. Girilen alan adı için algoritmamız onlarca faktörü tarar ve gerçeğe yakın bir tahmin aralığı üretir. Sonuç kesin satış fiyatı değildir; pazarlık, sektör ve alıcının ihtiyacına göre değişir. Yine de satış öncesi, portföy değerlendirmesi veya yatırım kararı için güçlü bir başlangıç noktasıdır.</p>
HTML,
            ],
            [
                'title' => 'Alan adı değerini etkileyen kriterler',
                'body' => <<<'HTML'
<h3>1. Uzantı (TLD)</h3>
<p><strong>.com</strong> hâlâ global ölçekte en değerli uzantıdır. Türkiye odaklı projelerde <strong>.com.tr</strong> ve <strong>.tr</strong> güven verir. Teknoloji ve yapay zeka projelerinde <strong>.io</strong>, <strong>.ai</strong> ve <strong>.dev</strong> prim yapar. Uzantı seçimi, hedef kitlenize ve sektörünüze göre değeri doğrudan etkiler.</p>

<h3>2. Uzunluk</h3>
<p>Genel kural basittir: <strong>ne kadar kısa, o kadar değerli</strong>. Tek veya iki harfli .com alan adları nadir ve çok yüksek fiyatlara ulaşır. 3–5 karakterli isimler güçlü marka adaylarıdır. 15 karakterin üzerindeki uzun isimler genelde düşük likiditeye sahiptir.</p>

<h3>3. Karakter kalitesi</h3>
<p>Yalnızca harf içeren, tire ve rakam barındırmayan alan adları daha temiz ve profesyonel algılanır. <em>best-shop-24.com</em> gibi yapılar, <em>bestshop.com</em> kadar değerli kabul edilmez. Hostvim değer motoru bu kalite farkını otomatik hesaba katar.</p>

<h3>4. Anahtar kelime ve ticari niyet</h3>
<p>İçinde <strong>shop, market, pay, hosting, ai, tech</strong> gibi ticari anahtar kelimeler barındıran alan adları, organik arama ve marka bilinirliği açısından avantajlıdır. Tam eşleşen anahtar kelime domainleri (exact match) özellikle e-ticaret ve lead generation projelerinde yüksek talep görür.</p>

<h3>5. Marka potansiyeli</h3>
<p>Telaffuz edilebilir, yazımı kolay ve akılda kalan isimler startup ve kurumsal alıcılar için caziptir. Sesli harf dağılımı dengeli, anlamlı veya anlamlı görünen kısa kelimeler marka değerini artırır.</p>

<h3>6. Kayıt yaşı ve geçmiş</h3>
<p>Uzun süredir kayıtlı, düzenli yenilenen alan adları arama motorları ve alıcılar nezdinde daha güvenilirdir. WHOIS verisiyle tespit edilen <strong>10 yılı aşkın</strong> kayıtlar değer tahminine olumlu yansır.</p>
HTML,
            ],
            [
                'title' => 'Hostvim domain değer sorgulama nasıl çalışır?',
                'body' => <<<'HTML'
<p>Sayfanın üstündeki arama kutusuna alan adınızı yazın ve <strong>Değerini Hesapla</strong> butonuna tıklayın. Sistemimiz şu adımları izler:</p>
<ol>
<li>Alan adını doğrular ve uzantı ile isim kısmını ayırır.</li>
<li>Her kriter için 0–100 arası puan üretir (uzantı, uzunluk, karakter, anahtar kelime, marka, yaş).</li>
<li>Kriterleri ağırlıklı olarak birleştirerek <strong>genel skor</strong> hesaplar.</li>
<li>Skoru piyasa verileriyle kalibre edilmiş baz fiyatlarla çarparak <strong>tahmini değer</strong> ve <strong>alt–üst aralık</strong> sunar.</li>
<li>Kayıtlı alan adlarında RDAP/WHOIS sorgusu ile yaş bilgisi eklenir.</li>
</ol>
<p>Sonuç ekranında her kriterin puanını ve kısa açıklamasını görürsünüz. Böylece değerin neden yüksek veya düşük çıktığını şeffaf biçimde anlayabilirsiniz.</p>
HTML,
            ],
            [
                'title' => 'Domain değerini artırmanın yolları',
                'body' => <<<'HTML'
<p>Mevcut bir alan adının değerini zamanla artırmak mümkündür:</p>
<ul>
<li><strong>Aktif web sitesi:</strong> Boş park sayfası yerine gerçek içerik ve trafik değer katar.</li>
<li><strong>SSL ve güven:</strong> HTTPS, düzenli yenileme ve temiz WHOIS kaydı güven sinyali verir.</li>
<li><strong>Marka oluşturma:</strong> Sosyal medya hesapları, logo ve tutarlı kullanım algıyı güçlendirir.</li>
<li><strong>Backlink profili:</strong> Kaliteli sitelerden gelen bağlantılar SEO değerini yükseltir.</li>
<li><strong>Doğru uzantı:</strong> Projenize uygun premium uzantı (.com, .com.tr) uzun vadede likiditeyi artırır.</li>
</ul>
<p>Satış öncesi alan adınızı Hostvim üzerinde değerlendirip, ardından <a href="/domain">domain sorgulama</a> sayfamızdan müsaitlik ve transfer seçeneklerini inceleyebilirsiniz.</p>
HTML,
            ],
            [
                'title' => 'Kimler domain değer sorgulaması kullanmalı?',
                'body' => <<<'HTML'
<p><strong>Domain yatırımcıları</strong> portföylerindeki alan adlarının güncel değerini takip eder. <strong>Girişimciler</strong> yeni marka ismi seçerken bütçe planlaması yapar. <strong>Ajanslar ve geliştiriciler</strong> müşterilerine satış öncesi rapor sunar. <strong>Alan adı sahipleri</strong> satış veya takas görüşmelerinde referans fiyat belirler.</p>
<p>Hostvim olarak kendi Panelze altyapımızla domain kayıt, transfer ve hosting hizmetlerini tek ekosistemde sunuyoruz. Değer sorgulaması sonrası alan adınızı kaydetmek veya mevcut sitenizi taşımak için <a href="/iletisim">destek ekibimiz</a> yanınızdadır.</p>
HTML,
            ],
            [
                'title' => 'Sık yapılan hatalar',
                'body' => <<<'HTML'
<p>Domain değerlendirmesinde en yaygın hatalar şunlardır:</p>
<ul>
<li><strong>Kayıt fiyatı ile piyasa değerini karıştırmak:</strong> Yıllık 500 ₺'ye alınan bir alan adı 50.000 ₺'ye satılabilir — veya hiç satılamayabilir.</li>
<li><strong>Sadece uzunluğa bakmak:</strong> Kısa ama anlamsız veya zor telaffuz edilen isimler her zaman değerli değildir.</li>
<li><strong>Tek teklife güvenmek:</strong> Profesyonel değerleme için birden fazla kaynak ve pazar yeri verisi karşılaştırılmalıdır.</li>
<li><strong>Telif ve marka ihlali:</strong> Büyük markalara benzeyen alan adları yasal risk taşır; değerleri yapay şişkin olabilir.</li>
</ul>
<p>Hostvim aracı bu tuzakların farkında olarak dengeli, gerçekçi tahminler üretmeyi hedefler. Yine de yüksek tutarlı işlemlerde uzman görüşü almanızı öneririz.</p>
HTML,
            ],
        ];
    }
}
