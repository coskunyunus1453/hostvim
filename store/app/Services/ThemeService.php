<?php

namespace App\Services;

use App\Support\ThemePresets;

class ThemeService
{
    public function __construct(
        protected SettingsService $settings,
        protected ThemePresetService $presets,
    ) {}

    public function presetId(): string
    {
        return $this->presets->activePresetId();
    }

    public function shell(): string
    {
        return $this->presets->shell();
    }

    public function fontFamily(): string
    {
        $fromDb = trim((string) $this->settings->get('design_font_family', ''));

        if ($fromDb !== '') {
            return $fromDb;
        }

        $preset = ThemePresets::get($this->presetId());

        return $preset['font_family'] ?? 'Plus Jakarta Sans';
    }

    public function fontUrl(): string
    {
        $presetId = $this->presetId();
        if ($presetId !== 'custom') {
            $preset = ThemePresets::get($presetId);
            if ($preset) {
                return $preset['font_url'];
            }
        }

        $family = str_replace(' ', '+', $this->fontFamily());

        return "https://fonts.googleapis.com/css2?family={$family}:wght@400;600;700&display=swap";
    }

    public function defaultMode(): string
    {
        $mode = (string) $this->settings->get('design_theme_mode', 'system');

        return in_array($mode, ['light', 'dark', 'system'], true) ? $mode : 'system';
    }

    public function isToggleEnabled(): bool
    {
        return filter_var($this->settings->get('design_theme_toggle', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function headerStyle(): string
    {
        $style = (string) $this->settings->get('design_header_style', 'glass');

        return in_array($style, ['default', 'glass', 'solid'], true) ? $style : 'glass';
    }

    public function footerStyle(): string
    {
        $style = (string) $this->settings->get('design_footer_style', 'default');

        return in_array($style, ['default', 'minimal', 'gradient'], true) ? $style : 'default';
    }

    /** @return array<string, string> */
    public function lightTokens(): array
    {
        return [
            'primary' => $this->color('design_light_primary', '#C2410C'),
            'primary-hover' => $this->color('design_light_primary_hover', '#9A3412'),
            'secondary' => $this->color('design_light_secondary', '#166534'),
            'bg' => $this->color('design_light_bg', '#FFFFFF'),
            'surface' => $this->color('design_light_surface', '#FAFAF9'),
            'surface-elevated' => $this->color('design_light_surface_elevated', '#FFFFFF'),
            'text' => $this->color('design_light_text', '#1C1917'),
            'text-muted' => $this->color('design_light_text_muted', '#57534E'),
            'link' => $this->color('design_light_link', '#C2410C'),
            'border' => $this->color('design_light_border', '#E7E5E4'),
            'header-bg' => $this->color('design_header_bg_light', '#FFFFFF'),
            'footer-bg' => $this->color('design_footer_bg_light', '#FAFAF9'),
        ];
    }

    /** @return array<string, string> */
    public function darkTokens(): array
    {
        return [
            'primary' => $this->color('design_dark_primary', '#EA580C'),
            'primary-hover' => $this->color('design_dark_primary_hover', '#FB923C'),
            'secondary' => $this->color('design_dark_secondary', '#22C55E'),
            'bg' => $this->color('design_dark_bg', '#0C0A09'),
            'surface' => $this->color('design_dark_surface', '#1C1917'),
            'surface-elevated' => $this->color('design_dark_surface_elevated', '#292524'),
            'text' => $this->color('design_dark_text', '#FAFAF9'),
            'text-muted' => $this->color('design_dark_text_muted', '#A8A29E'),
            'link' => $this->color('design_dark_link', '#FB923C'),
            'border' => $this->color('design_dark_border', '#292524'),
            'header-bg' => $this->color('design_header_bg_dark', '#0C0A09'),
            'footer-bg' => $this->color('design_footer_bg_dark', '#1C1917'),
        ];
    }

    public function cssVariables(): string
    {
        $light = $this->lightTokens();
        $dark = $this->darkTokens();
        $font = $this->fontFamily();
        $radius = $this->shellRadius();

        $lines = [
            ':root {',
            "  --hv-font-sans: '{$font}', ui-sans-serif, system-ui, sans-serif;",
            "  --hv-radius: {$radius};",
            "  --hv-radius-lg: calc({$radius} + 4px);",
        ];
        foreach ($light as $key => $value) {
            $lines[] = "  --hv-{$key}: {$value};";
        }
        $lines[] = '}';
        $lines[] = 'html.dark, [data-theme="dark"] {';
        foreach ($dark as $key => $value) {
            $lines[] = "  --hv-{$key}: {$value};";
        }
        $lines[] = '}';

        return implode("\n", $lines);
    }

    protected function shellRadius(): string
    {
        return match ($this->shell()) {
            'friendly-soft', 'friendly-cozy' => '1.25rem',
            'gamer-neon', 'gamer-cyber' => '0.25rem',
            'corporate-bar', 'corporate-red' => '0.375rem',
            default => '0.75rem',
        };
    }

    protected function color(string $key, string $default): string
    {
        $value = trim((string) $this->settings->get($key, $default));

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return $value;
        }

        return $default;
    }
}
