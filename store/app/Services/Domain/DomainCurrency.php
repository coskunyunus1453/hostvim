<?php

namespace App\Services\Domain;

class DomainCurrency
{
    public function __construct(private DomainSettings $settings) {}

    public function toTry(float $amount, string $currency): float
    {
        $currency = strtoupper(trim($currency));

        if ($currency === 'TRY' || $currency === 'TL') {
            return round($amount, 2);
        }

        if ($currency === 'USD') {
            return round($amount * $this->settings->usdTryRate(), 2);
        }

        if ($currency === 'EUR') {
            $rate = $this->settings->eurTryRate();
            // EUR kuru ayarlanmamissa USD kuruna gore yaklasik cevir (1 EUR ~= 1.08 USD)
            $rate = $rate > 0 ? $rate : ($this->settings->usdTryRate() * 1.08);

            return round($amount * $rate, 2);
        }

        if ($currency === 'GBP') {
            $rate = $this->settings->gbpTryRate();
            $rate = $rate > 0 ? $rate : ($this->settings->usdTryRate() * 1.27);

            return round($amount * $rate, 2);
        }

        // Bilinmeyen para birimi: USD varsayip cevir (cevrilmeden gecmesin)
        return round($amount * $this->settings->usdTryRate(), 2);
    }

    public function rateFor(string $currency): float
    {
        $currency = strtoupper(trim($currency));

        return match ($currency) {
            'TRY', 'TL' => 1.0,
            'USD' => $this->settings->usdTryRate(),
            'EUR' => $this->settings->eurTryRate() > 0 ? $this->settings->eurTryRate() : $this->settings->usdTryRate() * 1.08,
            'GBP' => $this->settings->gbpTryRate() > 0 ? $this->settings->gbpTryRate() : $this->settings->usdTryRate() * 1.27,
            default => $this->settings->usdTryRate(),
        };
    }
}
