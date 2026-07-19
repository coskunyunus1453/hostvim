<?php

namespace App\Support;

/**
 * Müşteri arayüzünde gösterilen metinlerden üçüncü taraf API/marka adlarını temizler.
 */
final class CustomerFacingText
{
    /** @var list<string> */
    private const VENDOR_PATTERNS = [
        'spaceship',
        'porkbun',
        'cloudflare',
        'metunic',
        'panelze',
        'whmcs',
    ];

    public static function sanitize(?string $message): string
    {
        if ($message === null || trim($message) === '') {
            return '';
        }

        $text = $message;
        foreach (self::VENDOR_PATTERNS as $vendor) {
            $text = (string) preg_replace('/\b'.preg_quote($vendor, '/').'\b/i', '', $text);
        }

        $text = (string) preg_replace('/\(\s*\)/', '', $text);
        $text = (string) preg_replace('/\s{2,}/', ' ', $text);
        $text = trim($text, " \t\n\r\0\x0B,;.");

        if ($text === '') {
            return 'İşlem tamamlanamadı. Sorun devam ederse destek ile iletişime geçin.';
        }

        return $text;
    }

    /**
     * @return list<string>
     */
    public static function defaultNameservers(): array
    {
        $ns = config('brand.default_nameservers', []);

        return is_array($ns) ? array_values(array_filter(array_map('strval', $ns))) : [];
    }

    public static function brandName(): string
    {
        return (string) config('brand.name', 'HostVim');
    }
}
