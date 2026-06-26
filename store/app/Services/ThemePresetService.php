<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Support\ThemePresets;

class ThemePresetService
{
    public function activePresetId(): string
    {
        $id = trim((string) app(SettingsService::class)->get('design_theme_preset', 'hostvim-main'));

        if ($id === 'custom' || ThemePresets::has($id)) {
            return $id;
        }

        return 'hostvim-main';
    }

    public function shell(): string
    {
        $id = $this->activePresetId();
        if ($id === 'custom') {
            return 'classic';
        }

        return ThemePresets::get($id)['shell'] ?? 'classic';
    }

    public function apply(string $presetId): bool
    {
        if ($presetId === 'custom' || ! ThemePresets::has($presetId)) {
            return false;
        }

        foreach (ThemePresets::settingsFor($presetId) as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $value,
                    'group' => 'design',
                    'type' => 'text',
                    'label' => $key,
                ]
            );
        }

        SettingsService::clearCache();
        app(\App\Services\CacheInvalidator::class)->forThemePresetApplied();

        return true;
    }
}
