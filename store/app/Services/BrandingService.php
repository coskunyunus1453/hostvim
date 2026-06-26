<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class BrandingService
{
    public function __construct(
        protected SettingsService $settings,
    ) {}

    public function logoPath(bool $dark = false): ?string
    {
        $key = $dark ? 'site_logo_dark' : 'site_logo';
        $path = $this->normalizeStoredPath($this->settings->get($key));

        if ($dark && ($path === null || $path === '')) {
            $path = $this->normalizeStoredPath($this->settings->get('site_logo'));
        }

        return $this->resolveExistingPath($path);
    }

    public function logoUrl(bool $dark = false): ?string
    {
        $path = $this->logoPath($dark);

        return $path ? $this->publicDiskUrl($path) : null;
    }

    public function faviconUrl(): ?string
    {
        $path = $this->resolveExistingPath($this->normalizeStoredPath($this->settings->get('site_favicon')));

        return $path ? $this->publicDiskUrl($path) : null;
    }

    protected function resolveExistingPath(?string $path): ?string
    {
        static $resolved = [];

        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = ltrim($path, '/');

        if (array_key_exists($path, $resolved)) {
            return $resolved[$path];
        }

        $resolved[$path] = Storage::disk('public')->exists($path) ? $path : null;

        return $resolved[$path];
    }

    protected function normalizeStoredPath(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = collect($path)->filter()->first();
        }

        if (! is_string($path)) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, '[')) {
            $decoded = json_decode($path, true);
            if (is_array($decoded)) {
                $path = (string) (collect($decoded)->filter()->first() ?? '');
            }
        }

        return $path !== '' ? $path : null;
    }

    protected function publicDiskUrl(string $path): string
    {
        return Storage::disk('public')->url(ltrim($path, '/'));
    }

    public function logoHeight(string $context = 'header'): int
    {
        $key = match ($context) {
            'footer' => 'site_logo_footer_height',
            'mobile' => 'site_logo_mobile_height',
            default => 'site_logo_height',
        };

        $height = (int) $this->settings->get($key, match ($context) {
            'footer' => 32,
            'mobile' => 36,
            default => 40,
        });

        return max(20, min(120, $height));
    }

    public function showSiteName(): bool
    {
        return filter_var($this->settings->get('site_logo_show_name', '1'), FILTER_VALIDATE_BOOLEAN);
    }
}
