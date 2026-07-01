<?php

namespace App\Support;

class DomainValueFaq
{
    /**
     * @return list<array{q: string, a: string}>
     */
    public static function all(): array
    {
        return [
            [
                'q' => 'Domain değer sorgulama nedir?',
                'a' => 'Domain değer sorgulama, bir alan adının tahmini piyasa değerini hesaplayan analiz aracıdır. Hostvim\'de uzantı, uzunluk, karakter kalitesi, anahtar kelime uyumu, marka potansiyeli ve kayıt yaşı gibi kriterler bir arada değerlendirilir.',
            ],
            [
                'q' => 'Tahmini değer ne kadar doğru?',
                'a' => 'Sonuçlar istatistiksel model ve sektör deneyimine dayalı tahmindir; kesin satış fiyatı garantisi vermez. Gerçek alım-satım fiyatı alıcı, satıcı ve piyasa koşullarına göre değişebilir. Aracımız size gerçeğe yakın bir referans aralığı sunar.',
            ],
            [
                'q' => 'Hangi kriterler değeri artırır?',
                'a' => 'Kısa .com uzantılı alan adları, yalnızca harf içeren yapılar, ticari anahtar kelimeler (.ai, shop, tech vb.), yüksek marka potansiyeli ve uzun kayıt geçmişi değeri yükseltir. Rakam, tire ve uzun isimler genelde değeri düşürür.',
            ],
            [
                'q' => 'Kayıtlı olmayan domain için de sorgulayabilir miyim?',
                'a' => 'Evet. Müsait alan adları için değer, marka ve geliştirme potansiyeline göre hesaplanır. Kayıtlı alan adlarında WHOIS verisiyle yaş kriteri de devreye girer.',
            ],
            [
                'q' => 'Domainimi satmak istiyorum, ne yapmalıyım?',
                'a' => 'Önce değer sorgulaması yaparak referans fiyat alın. Ardından Hostvim müşteri panelinden alan adınızı yönetebilir veya destek ekibimizle iletişime geçerek transfer ve satış süreçleri hakkında bilgi alabilirsiniz.',
            ],
            [
                'q' => 'Ücretsiz mi?',
                'a' => 'Evet. Hostvim domain değer sorgulama aracı tamamen ücretsizdir; günlük sorgu limiti kötüye kullanımı önlemek için uygulanır.',
            ],
        ];
    }
}
