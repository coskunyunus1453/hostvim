<?php

namespace App\Support;

/**
 * Yüksek değerli jenerik (tek kelime) alan adları sözlüğü.
 * haber.com, yemek.com, news.com gibi premium domainler için baz USD değerleri.
 */
class DomainPremiumLexicon
{
    /**
     * Ultra premium — yüksek arama hacmi, sektör lideri potansiyeli.
     * Değerler: tipik piyasa bandının orta noktası (USD).
     *
     * @var array<string, int>
     */
    public const ULTRA = [
        // Türkçe jenerik
        'haber' => 1_500_000, 'yemek' => 1_200_000, 'para' => 1_400_000,
        'banka' => 1_300_000, 'kredi' => 900_000, 'sigorta' => 850_000,
        'emlak' => 1_100_000, 'araba' => 950_000, 'otomobil' => 700_000,
        'otel' => 800_000, 'tatil' => 750_000, 'turizm' => 700_000,
        'alisveris' => 650_000, 'magaza' => 600_000, 'market' => 700_000,
        'saglik' => 900_000, 'doktor' => 750_000, 'hastane' => 650_000,
        'hukuk' => 700_000, 'avukat' => 600_000, 'egitim' => 650_000,
        'oyun' => 800_000, 'spor' => 750_000, 'muzik' => 650_000,
        'film' => 700_000, 'video' => 850_000, 'foto' => 500_000,
        'moda' => 700_000, 'giyim' => 600_000, 'ayakkabi' => 550_000,
        'cocuk' => 600_000, 'bebek' => 650_000, 'evlilik' => 500_000,
        'borsa' => 900_000, 'altin' => 850_000, 'doviz' => 750_000,
        'kripto' => 950_000, 'bitcoin' => 1_000_000, 'casino' => 1_100_000,
        'bahis' => 900_000, 'kumar' => 700_000, 'porno' => 1_200_000,
        'sex' => 1_500_000, 'ask' => 600_000, 'ev' => 900_000,
        'kira' => 650_000, 'satilik' => 550_000, 'is' => 800_000,
        'isler' => 500_000, 'kariyer' => 550_000, 'cv' => 450_000,
        'bulut' => 600_000, 'hosting' => 550_000, 'domain' => 700_000,
        'mail' => 800_000, 'email' => 850_000, 'web' => 900_000,
        'mobil' => 650_000, 'app' => 750_000, 'yazilim' => 500_000,
        'teknoloji' => 550_000, 'bilgisayar' => 500_000, 'telefon' => 600_000,
        'enerji' => 550_000, 'elektrik' => 500_000, 'su' => 450_000,
        'gida' => 600_000, 'et' => 400_000, 'balik' => 450_000,
        'sehir' => 550_000, 'harita' => 500_000, 'ucak' => 650_000,
        'otel' => 800_000, 'villa' => 550_000, 'daire' => 500_000,
        // İngilizce jenerik (global premium)
        'news' => 2_000_000, 'food' => 1_500_000, 'money' => 1_800_000,
        'bank' => 1_700_000, 'shop' => 1_200_000, 'store' => 1_100_000,
        'hotel' => 1_300_000, 'travel' => 1_200_000, 'car' => 1_400_000,
        'cars' => 1_200_000, 'home' => 1_500_000, 'house' => 1_200_000,
        'job' => 1_300_000, 'jobs' => 1_400_000, 'work' => 900_000,
        'health' => 1_200_000, 'doctor' => 900_000, 'law' => 800_000,
        'lawyer' => 850_000, 'insurance' => 1_100_000, 'credit' => 950_000,
        'loan' => 900_000, 'invest' => 850_000, 'stock' => 900_000,
        'crypto' => 1_100_000, 'bitcoin' => 1_300_000, 'casino' => 1_500_000,
        'poker' => 1_000_000, 'game' => 1_000_000, 'games' => 1_100_000,
        'sport' => 900_000, 'music' => 900_000, 'movie' => 850_000,
        'film' => 800_000, 'video' => 1_000_000, 'photo' => 700_000,
        'buy' => 1_200_000, 'sell' => 1_100_000, 'pay' => 1_300_000,
        'mail' => 900_000, 'email' => 1_000_000, 'web' => 1_100_000,
        'cloud' => 950_000, 'data' => 900_000, 'ai' => 1_500_000,
        'app' => 1_000_000, 'tech' => 850_000, 'phone' => 800_000,
        'wine' => 700_000, 'beer' => 750_000, 'pizza' => 650_000,
        'coffee' => 700_000, 'sex' => 2_000_000, 'love' => 900_000,
        'dating' => 850_000, 'finance' => 1_000_000, 'business' => 900_000,
        'market' => 1_000_000, 'trade' => 850_000, 'sale' => 800_000,
        'rent' => 750_000, 'mortgage' => 900_000, 'debt' => 600_000,
        'tax' => 700_000, 'legal' => 650_000, 'medical' => 750_000,
        'dental' => 600_000, 'fitness' => 650_000, 'diet' => 600_000,
        'fashion' => 750_000, 'beauty' => 700_000, 'baby' => 800_000,
        'kids' => 750_000, 'pet' => 650_000, 'dog' => 600_000, 'cat' => 550_000,
        'flight' => 750_000, 'flights' => 800_000, 'ticket' => 700_000,
        'book' => 650_000, 'books' => 700_000, 'learn' => 600_000,
        'course' => 550_000, 'school' => 650_000, 'college' => 600_000,
    ];

