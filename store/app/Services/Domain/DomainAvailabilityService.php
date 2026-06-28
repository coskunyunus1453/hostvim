<?php

namespace App\Services\Domain;

use App\Models\DomainRegistrar;
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

        // Musaitlik registrar API (ornegin Spaceship) ile dogrulanir.
        // Fiyat ise her zaman katalogdan (satis fiyati) gelir.
        $account = $this->resolveAccount($row);
        [$available, $reason] = $this->resolveAvailability($account, $domain);

        return $this->buildResult($domain, $row, $available, $reason);
    }

    /**
     * TLD'ye atanmis registrar hesabini, yoksa varsayilan aktif hesabi dondurur.
     */
    private function resolveAccount(DomainTld $row): ?DomainRegistrar
    {
        $apiName = $row->registrar_api_name ?: $row->wholesale_registrar_api;
        $account = $apiName ? $this->registrars->account($apiName) : null;

        return $account ?? $this->registrars->enabledAccounts()->first();
    }

    /**
     * Registrar API ile musaitlik kontrolu; API yoksa/hata verirse DNS'e dusulur.
     * ASLA "dogrulanmadan musait" demez — yanlis musait gostermek satista risklidir.
     *
     * @return array{0: bool, 1: ?string}
     */
    private function resolveAvailability(?DomainRegistrar $account, string $domain): array
    {
        if ($account !== null) {
            try {
                $result = $this->registrars->driver($account->api_name)->checkAvailability($account, $domain);
                $reason = $result['reason'] ?? null;

                // API kesin cevap veremedi (rate limit / hata / yapilandirma):
                // yanlis sonuc vermemek icin DNS kontroluyle teyit et.
                if (in_array($reason, ['api_error', 'not_configured', 'rate_limited'], true)) {
                    return $this->dnsAvailability($domain);
                }

                return [(bool) ($result['available'] ?? false), $reason];
            } catch (\Throwable $e) {
                Log::warning('domain.check.api_failed', ['api' => $account->api_name, 'domain' => $domain, 'error' => $e->getMessage()]);
            }
        }

        return $this->dnsAvailability($domain);
    }

    /**
     * Registrar API erisilemediginde DNS kayitlarina bakarak kabaca kontrol eder.
     * NS/SOA/A kaydi varsa domain KAYITLIDIR; hicbiri yoksa muhtemelen musait;
     * sorgulama yapilamazsa "dogrulanamadi" (musait gosterilmez).
     *
     * @return array{0: bool, 1: ?string}
     */
    private function dnsAvailability(string $domain): array
    {
        $registered = $this->looksRegistered($domain);
        if ($registered === true) {
            return [false, 'registered'];
        }
        if ($registered === false) {
            return [true, null];
        }

        return [false, 'unverified'];
    }

    private function looksRegistered(string $domain): ?bool
    {
        try {
            foreach (['NS', 'SOA', 'A'] as $type) {
                $records = @dns_get_record($domain, constant('DNS_'.$type));
                if (is_array($records) && count($records) > 0) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Katalog fiyatlari + musaitlik durumundan standart sonuc dizisini kurar.
     *
     * @return array<string, mixed>
     */
    private function buildResult(string $domain, DomainTld $row, bool $available, ?string $reason): array
    {
        $retailRegister = (float) $row->register_price;
        $retailRenew = (float) $row->renew_price;
        $retailTransfer = (float) ($row->transfer_price ?? 0);
        $renew = $retailRenew > 0 ? $retailRenew : $retailRegister;

        return [
            'domain' => $domain,
            'tld' => $row->normalizedTld(),
            'available' => $available,
            'register_price' => $retailRegister,
            'renew_price' => $renew,
            'transfer_price' => $retailTransfer > 0 ? $retailTransfer : $renew,
            'currency' => $this->settings->currency(),
            'registrar_api' => $row->registrar_api_name ?: $row->wholesale_registrar_api,
            'reason' => $reason,
            'source' => 'catalog',
        ];
    }

    /**
     * Tek sorguda hem girilen alan adini hem de ayni isimle populer
     * uzantilari (oneriler) musaitlik + fiyatlariyla dondurur.
     *
     * @return array{query: string, sld: string, primary: array<string,mixed>, suggestions: list<array<string,mixed>>}
     */
    public function search(string $raw, int $suggestionLimit = 9): array
    {
        $domain = $this->normalize($raw);
        $primaryTld = $this->extractTld($domain);
        $sld = $this->stripTld($domain, $primaryTld);
        $registerEnabled = $this->settings->registerEnabled();

        $rows = $registerEnabled
            ? DomainTld::query()->where('is_active', true)->orderBy('sort_order')->get()
            : collect();

        $primaryRow = $rows->first(fn (DomainTld $r) => $r->normalizedTld() === $primaryTld);

        // Aday alan adlari (primary once), tld kaydiyla eslestirilir.
        $candidates = [];
        if ($primaryRow !== null) {
            $candidates[$domain] = $primaryRow;
        }
        foreach ($rows as $r) {
            if ($r->normalizedTld() === $primaryTld) {
                continue;
            }
            $candidates[$sld.$r->normalizedTld()] = $r;
            if (count($candidates) >= $suggestionLimit + 1) {
                break;
            }
        }

        // Tum adaylar tek istekte kontrol edilir (Spaceship bulk availability).
        $availability = $this->bulkAvailability(array_keys($candidates));

        $primary = $primaryRow !== null
            ? $this->buildResult(
                $domain,
                $primaryRow,
                $availability[$domain]['available'] ?? false,
                $availability[$domain]['reason'] ?? null
            )
            : $this->unavailable($domain, $registerEnabled ? 'tld_not_supported' : 'disabled', $primaryTld);

        $suggestions = [];
        foreach ($candidates as $candidate => $row) {
            if ($candidate === $domain) {
                continue;
            }
            $av = $availability[$candidate] ?? ['available' => false, 'reason' => 'unverified'];
            $suggestions[] = $this->buildResult($candidate, $row, $av['available'], $av['reason']);
        }

        return [
            'query' => $domain,
            'sld' => $sld,
            'primary' => $primary,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Birden fazla alan adini tek seferde kontrol eder. Aktif registrar bulk
     * destekliyorsa tek API istegi; degilse tekil/DNS'e duser.
     *
     * @param  list<string>  $domains
     * @return array<string, array{available: bool, reason: ?string}>
     */
    private function bulkAvailability(array $domains): array
    {
        if ($domains === []) {
            return [];
        }

        $account = $this->registrars->enabledAccounts()->first();

        if ($account !== null) {
            $driver = $this->registrars->driver($account->api_name);

            if (method_exists($driver, 'checkAvailabilityBulk')) {
                try {
                    $res = $driver->checkAvailabilityBulk($account, $domains);
                    if ($res !== []) {
                        foreach ($domains as $d) {
                            if (! isset($res[$d])) {
                                $res[$d] = $this->pairToResult($this->resolveAvailability($account, $d));
                            }
                        }

                        return $res;
                    }
                } catch (\Throwable $e) {
                    Log::warning('domain.bulk.failed', ['api' => $account->api_name, 'error' => $e->getMessage()]);
                }
            }

            $out = [];
            foreach ($domains as $d) {
                $out[$d] = $this->pairToResult($this->resolveAvailability($account, $d));
            }

            return $out;
        }

        $out = [];
        foreach ($domains as $d) {
            $out[$d] = $this->pairToResult($this->dnsAvailability($d));
        }

        return $out;
    }

    /**
     * @param  array{0: bool, 1: ?string}  $pair
     * @return array{available: bool, reason: ?string}
     */
    private function pairToResult(array $pair): array
    {
        return ['available' => $pair[0], 'reason' => $pair[1]];
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
                'transfer_price' => (float) ($t->transfer_price ?? 0) > 0
                    ? (float) $t->transfer_price
                    : (float) $t->renew_price,
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

    private function stripTld(string $domain, string $tld): string
    {
        if ($tld !== '' && str_ends_with($domain, $tld)) {
            return substr($domain, 0, -strlen($tld));
        }

        return explode('.', $domain, 2)[0] ?? $domain;
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
            'transfer_price' => 0,
            'currency' => $this->settings->currency(),
            'reason' => $reason,
        ];
    }
}
