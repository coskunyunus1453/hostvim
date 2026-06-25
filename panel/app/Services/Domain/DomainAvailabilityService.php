<?php

namespace App\Services\Domain;

use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Services\Billing\BillingSettings;
use App\Services\Domain\Registrar\RegistrarDriverResolver;
use App\Services\Domain\Registrar\ResellerClubClient;
use Illuminate\Validation\ValidationException;

class DomainAvailabilityService
{
    public function __construct(
        private BillingSettings $settings,
        private RegistrarDriverResolver $registrarResolver,
        private ResellerClubClient $resellerClub,
    ) {}

    /** @return array{domain: string, tld: string, available: bool, register_price: float, renew_price: float, currency: string, reason?: string} */
    public function check(string $raw): array
    {
        $domain = $this->normalize($raw);
        $tld = $this->extractTld($domain);
        $row = DomainTld::query()->where('tld', $tld)->where('enabled', true)->first();
        if ($row === null) {
            return [
                'domain' => $domain,
                'tld' => $tld,
                'available' => false,
                'register_price' => 0,
                'renew_price' => 0,
                'currency' => $this->settings->currency(),
                'reason' => 'tld_not_supported',
            ];
        }

        if (DomainRegistration::query()->where('domain', $domain)->whereIn('status', ['pending', 'active'])->exists()) {
            return [
                'domain' => $domain,
                'tld' => $tld,
                'available' => false,
                'register_price' => (float) $row->register_price,
                'renew_price' => (float) $row->renew_price,
                'currency' => $this->settings->currency(),
                'reason' => 'already_registered',
            ];
        }

        $available = $this->registrarResolver->usesResellerClub()
            ? $this->checkViaResellerClub($domain)
            : $this->probeAvailable($domain);

        return [
            'domain' => $domain,
            'tld' => $tld,
            'available' => $available,
            'register_price' => (float) $row->register_price,
            'renew_price' => (float) $row->renew_price,
            'currency' => $this->settings->currency(),
            'source' => $this->registrarResolver->usesResellerClub() ? 'resellerclub' : 'local',
        ];
    }

    /** @return list<array{tld: string, register_price: float, renew_price: float}> */
    public function listTlds(): array
    {
        return DomainTld::query()
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->get(['tld', 'register_price', 'renew_price'])
            ->map(fn (DomainTld $t) => [
                'tld' => $t->tld,
                'register_price' => (float) $t->register_price,
                'renew_price' => (float) $t->renew_price,
            ])
            ->all();
    }

    public function priceFor(string $domain, int $years = 1): float
    {
        $tld = $this->extractTld($this->normalize($domain));
        $row = DomainTld::query()->where('tld', $tld)->where('enabled', true)->first();
        if ($row === null) {
            throw ValidationException::withMessages(['domain' => 'Bu uzantı desteklenmiyor.']);
        }

        return round((float) $row->register_price * max(1, min(10, $years)), 2);
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
        if (preg_match('/\.(com|net|org)\.tr$/', $domain)) {
            return '.'.implode('.', array_slice(explode('.', $domain), -2));
        }
        $parts = explode('.', $domain);

        return '.'.end($parts);
    }

    private function checkViaResellerClub(string $domain): bool
    {
        try {
            return $this->resellerClub->isDomainAvailable($domain);
        } catch (\Throwable $e) {
            report($e);

            return $this->probeAvailable($domain);
        }
    }

    private function probeAvailable(string $domain): bool
    {
        if (@checkdnsrr($domain, 'A') || @checkdnsrr($domain, 'AAAA') || @checkdnsrr($domain, 'NS')) {
            return false;
        }

        $whois = $this->whoisHint($domain);
        if ($whois !== null) {
            $lower = strtolower($whois);
            foreach (['no match', 'not found', 'available', 'no entries found', 'status: free'] as $free) {
                if (str_contains($lower, $free)) {
                    return true;
                }
            }
            foreach (['domain name:', 'registrar:', 'creation date'] as $taken) {
                if (str_contains($lower, $taken)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function whoisHint(string $domain): ?string
    {
        if (! function_exists('proc_open')) {
            return null;
        }
        $proc = @proc_open(
            ['whois', $domain],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            null,
            ['bypass_shell' => true],
        );
        if (! is_resource($proc)) {
            return null;
        }
        $out = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        proc_close($proc);

        return $out !== '' ? $out : null;
    }
}
