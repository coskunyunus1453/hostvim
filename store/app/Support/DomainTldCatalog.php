<?php

namespace App\Support;

/**
 * Populer TLD katalogu: Spaceship maliyetleri (USD/yil).
 *
 * Maliyetler ICANN ucreti (genelde 0.20 USD) DAHIL toplam alis tutaridir.
 * Satis fiyati = maliyet x USD/TRY kuru x (1 + kar marji) olarak otomatik hesaplanir.
 *
 * Satir bicimi: [tld, register, renew, sort, verified?]
 *  - register: yillik kayit maliyeti (USD, ICANN dahil)
 *  - renew:    yillik yenileme maliyeti (USD, ICANN dahil)
 *  - verified: true => Spaceship'ten dogrulanmis GUNCEL fiyat (2026-06).
 *              false/yok => yaklasik referans deger (yonetici teyit etmeli).
 *
 * Dogrulanmis fiyatlar https://www.spaceship.com/domains/ uzerinden alinmistir
 * (gosterilen kayit/yenileme fiyati + 0.20 USD ICANN ucreti).
 */
class DomainTldCatalog
{
    /**
     * @return list<array{tld: string, register: float, renew: float, sort: int, verified: bool}>
     */
    public static function all(): array
    {
        $raw = [
            // Klasik / en populer
            ['.com', 9.08, 10.18, 1, true],
            ['.net', 11.40, 11.40, 2, true],
            ['.org', 6.85, 11.59, 3, true],
            ['.info', 3.31, 21.94, 4, true],
            ['.biz', 14.98, 16.98, 5],
            ['.co', 3.48, 25.98, 6, true],
            ['.me', 8.70, 15.53, 7, true],
            ['.tv', 29.98, 34.98, 8],
            ['.cc', 9.98, 11.98, 9],
            ['.name', 8.98, 10.98, 10],
            ['.pro', 2.79, 21.94, 11, true],
            ['.mobi', 14.98, 19.98, 12],
            ['.asia', 12.98, 14.98, 13],
            ['.it.com', 3.62, 25.88, 14, true],

            // Teknoloji / yeni nesil
            ['.io', 31.98, 51.75, 20, true],
            ['.dev', 10.55, 12.62, 21, true],
            ['.app', 13.98, 15.98, 22],
            ['.tech', 7.42, 50.92, 23, true],
            ['.cloud', 3.49, 20.90, 24, true],
            ['.ai', 79.98, 79.98, 25, true],
            ['.software', 19.98, 24.98, 27],
            ['.systems', 6.98, 21.98, 28],
            ['.network', 6.98, 21.98, 29],
            ['.digital', 4.98, 31.98, 30],
            ['.codes', 12.98, 47.98, 31],
            ['.tools', 6.98, 31.98, 32],
            ['.host', 9.98, 79.98, 33],
            ['.website', 2.98, 24.98, 34],
            ['.site', 1.18, 20.18, 35, true],
            ['.online', 1.18, 20.18, 36, true],
            ['.space', 2.48, 22.98, 37],
            ['.click', 1.24, 10.55, 38, true],
            ['.link', 8.98, 11.98, 39],
            ['.llc', 10.55, 34.36, 40, true],

            // E-ticaret / is
            ['.store', 1.18, 30.78, 50, true],
            ['.shop', 0.90, 31.25, 51, true],
            ['.shopping', 6.98, 31.98, 52],
            ['.business', 6.98, 19.98, 53],
            ['.company', 7.98, 14.98, 54],
            ['.agency', 6.98, 21.98, 55],
            ['.services', 8.98, 31.98, 56],
            ['.solutions', 6.98, 24.98, 57],
            ['.consulting', 9.98, 31.98, 58],
            ['.marketing', 9.98, 31.98, 59],
            ['.management', 6.98, 21.98, 60],
            ['.finance', 9.98, 44.98, 61],
            ['.capital', 12.98, 47.98, 62],
            ['.ventures', 12.98, 47.98, 63],
            ['.partners', 12.98, 47.98, 64],
            ['.group', 7.98, 19.98, 65],
            ['.team', 9.98, 25.98, 66],
            ['.global', 16.98, 64.98, 67],
            ['.international', 6.98, 21.98, 68],
            ['.enterprises', 9.98, 31.98, 69],
            ['.holdings', 12.98, 47.98, 70],
            ['.industries', 9.98, 31.98, 71],
            ['.institute', 6.98, 21.98, 72],
            ['.properties', 9.98, 31.98, 73],
            ['.email', 5.98, 24.98, 74],
            ['.center', 6.98, 23.98, 75],
            ['.zone', 9.98, 31.98, 76],
            ['.work', 2.48, 9.98, 77],
            ['.support', 5.98, 23.98, 78],
            ['.technology', 6.98, 19.98, 79],

            // Icerik / medya / kisisel
            ['.blog', 4.98, 29.98, 90],
            ['.news', 8.98, 24.98, 91],
            ['.media', 7.98, 34.98, 92],
            ['.live', 2.27, 26.08, 93, true],
            ['.life', 3.98, 29.98, 94],
            ['.world', 4.98, 29.98, 95],
            ['.fun', 3.98, 23.98, 96],
            ['.club', 12.98, 14.98, 97],
            ['.art', 3.98, 14.98, 98],
            ['.design', 12.98, 39.98, 99],
            ['.studio', 9.98, 25.98, 100],
            ['.games', 6.98, 24.98, 101],
            ['.photography', 6.98, 21.98, 102],
            ['.photo', 9.98, 24.98, 103],
            ['.video', 9.98, 27.98, 104],
            ['.social', 9.98, 31.98, 105],
            ['.today', 3.98, 24.98, 106],
            ['.academy', 9.98, 31.98, 107],
            ['.education', 6.98, 19.98, 108],
            ['.events', 9.98, 31.98, 109],
            ['.care', 9.98, 31.98, 110],
            ['.health', 19.98, 64.98, 111],
            ['.fit', 6.98, 31.98, 112],
            ['.coffee', 9.98, 31.98, 113],
            ['.kitchen', 12.98, 47.98, 114],
            ['.house', 9.98, 31.98, 115],
            ['.estate', 9.98, 31.98, 116],
            ['.realty', 24.98, 64.98, 117],
            ['.travel', 79.98, 99.98, 118],
            ['.tours', 12.98, 47.98, 119],

            // Kisa / ucuz / promosyon
            ['.xyz', 0.98, 12.72, 130, true],
            ['.top', 2.98, 8.98, 131],
            ['.icu', 2.48, 12.98, 132],
            ['.cyou', 2.48, 13.98, 133],
            ['.sbs', 2.98, 13.98, 134],
            ['.vip', 14.98, 19.98, 135],
            ['.pw', 6.98, 12.98, 136],
            ['.ws', 24.98, 27.98, 137],
            ['.so', 24.98, 29.98, 138],
            ['.gg', 64.98, 74.98, 139],

            // Ulke kodlu (ccTLD)
            ['.us', 4.14, 6.48, 150, true],
            ['.uk', 7.98, 9.98, 151],
            ['.co.uk', 5.42, 5.42, 152, true],
            ['.eu', 5.98, 8.98, 153],
            ['.de', 6.98, 8.98, 154],
            ['.nl', 7.98, 9.98, 155],
            ['.fr', 9.98, 11.98, 156],
            ['.es', 8.98, 10.98, 157],
            ['.it', 7.98, 9.98, 158],
            ['.ca', 9.32, 9.32, 159, true],
            ['.in', 8.98, 10.98, 160],
            ['.co.in', 5.98, 7.98, 161],
            ['.tr', 12.98, 14.98, 162],
        ];

        $out = [];
        foreach ($raw as $row) {
            $tld = $row[0];
            $out[$tld] = [
                'tld' => $tld,
                'register' => (float) $row[1],
                'renew' => (float) $row[2],
                'sort' => (int) $row[3],
                'verified' => (bool) ($row[4] ?? false),
            ];
        }

        return array_values($out);
    }

    /**
     * Sadece Spaceship'ten dogrulanmis guncel fiyatlari dondurur.
     *
     * @return list<array{tld: string, register: float, renew: float, sort: int, verified: bool}>
     */
    public static function verified(): array
    {
        return array_values(array_filter(self::all(), fn ($e) => $e['verified']));
    }
}
