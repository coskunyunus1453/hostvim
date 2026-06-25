<?php

namespace App\Services\Domain;

use App\Models\SiteSetting;

class DomainSettings
{
    public function registerEnabled(): bool
    {
        return $this->bool('domain_register_enabled', true);
    }

    public function usdTryRate(): float
    {
        $rate = (float) $this->get('domain_usd_try_rate', config('domain_registrars.default_usd_try_rate', 35));

        return max(0.01, $rate);
    }

    public function eurTryRate(): float
    {
        return max(0.0, (float) $this->get('domain_eur_try_rate', 0));
    }

    public function gbpTryRate(): float
    {
        return max(0.0, (float) $this->get('domain_gbp_try_rate', 0));
    }

    public function defaultMarkupPercent(): float
    {
        return max(0, (float) $this->get('domain_default_markup_percent', config('domain_registrars.default_markup_percent', 15)));
    }

    public function autoImportTlds(): bool
    {
        return $this->bool('domain_auto_import_tlds', false);
    }

    public function currency(): string
    {
        return 'TRY';
    }

    private function get(string $key, mixed $default = null): mixed
    {
        $value = SiteSetting::query()->where('key', $key)->value('value');

        return $value !== null && $value !== '' ? $value : $default;
    }

    private function bool(string $key, bool $default): bool
    {
        $value = $this->get($key, $default ? '1' : '0');

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
