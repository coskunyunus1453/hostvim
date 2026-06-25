<?php

namespace App\Services\Domain;

use App\Models\DomainRegistrar;
use App\Models\DomainTld;
use App\Services\Domain\Registrar\DomainRegistrarResolver;
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

    private function normalizeTld(string $tld): string
    {
        $tld = strtolower(trim($tld));

        return str_starts_with($tld, '.') ? $tld : '.'.$tld;
    }
}
