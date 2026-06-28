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

    public function autoInstallPanel(): bool
    {
        return $this->bool('cloud_auto_install_panel', (bool) config('cloud_providers.panel_install.enabled', false));
    }

    public function remoteInstallUrl(): string
    {
        return trim((string) $this->get('cloud_panelze_install_url', config('cloud_providers.panel_install.remote_install_url', '')));
    }

    public function panelLoginUrl(): string
    {
        return rtrim(trim((string) $this->get('cloud_panelze_panel_url', config('cloud_providers.panel_install.panel_url', ''))), '/');
    }

    public function pollMaxAttempts(): int
    {
        return max(1, (int) config('cloud_providers.poll.max_attempts', 30));
    }

    public function pollIntervalSeconds(): int
    {
        return max(2, (int) config('cloud_providers.poll.interval_seconds', 10));
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
