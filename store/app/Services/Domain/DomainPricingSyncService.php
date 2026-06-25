<?php

namespace App\Services\Domain;

use App\Models\DomainRegistrar;
use App\Models\DomainTld;
use App\Services\Domain\Registrar\DomainRegistrarResolver;
use App\Support\DomainTldCatalog;
use Illuminate\Support\Facades\Log;

class DomainPricingSyncService
{
    public function __construct(
        private DomainSettings $settings,
        private DomainCurrency $currency,
        private DomainRegistrarResolver $registrars,
        private DomainAvailabilityService $availability,
    ) {}

    /**
     * @return array{updated: int, created: int, errors: list<string>}
     */
    public function syncAll(): array
    {
        $quotesByTld = [];
        $errors = [];

        foreach ($this->registrars->enabledAccounts() as $account) {
            try {
                $quotes = $this->registrars->driver($account->api_name)->fetchTldPricing($account);
                foreach ($quotes as $tld => $quote) {
                    $tld = $this->normalizeTld($tld);
                    $priceTry = $this->currency->toTry((float) $quote['register'], (string) ($quote['currency'] ?? 'USD'));
                    if ($priceTry <= 0) {
                        continue;
                    }
                    $quotesByTld[$tld][] = [
                        'api_name' => $account->api_name,
                        'register_try' => $priceTry,
                        'renew_try' => $this->currency->toTry((float) ($quote['renew'] ?? $quote['register']), (string) ($quote['currency'] ?? 'USD')),
                        'wholesale_register' => (float) $quote['register'],
                        'wholesale_renew' => (float) ($quote['renew'] ?? $quote['register']),
                        'currency' => (string) ($quote['currency'] ?? 'USD'),
                    ];
                }

                $account->update([
                    'last_synced_at' => now(),
                    'last_sync_status' => 'ok',
                    'last_sync_message' => count($quotes).' TLD fiyatı alındı.',
                ]);
            } catch (\Throwable $e) {
                $message = $account->display_name.': '.$e->getMessage();
                $errors[] = $message;
                Log::warning('domain.sync.failed', ['api' => $account->api_name, 'error' => $e->getMessage()]);
                $account->update([
                    'last_synced_at' => now(),
                    'last_sync_status' => 'error',
                    'last_sync_message' => $e->getMessage(),
                ]);
            }
        }

        $updated = 0;
        $created = 0;

        foreach ($quotesByTld as $tld => $quotes) {
            usort($quotes, fn ($a, $b) => $a['register_try'] <=> $b['register_try']);
            $cheapest = $quotes[0];

            $row = DomainTld::query()->where('tld', $tld)->first();
            if ($row === null) {
                if (! $this->settings->autoImportTlds()) {
                    continue;
                }
                $row = DomainTld::create([
                    'tld' => $tld,
                    'is_active' => false,
                    'sort_order' => 500,
                ]);
                $created++;
            } else {
                $updated++;
            }

            $retailRegister = $this->availability->applyMarkup($cheapest['register_try'], $row);
            $retailRenew = $this->availability->applyMarkup($cheapest['renew_try'], $row);

            $row->update([
                'wholesale_register' => $cheapest['wholesale_register'],
                'wholesale_renew' => $cheapest['wholesale_renew'],
                'wholesale_currency' => $cheapest['currency'],
                'wholesale_registrar_api' => $cheapest['api_name'],
                'register_price' => $retailRegister,
                'renew_price' => $retailRenew,
                'prices_synced_at' => now(),
            ]);
        }

        return compact('updated', 'created', 'errors');
    }

    public function syncRegistrar(DomainRegistrar $account): array
    {
        $account->update(['is_enabled' => true]);

        return $this->syncAll();
    }

    /**
     * Hazir TLD katalogundaki uzantilari (yaklasik maliyetlerle) toplu ekler.
     * Mevcut kayitlara DOKUNMAZ (elle girilen fiyatlar korunur).
     *
     * @return array{created: int, skipped: int}
     */
    public function importFromCatalog(bool $activate = true): array
    {
        $created = 0;
        $skipped = 0;

        foreach (DomainTldCatalog::all() as $entry) {
            $tld = $this->normalizeTld($entry['tld']);

            if (DomainTld::query()->where('tld', $tld)->exists()) {
                $skipped++;

                continue;
            }

            $row = new DomainTld();
            $row->fill([
                'tld' => $tld,
                'wholesale_register' => $entry['register'],
                'wholesale_renew' => $entry['renew'] ?? $entry['register'],
                'wholesale_currency' => 'USD',
                'auto_price' => true,
                'is_active' => $activate,
                'sort_order' => $entry['sort'] ?? 500,
            ]);
            $row->save(); // saving hook auto_price ile satis fiyatini hesaplar
            $created++;
        }

        return compact('created', 'skipped');
    }

    /**
     * Serbest metinden toplu TLD ekler. Her satir:
     *   .com
     *   .com,9.48
     *   .com,9.48,USD
     *   .com,9.48,USD,15
     * Maliyet verilmezse katalogdan denenir; o da yoksa satisa kapali (pasif) eklenir.
     *
     * @return array{created: int, skipped: int, errors: list<string>}
     */
    public function bulkAddFromText(string $raw, bool $activate = true): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];

        $catalog = [];
        foreach (DomainTldCatalog::all() as $entry) {
            $catalog[$this->normalizeTld($entry['tld'])] = $entry;
        }

        $lines = preg_split('/[\r\n]+/', trim($raw)) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode(',', $line));
            $tld = $this->normalizeTld($parts[0]);

            if (! preg_match('/^\.[a-z0-9.-]{2,}$/', $tld)) {
                $errors[] = "Gecersiz TLD: {$parts[0]}";

                continue;
            }

            if (DomainTld::query()->where('tld', $tld)->exists()) {
                $skipped++;

                continue;
            }

            $cost = isset($parts[1]) && $parts[1] !== '' ? (float) str_replace(',', '.', $parts[1]) : null;
            $currency = isset($parts[2]) && $parts[2] !== '' ? strtoupper($parts[2]) : 'USD';
            $markup = isset($parts[3]) && $parts[3] !== '' ? (float) $parts[3] : null;

            // Maliyet verilmediyse katalogdan dene
            if ($cost === null && isset($catalog[$tld])) {
                $cost = (float) $catalog[$tld]['register'];
                $currency = 'USD';
            }

            $row = new DomainTld();
            if ($cost !== null && $cost > 0) {
                $row->fill([
                    'tld' => $tld,
                    'wholesale_register' => $cost,
                    'wholesale_renew' => $catalog[$tld]['renew'] ?? $cost,
                    'wholesale_currency' => $currency,
                    'markup_percent' => $markup,
                    'auto_price' => true,
                    'is_active' => $activate,
                    'sort_order' => $catalog[$tld]['sort'] ?? 500,
                ]);
            } else {
                // Maliyet yok: satisa kapali ekle, yonetici fiyat girsin
                $row->fill([
                    'tld' => $tld,
                    'auto_price' => false,
                    'is_active' => false,
                    'sort_order' => 500,
                ]);
            }
            $row->save();
            $created++;
        }

        return compact('created', 'skipped', 'errors');
    }

    private function normalizeTld(string $tld): string
    {
        $tld = strtolower(trim($tld));

        return str_starts_with($tld, '.') ? $tld : '.'.$tld;
    }
}
