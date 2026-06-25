<?php

namespace App\Support;

/**
 * Populer TLD katalogu: yaklasik Spaceship maliyetleri (USD/yil).
 *
 * NOT: Spaceship API'si standart TLD fiyat katalogu DONDURMEZ
 * (sadece premium domainlerde fiyat doner). Bu yuzden buradaki
 * maliyetler YAKLASIK referans degerlerdir; yonetici kendi Spaceship
 * hesabindan teyit edip guncellemelidir. Satis fiyati = maliyet x USD/TRY
 * kuru x (1 + kar marji) olarak otomatik hesaplanir.
 *
 * register: yillik kayit maliyeti, renew: yillik yenileme maliyeti (USD).
 */
class DomainTldCatalog
{
    /**
     * @return list<array{tld: string, register: float, renew: float, sort: int}>
     */
    public static function all(): array
    {
        $raw = [
            // Klasik / en populer
            ['.com', 9.48, 11.48, 1],
            ['.net', 12.48, 14.48, 2],
            ['.org', 9.98, 11.98, 3],
            ['.info', 4.48, 22.48, 4],
            ['.biz', 14.98, 16.98, 5],
            ['.co', 24.98, 29.98, 6],
            ['.me', 8.98, 21.98, 7],
            ['.tv', 29.98, 34.98, 8],
            ['.cc', 9.98, 11.98, 9],
            ['.name', 8.98, 10.98, 10],
            ['.pro', 5.98, 19.98, 11],
            ['.mobi', 14.98, 19.98, 12],
            ['.asia', 12.98, 14.98, 13],

            // Teknoloji / yeni nesil
            ['.io', 34.98, 44.98, 20],
            ['.dev', 12.98, 14.98, 21],
            ['.app', 13.98, 15.98, 22],
            ['.tech', 5.98, 49.98, 23],
            ['.cloud', 3.98, 19.98, 24],
            ['.ai', 69.98, 99.98, 25],
            ['.dev', 12.98, 14.98, 26],
            ['.software', 19.98, 24.98, 27],
            ['.systems', 6.98, 21.98, 28],
            ['.network', 6.98, 21.98, 29],
            ['.digital', 4.98, 31.98, 30],
            ['.codes', 12.98, 47.98, 31],
            ['.tools', 6.98, 31.98, 32],
            ['.host', 9.98, 79.98, 33],
            ['.website', 2.98, 24.98, 34],
            ['.site', 2.98, 28.98, 35],
            ['.online', 3.48, 33.48, 36],
            ['.space', 2.48, 22.98, 37],
            ['.click', 3.98, 11.98, 38],
            ['.link', 8.98, 11.98, 39],

            // E-ticaret / is
            ['.store', 4.98, 54.98, 50],
            ['.shop', 2.48, 35.98, 51],
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
            ['.live', 4.98, 23.98, 93],
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
            ['.xyz', 2.98, 13.98, 130],
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
            ['.us', 6.98, 9.98, 150],
            ['.uk', 7.98, 9.98, 151],
            ['.co.uk', 7.98, 9.98, 152],
            ['.eu', 5.98, 8.98, 153],
            ['.de', 6.98, 8.98, 154],
            ['.nl', 7.98, 9.98, 155],
            ['.fr', 9.98, 11.98, 156],
            ['.es', 8.98, 10.98, 157],
            ['.it', 7.98, 9.98, 158],
            ['.ca', 12.98, 14.98, 159],
            ['.in', 8.98, 10.98, 160],
            ['.co.in', 5.98, 7.98, 161],
            ['.tr', 12.98, 14.98, 162],
        ];

        $out = [];
        foreach ($raw as [$tld, $register, $renew, $sort]) {
            $out[$tld] = ['tld' => $tld, 'register' => $register, 'renew' => $renew, 'sort' => $sort];
        }

        return array_values($out);
    }
}
