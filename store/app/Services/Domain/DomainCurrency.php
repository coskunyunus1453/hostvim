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

        return round($amount, 2);
    }
}
