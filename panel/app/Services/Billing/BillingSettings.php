<?php

namespace App\Services\Billing;

use App\Models\PanelSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Faturalama & otomasyon ayarları (PanelSetting key-value üzerinde, `billing.*` anahtarlarıyla).
 * Tüm değerler tek bir JSON kaydında ("billing.config") tutulur; tip güvenli erişim sağlar.
 */
class BillingSettings
{
    private const STORE_KEY = 'billing.config';

    private const CACHE_KEY = 'billing.config.cache';

    /** @var array<string, mixed> */
    public const DEFAULTS = [
        'enabled' => true,
        'currency' => 'TRY',
        'tax_rate' => 20.0,            // KDV %
        'tax_inclusive' => false,      // fiyatlara KDV dahil mi
        'invoice_prefix' => 'INV-',
        'order_prefix' => 'ORD-',
        'ticket_prefix' => 'TKT-',
        'due_days' => 7,               // fatura vadesi (gün)
        'reminder_days_before' => [3, 1],      // vade öncesi hatırlatma günleri
        'overdue_reminder_days' => [1, 3, 7],  // vade sonrası hatırlatma günleri
        'renew_generate_days_before' => 10,    // yenileme faturasını kaç gün önce üret
        'suspend_after_days' => 3,     // vade geçince kaç gün sonra askıya al
        'terminate_after_days' => 15,  // askıdan kaç gün sonra sonlandır
        'auto_suspend' => true,
        'auto_terminate' => false,
        'default_php' => '8.2',
        'default_server_type' => 'nginx',
        'company_name' => '',
        'company_address' => '',
        'company_tax_id' => '',
        'support_email' => '',
        'payment_instructions' => '',
    ];

    /** @return array<string, mixed> */
    public function all(): array
    {
        $stored = Cache::remember(self::CACHE_KEY, 300, function (): array {
            $row = PanelSetting::query()->where('key', self::STORE_KEY)->first();
            if (! $row || ! is_string($row->value)) {
                return [];
            }
            $decoded = json_decode($row->value, true);

            return is_array($decoded) ? $decoded : [];
        });

        return array_merge(self::DEFAULTS, $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    /** @param array<string, mixed> $values */
    public function update(array $values): array
    {
        $current = $this->all();
        $merged = array_merge($current, array_intersect_key($values, self::DEFAULTS));

        PanelSetting::query()->updateOrCreate(
            ['key' => self::STORE_KEY],
            ['value' => json_encode($merged, JSON_UNESCAPED_UNICODE)],
        );
        Cache::forget(self::CACHE_KEY);

        return $this->all();
    }

    public function currency(): string
    {
        return strtoupper((string) $this->get('currency', 'TRY'));
    }

    public function taxRate(): float
    {
        return (float) $this->get('tax_rate', 0);
    }

    public function taxInclusive(): bool
    {
        return (bool) $this->get('tax_inclusive', false);
    }

    /** @return array<int, int> */
    public function intList(string $key): array
    {
        $raw = $this->get($key, []);
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn ($v) => (int) $v, $raw)));
    }
}
