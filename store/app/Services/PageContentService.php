<?php

namespace App\Services;

use App\Support\CloudPageContent;
use App\Support\HostingPageContent;

/**
 * /hosting ve /sunucu sayfalarinin icerigini cozumler.
 * Once site_settings'teki JSON kaydina bakar; bos veya eksikse
 * Support sinifindaki varsayilan SEO icerigine duser.
 */
class PageContentService
{
    public function __construct(
        protected SettingsService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function hosting(): array
    {
        return $this->resolve('hosting_page', HostingPageContent::defaults());
    }

    /**
     * @return array<string, mixed>
     */
    public function cloud(): array
    {
        return $this->resolve('cloud_page', CloudPageContent::defaults());
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    protected function resolve(string $key, array $defaults): array
    {
        $raw = $this->settings->get($key);
        $saved = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        if (! is_array($saved)) {
            return $defaults;
        }

        return [
            'hero' => array_merge($defaults['hero'], $this->filled($saved['hero'] ?? null)),
            'seo' => array_merge($defaults['seo'], $this->filled($saved['seo'] ?? null)),
            'platform' => $this->list($saved['platform'] ?? null, $defaults['platform'] ?? []),
            'intro' => array_merge($defaults['intro'], $this->filled($saved['intro'] ?? null)),
            'features' => $this->list($saved['features'] ?? null, $defaults['features']),
            'tech' => $this->list($saved['tech'] ?? null, $defaults['tech']),
            'details' => $this->list($saved['details'] ?? null, $defaults['details']),
            'faqs' => $this->list($saved['faqs'] ?? null, $defaults['faqs']),
        ];
    }

    /**
     * Sadece dolu (bos olmayan) anahtarlari dondur; bos string'ler varsayilani ezmesin.
     *
     * @param  mixed  $value
     * @return array<string, mixed>
     */
    protected function filled(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_filter($value, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Liste alani (repeater) doluysa kayitli olani, bossa varsayilani kullan.
     *
     * @param  mixed  $value
     * @param  array<int, mixed>  $default
     * @return array<int, mixed>
     */
    protected function list(mixed $value, array $default): array
    {
        if (! is_array($value)) {
            return $default;
        }

        $clean = array_values(array_filter($value, fn ($item) => is_array($item) && count(array_filter($item, fn ($v) => $v !== null && $v !== '')) > 0));

        return $clean === [] ? $default : $clean;
    }
}
