<?php

namespace App\Services;

use App\Services\EInvoice\EInvoiceSettings;

/**
 * KDV hesap yardımcısı. Oran ve "fiyata dahil mi" bilgisini e-fatura ayarlarından
 * (tek kaynak) okur; böylece ödeme ekranı ile kesilen fatura her zaman tutarlı olur.
 */
class TaxService
{
    public function rate(): float
    {
        return (float) EInvoiceSettings::taxRate();
    }

    public function includesTax(): bool
    {
        return EInvoiceSettings::priceIncludesTax();
    }

    /**
     * Verilen tutar (indirim sonrası kalem toplamı) için KDV kırılımı döndürür.
     *
     * - KDV dahil ise: gross = amount (değişmez), tax içeriden ayrıştırılır.
     * - KDV hariç ise: gross = amount + tax (ödenecek tutar artar).
     *
     * @return array{rate: float, net: float, tax: float, gross: float, includes: bool}
     */
    public function breakdown(float $amount): array
    {
        $rate = $this->rate();
        $includes = $this->includesTax();

        if ($rate <= 0 || $amount <= 0) {
            return [
                'rate' => $rate,
                'net' => round(max(0, $amount), 2),
                'tax' => 0.0,
                'gross' => round(max(0, $amount), 2),
                'includes' => $includes,
            ];
        }

        if ($includes) {
            $net = $amount / (1 + $rate / 100);
            $tax = $amount - $net;
            $gross = $amount;
        } else {
            $net = $amount;
            $tax = $amount * $rate / 100;
            $gross = $amount + $tax;
        }

        return [
            'rate' => $rate,
            'net' => round($net, 2),
            'tax' => round($tax, 2),
            'gross' => round($gross, 2),
            'includes' => $includes,
        ];
    }
}