    /**
     * Yüksek değerli — güçlü ticari anahtar kelime ama ultra değil.
     *
     * @var array<string, int>
     */
    public const HIGH = [
        'blog' => 120_000, 'forum' => 100_000, 'rehber' => 80_000,
        'inceleme' => 60_000, 'fiyat' => 90_000, 'kampanya' => 70_000,
        'indirim' => 85_000, 'kupon' => 75_000, 'hediye' => 65_000,
        'cicek' => 70_000, 'kitap' => 80_000, 'ders' => 75_000,
        'sinav' => 70_000, 'universite' => 90_000, 'lise' => 60_000,
        'anne' => 65_000, 'baba' => 60_000, 'aile' => 70_000,
        'dugun' => 75_000, 'organizasyon' => 55_000, 'dekorasyon' => 50_000,
        'mobilya' => 80_000, 'bahce' => 65_000, 'temizlik' => 55_000,
        'tamir' => 60_000, 'servis' => 70_000, 'nakliye' => 65_000,
        'kargo' => 80_000, 'teslimat' => 55_000, 'restoran' => 90_000,
        'cafe' => 85_000, 'kahve' => 75_000, 'cay' => 60_000,
        'pastane' => 55_000, 'firin' => 50_000, 'marketplace' => 100_000,
        'blog' => 120_000, 'wiki' => 90_000, 'search' => 150_000,
        'find' => 100_000, 'best' => 120_000, 'top' => 110_000,
        'free' => 130_000, 'cheap' => 90_000, 'deal' => 85_000,
        'review' => 80_000, 'guide' => 75_000, 'tips' => 60_000,
        'recipe' => 85_000, 'cook' => 70_000, 'chef' => 65_000,
        'gym' => 70_000, 'yoga' => 65_000, 'run' => 60_000,
        'bike' => 65_000, 'golf' => 70_000, 'tennis' => 60_000,
        'newsletter' => 55_000, 'podcast' => 70_000, 'stream' => 90_000,
        'live' => 85_000, 'chat' => 80_000, 'social' => 75_000,
        'design' => 70_000, 'art' => 75_000, 'photo' => 70_000,
        'print' => 55_000, 'logo' => 60_000, 'brand' => 80_000,
        'agency' => 65_000, 'consulting' => 60_000, 'expert' => 55_000,
    ];

    /**
     * @return array{tier: string, base_usd: int, label: string}|null
     */
    public static function match(string $sld): ?array
    {
        $word = strtolower($sld);

        if (isset(self::ULTRA[$word])) {
            return [
                'tier' => 'ultra',
                'base_usd' => self::ULTRA[$word],
                'label' => 'Ultra premium jenerik kelime',
            ];
        }

        if (isset(self::HIGH[$word])) {
            return [
                'tier' => 'high',
                'base_usd' => self::HIGH[$word],
                'label' => 'Yüksek değerli jenerik kelime',
            ];
        }

        return null;
    }

    /**
     * Tek kelime jenerik olabilecek ama sözlükte olmayan isimler için minimum çarpan.
     */
    public static function isLikelyGenericWord(string $sld): bool
    {
        $len = strlen($sld);

        return $len >= 3
            && $len <= 10
            && preg_match('/^[a-z]+$/', $sld)
            && preg_match('/[aeiou].*[aeiou]/', $sld);
    }
}
