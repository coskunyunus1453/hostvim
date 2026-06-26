<?php

namespace App\Services\EInvoice;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * E-fatura ayarlarını (sağlayıcı, kimlikler, firma bilgisi) okur.
 * Değerler site_settings tablosunda 'e_invoice.*' anahtarlarında saklanır.
 * Gizli alanlar (api key/secret/şifre) şifreli (encrypt) tutulur.
 */
class EInvoiceSettings
{
    public const GROUP = 'einvoice';

    /** @var list<string> Şifrelenerek saklanan hassas alanlar */
    public const SECRET_KEYS = [
        'e_invoice.nilvera_api_key',
        'e_invoice.parasut_client_secret',
        'e_invoice.parasut_password',
        'e_invoice.mukellef_api_key',
    ];

    /** @return array<string, string> */
    public static function all(): array
    {
        return Cache::remember('site_settings.einvoice', 3600, function (): array {
            return SiteSetting::query()
                ->where('group', self::GROUP)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all()[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }

        if (in_array($key, self::SECRET_KEYS, true)) {
            try {
                return decrypt($value);
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::all()[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function provider(): string
    {
        return (string) (self::all()['e_invoice.provider'] ?? 'none');
    }

    public static function isEnabled(): bool
    {
        return self::provider() !== 'none' && self::provider() !== '';
    }

    public static function autoCreateDraft(): bool
    {
        return self::bool('e_invoice.auto_draft', true);
    }

    public static function autoIssue(): bool
    {
        return self::bool('e_invoice.auto_issue', false);
    }

    public static function testMode(): bool
    {
        return self::bool('e_invoice.test_mode', true);
    }

    public static function taxRate(): float
    {
        return (float) (self::all()['e_invoice.tax_rate'] ?? 20);
    }

    /** Fiyatlara KDV dahil mi? (true ise tutardan KDV ayrıştırılır) */
    public static function priceIncludesTax(): bool
    {
        return self::bool('e_invoice.price_includes_tax', true);
    }

    /** @return array<string, string> Satıcı firma bilgileri */
    public static function company(): array
    {
        $all = self::all();

        return [
            'title' => (string) ($all['e_invoice.company_title'] ?? ''),
            'tax_office' => (string) ($all['e_invoice.company_tax_office'] ?? ''),
            'tax_number' => (string) ($all['e_invoice.company_tax_number'] ?? ''),
            'address' => (string) ($all['e_invoice.company_address'] ?? ''),
            'phone' => (string) ($all['e_invoice.company_phone'] ?? ''),
            'email' => (string) ($all['e_invoice.company_email'] ?? ''),
        ];
    }

    public static function clearCache(): void
    {
        Cache::forget('site_settings.einvoice');
    }
}
