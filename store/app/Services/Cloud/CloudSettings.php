<?php

namespace App\Services\Cloud;

use App\Models\SiteSetting;

class CloudSettings
{
    public function provisionEnabled(): bool
    {
        return $this->bool('cloud_provision_enabled', true);
    }

    public function usdTryRate(): float
    {
        return max(0.01, (float) $this->get('cloud_usd_try_rate', 35));
    }

    public function eurTryRate(): float
    {
        return max(0.01, (float) $this->get('cloud_eur_try_rate', 38));
    }

    private function get(string $key, mixed $default = null): mixed
    {
        $value = SiteSetting::query()->where('key', $key)->value('value');

        return $value !== null && $value !== '' ? $value : $default;
    }

    private function bool(string $key, bool $default): bool
    {
        return filter_var($this->get($key, $default ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
    }
}
