<?php

namespace App\Services\Domain;

use App\Models\DomainTld;
use App\Services\Domain\Registrar\DomainRegistrarResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DomainAvailabilityService
{
    public function __construct(
        private DomainSettings $settings,
        private DomainCurrency $currency,
        private DomainRegistrarResolver $registrars,
    ) {}

    /** @return array{domain: string, tld: string, available: bool, register_price: float, renew_price: float, currency: string, registrar_api?: string, reason?: string} */
    public function check(string $raw): array
    {
        if (! $this->settings->registerEnabled()) {
            return $this->unavailable($this->normalize($raw), 'disabled');
        }

        $domain = $this->normalize($raw);
        $tld = $this->extractTld($domain);
        $row = DomainTld::query()->where('tld', $tld)->where('is_active', true)->first();

        if ($row === null) {
            return $this->unavailable($domain, 'tld_not_supported', $tld);
        }

        $apiName = $this->resolveRegistrarApi($row, $tld);
        $retailRegister = (float) $row->register_price;
        $retailRenew = (float) $row->renew_price;

        if ($apiName !== null) {
            $account = $this->registrars->account($apiName);
            if ($account !== null) {
                try {
                    $result = $this->registrars->driver($apiName)->checkAvailability($account, $domain);
                    if (isset($result['register_price'])) {
                        $retailRegister = $this->applyMarkup(
                            $this->currency->toTry((float) $result['register_price'], (string) ($result['currency'] ?? 'USD')),
                            $row
                        );
                    }
                    if (isset($result['renew_price'])) {
                        $retailRenew = $this->applyMarkup(
                            $this->currency->toTry((float) $result['renew_price'], (string) ($result['currency'] ?? 'USD')),
                            $row
                        );
                    }

                    return [
                        'domain' => $domain,
                        'tld' => $tld,
                        'available' => (bool) ($result['available'] ?? false),
                        'register_price' => $retailRegister,
                        'renew_price' => $retailRenew > 0 ? $retailRenew : $retailRegister,
                        'currency' => $this->settings->currency(),
                        'registrar_api' => $apiName,
                        'reason' => $result['reason'] ?? null,
                    ];
                } catch (\Throwable $e) {
                    Log::warning('domain.check.api_failed', ['api' => $apiName, 'domain' => $domain, 'error' => $e->getMessage()]);
                }
            }
        }

        return [
            'domain' => $domain,
            'tld' => $tld,
            'available' => true,
            'register_price' => $retailRegister,
            'renew_price' => $retailRenew > 0 ? $retailRenew : $retailRegister,
            'currency' => $this->settings->currency(),
            'registrar_api' => $apiName ?? $row->wholesale_registrar_api,
            'source' => 'catalog',
        ];
    }

    /** @return list<array{tld: string, register_price: float, renew_price: float, registrar_api?: string}> */
    public function listTlds(): array
    {
        return DomainTld::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (DomainTld $t) => [
                'tld' => $t->normalizedTld(),
                'register_price' => (float) $t->register_price,
                'renew_price' => (float) $t->renew_price,
                'registrar_api' => $t->registrar_api_name ?: $t->wholesale_registrar_api,
            ])
            ->all();
    }

    public function priceFor(string $domain, int $years = 1): float
    {
        $check = $this->check($domain);
        if (! ($check['available'] ?? false)) {
            throw ValidationException::withMessages(['domain' => 'Bu alan adı müsait değil veya desteklenmiyor.']);
        }

        return round((float) $check['register_price'] * max(1, min(10, $years)), 2);
    }

    public function applyMarkup(float $wholesaleTry, DomainTld $row): float
    {
        $markup = $row->markup_percent;
        if ($markup === null) {
            $markup = $this->settings->defaultMarkupPercent();
        }

        return round($wholesaleTry * (1 + ((float) $markup / 100)), 2);
    }

    private function resolveRegistrarApi(DomainTld $row, string $tld): ?string
    {
        if ($row->registrar_api_name) {
            return $row->registrar_api_name;
        }

        if ($row->wholesale_registrar_api) {
            return $row->wholesale_registrar_api;
        }

        return $this->cheapestRegistrarForTld($tld);
    }

    private function cheapestRegistrarForTld(string $tld): ?string
    {
        $bestApi = null;
        $bestPrice = PHP_FLOAT_MAX;

        foreach ($this->registrars->enabledAccounts() as $account) {
            try {
                $quotes = $this->registrars->driver($account->api_name)->fetchTldPricing($account);
                $quote = $quotes[$tld] ?? null;
                if ($quote === null) {
                    continue;
                }
                $priceTry = $this->currency->toTry((float) $quote['register'], (string) ($quote['currency'] ?? 'USD'));
                if ($priceTry > 0 && $priceTry < $bestPrice) {
                    $bestPrice = $priceTry;
                    $bestApi = $account->api_name;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $bestApi;
    }

    private function normalize(string $raw): string
    {
        $domain = strtolower(trim($raw));
        $domain = preg_replace('/^https?:\/\//', '', $domain) ?? $domain;
        $domain = explode('/', $domain)[0] ?? $domain;
        if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/', $domain)) {
            throw ValidationException::withMessages(['domain' => 'Geçersiz alan adı.']);
        }

        return $domain;
    }

    private function extractTld(string $domain): string
    {
        if (preg_match('/\.(com|net|org|gen|web|info|biz|name|tv|cc|bel)\.tr$/', $domain)) {
            return substr($domain, strrpos($domain, '.', -5));
        }

        return '.'.explode('.', $domain, 2)[1] ?? '';
    }

    /** @return array{domain: string, tld: string, available: false, register_price: float, renew_price: float, currency: string, reason: string} */
    private function unavailable(string $domain, string $reason, ?string $tld = null): array
    {
        return [
            'domain' => $domain,
            'tld' => $tld ?? $this->extractTld($domain),
            'available' => false,
            'register_price' => 0,
            'renew_price' => 0,
            'currency' => $this->settings->currency(),
            'reason' => $reason,
        ];
    }
}
