<?php

namespace App\Services\Domain;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Alan adi sahiplik bilgisini (WHOIS) RDAP protokolu ile getirir.
 * RDAP, WHOIS'in modern JSON tabanli halidir; rdap.org bootstrap servisi
 * sorguyu ilgili registry'nin yetkili RDAP sunucusuna yonlendirir.
 */
class WhoisService
{
    private const RDAP_BOOTSTRAP = 'https://rdap.org/domain/';

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $raw): array
    {
        $domain = $this->normalize($raw);

        if ($domain === '' || ! str_contains($domain, '.')) {
            return ['ok' => false, 'reason' => 'invalid', 'domain' => $domain];
        }

        try {
            $response = Http::timeout(12)
                ->withHeaders(['Accept' => 'application/rdap+json'])
                ->get(self::RDAP_BOOTSTRAP.urlencode($domain));
        } catch (\Throwable $e) {
            Log::warning('whois.rdap_failed', ['domain' => $domain, 'error' => $e->getMessage()]);

            return ['ok' => false, 'reason' => 'unavailable', 'domain' => $domain];
        }

        if ($response->status() === 404) {
            return ['ok' => true, 'domain' => $domain, 'registered' => false, 'source' => 'rdap'];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'reason' => 'unavailable', 'domain' => $domain];
        }

        return $this->parse($domain, $response->json() ?? []);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function parse(string $domain, array $data): array
    {
        $entities = is_array($data['entities'] ?? null) ? $data['entities'] : [];

        $registrarEntity = $this->findEntity($entities, 'registrar');
        $registrantEntity = $this->findEntity($entities, 'registrant');

        $registrarCard = $registrarEntity ? $this->vcard($registrarEntity) : [];
        $registrantCard = $registrantEntity ? $this->vcard($registrantEntity) : [];

        $events = $this->events($data['events'] ?? []);

        $nameservers = [];
        foreach ($data['nameservers'] ?? [] as $ns) {
            if (is_array($ns) && ! empty($ns['ldhName'])) {
                $nameservers[] = strtolower((string) $ns['ldhName']);
            }
        }

        $statuses = [];
        foreach ($data['status'] ?? [] as $st) {
            if (is_string($st) && $st !== '') {
                $statuses[] = $st;
            }
        }

        $registrantName = $this->cleanRedacted($registrantCard['fn'] ?? null);
        $registrantOrg = $this->cleanRedacted($registrantCard['org'] ?? null);

        return [
            'ok' => true,
            'domain' => $domain,
            'registered' => true,
            'registrar' => $this->cleanRedacted($registrarCard['fn'] ?? null),
            'registrant' => $registrantName,
            'registrant_org' => $registrantOrg,
            'registrant_country' => $this->countryFromCard($registrantCard),
            'created_at' => $events['registration'] ?? null,
            'updated_at' => $events['last changed'] ?? ($events['last update of rdap database'] ?? null),
            'expires_at' => $events['expiration'] ?? null,
            'name_servers' => $nameservers,
            'statuses' => $statuses,
            'privacy_protected' => $registrantName === null && $registrantOrg === null,
            'source' => 'rdap',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $entities
     * @return array<string, mixed>|null
     */
    private function findEntity(array $entities, string $role): ?array
    {
        foreach ($entities as $entity) {
            if (! is_array($entity)) {
                continue;
            }

            $roles = array_map('strtolower', array_map('strval', (array) ($entity['roles'] ?? [])));
            if (in_array($role, $roles, true)) {
                return $entity;
            }

            if (! empty($entity['entities']) && is_array($entity['entities'])) {
                $nested = $this->findEntity($entity['entities'], $role);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    /**
     * vCard (jCard) dizisini key => value haritasina cevirir.
     *
     * @param  array<string, mixed>  $entity
     * @return array<string, string>
     */
    private function vcard(array $entity): array
    {
        $out = [];
        $items = $entity['vcardArray'][1] ?? [];

        if (! is_array($items)) {
            return $out;
        }

        foreach ($items as $item) {
            if (! is_array($item) || count($item) < 4) {
                continue;
            }

            $key = strtolower((string) $item[0]);
            $value = $item[3];

            if (is_array($value)) {
                $value = implode(' ', array_filter(array_map('strval', $value)));
            }

            $value = trim((string) $value);
            if ($value !== '') {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $card
     */
    private function countryFromCard(array $card): ?string
    {
        // jCard "adr" alaninin son bileseni ulke koduna karsilik gelebilir.
        if (! empty($card['country-name'])) {
            return $card['country-name'];
        }

        return null;
    }

    /**
     * @param  mixed  $events
     * @return array<string, string>
     */
    private function events($events): array
    {
        $out = [];

        if (! is_array($events)) {
            return $out;
        }

        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }

            $action = strtolower((string) ($event['eventAction'] ?? ''));
            $date = (string) ($event['eventDate'] ?? '');

            if ($action !== '' && $date !== '') {
                $out[$action] = $date;
            }
        }

        return $out;
    }

    private function cleanRedacted(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $lower = strtolower($value);
        foreach (['redacted', 'privacy', 'not disclosed', 'data protected', 'withheld', 'gdpr', 'masked'] as $needle) {
            if (str_contains($lower, $needle)) {
                return null;
            }
        }

        return $value;
    }

    private function normalize(string $raw): string
    {
        $raw = trim(strtolower($raw));
        $raw = preg_replace('#^https?://#', '', $raw) ?? $raw;
        $raw = explode('/', $raw)[0] ?? $raw;
        $raw = ltrim($raw, '.');

        return preg_replace('/[^a-z0-9.\-]/', '', $raw) ?? '';
    }
}
