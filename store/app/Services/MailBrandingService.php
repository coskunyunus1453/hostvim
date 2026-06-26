<?php

namespace App\Services;

class MailBrandingService
{
    public function __construct(
        protected SettingsService $settings,
        protected BrandingService $branding,
    ) {}

    /**
     * @return array{
     *     site_name: string,
     *     site_url: string,
     *     login_url: string,
     *     account_url: string,
     *     support_email: string,
     *     primary_color: string,
     *     secondary_color: string,
     *     logo_url: ?string,
     *     show_logo: bool,
     *     year: string
     * }
     */
    public function context(): array
    {
        $siteName = (string) $this->settings->get('site_name', config('app.name', 'HostVim'));
        $siteUrl = rtrim((string) config('app.url', url('/')), '/');
        $logoUrl = $this->absoluteAssetUrl($this->branding->logoUrl());

        return [
            'site_name' => $siteName,
            'site_url' => $siteUrl,
            'login_url' => $siteUrl.'/giris',
            'account_url' => $siteUrl.'/hesabim',
            'support_email' => (string) $this->settings->get('contact_email', ''),
            'primary_color' => $this->normalizeColor((string) $this->settings->get('primary_color', '#C2410C')),
            'secondary_color' => $this->normalizeColor((string) $this->settings->get('secondary_color', '#0F766E')),
            'logo_url' => $logoUrl,
            'show_logo' => $logoUrl !== null && $logoUrl !== '',
            'year' => (string) date('Y'),
        ];
    }

    /**
     * Şablon gövdesinde kullanılabilecek varsayılan değişkenler.
     *
     * @return array<string, string>
     */
    public function replacements(): array
    {
        $ctx = $this->context();

        return [
            'site_name' => $ctx['site_name'],
            'site_url' => $ctx['site_url'],
            'login_url' => $ctx['login_url'],
            'account_url' => $ctx['account_url'],
            'support_email' => $ctx['support_email'],
            'primary_color' => $ctx['primary_color'],
            'secondary_color' => $ctx['secondary_color'],
            'year' => $ctx['year'],
        ];
    }

    public function wrap(string $innerHtml): string
    {
        return view('mail.layout', [
            'content' => $innerHtml,
            'branding' => $this->context(),
        ])->render();
    }

    protected function absoluteAssetUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url(ltrim($path, '/'));
    }

    protected function normalizeColor(string $color): string
    {
        $color = trim($color);
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1) {
            return $color;
        }

        return '#C2410C';
    }
}
