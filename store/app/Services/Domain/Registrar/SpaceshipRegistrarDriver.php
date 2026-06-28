<?php

namespace App\Services\Domain\Registrar;

use App\Models\DomainRegistrar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SpaceshipRegistrarDriver implements DomainManagementInterface, DomainRegistrarDriverInterface
{
    private const BASE = 'https://spaceship.dev/api';

    public function apiName(): string
    {
        return 'spaceship';
    }

    public function isConfigured(DomainRegistrar $account): bool
    {
        $creds = $account->credentials ?? [];

        return ! empty($creds['api_key']) && ! empty($creds['api_secret']);
    }

    public function testConnection(DomainRegistrar $account): array
    {
        if (! $this->isConfigured($account)) {
            return ['ok' => false, 'message' => 'API Key ve Secret gerekli.'];
        }

        $response = $this->request($account, 'get', self::BASE.'/v1/domains', ['take' => 1, 'skip' => 0]);

        if ($response->successful() || $response->status() === 403) {
            return ['ok' => true, 'message' => 'Spaceship API erişimi doğrulandı.'];
        }

        return ['ok' => false, 'message' => 'Spaceship bağlantısı başarısız (HTTP '.$response->status().').'];
    }

    public function fetchTldPricing(DomainRegistrar $account): array
    {
        if (! $this->isConfigured($account)) {
            return [];
        }

        $knownTlds = ['.com', '.net', '.org', '.io', '.dev', '.app', '.xyz', '.info', '.co', '.me'];
        $domains = [];
        foreach ($knownTlds as $tld) {
            $domains[] = 'hostvim-sync-'.Str::random(6).$tld;
        }

        $response = $this->request($account, 'post', self::BASE.'/v1/domains/available', [
            'domains' => $domains,
        ]);

        if (! $response->successful()) {
            return [];
        }

        $out = [];
        foreach ($response->json('domains') ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $domain = strtolower((string) ($row['domain'] ?? ''));
            $tld = $this->extractTld($domain);
            if ($tld === '') {
                continue;
            }
            $pricing = $this->extractPricing($row);
            if ($pricing === null) {
                continue;
            }
            $out[$tld] = $pricing;
        }

        return $out;
    }

    /**
     * Birden fazla alan adini tek istekte kontrol eder.
     *
     * @param  list<string>  $domains
     * @return array<string, array{available: bool, reason: ?string}>
     */
    public function checkAvailabilityBulk(DomainRegistrar $account, array $domains): array
    {
        if (! $this->isConfigured($account) || $domains === []) {
            return [];
        }

        $domains = array_values(array_unique(array_map(
            fn ($d) => strtolower(trim((string) $d)),
            $domains
        )));

        $response = $this->request($account, 'post', self::BASE.'/v1/domains/available', [
            'domains' => $domains,
        ]);

        if (! $response->successful()) {
            return [];
        }

        $out = [];
        foreach ($response->json('domains') ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $domain = strtolower((string) ($row['domain'] ?? ''));
            if ($domain === '') {
                continue;
            }
            $result = strtolower((string) ($row['result'] ?? ''));
            $available = in_array($result, ['available', 'premium'], true);
            $out[$domain] = [
                'available' => $available,
                'reason' => $available ? null : ($result ?: 'unavailable'),
            ];
        }

        return $out;
    }

    public function checkAvailability(DomainRegistrar $account, string $domain): array
    {
        if (! $this->isConfigured($account)) {
            return ['available' => false, 'currency' => 'USD', 'reason' => 'not_configured'];
        }

        // Tekil GET /available ucu domain basina 5 istek/300sn ile cok kisitli
        // (429 doner). Bulk POST /available ucu daha cömert oldugundan tekil
        // sorgu da bulk uzerinden yapilir.
        $key = strtolower(trim($domain));
        $bulk = $this->checkAvailabilityBulk($account, [$key]);

        if (isset($bulk[$key])) {
            return [
                'available' => $bulk[$key]['available'],
                'currency' => 'USD',
                'reason' => $bulk[$key]['reason'],
            ];
        }

        return ['available' => false, 'currency' => 'USD', 'reason' => 'api_error'];
    }

 
    private function request(DomainRegistrar $account, string $method, string $url, array $payload = [])
    {
        $creds = $account->credentials ?? [];
        $pending = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'X-API-Key' => (string) ($creds['api_key'] ?? ''),
                'X-API-Secret' => (string) ($creds['api_secret'] ?? ''),
            ]);

        return match ($method) {
            'get' => $pending->get($url, $payload),
            'post' => $pending->post($url, $payload),
            'put' => $pending->put($url, $payload),
            'delete' => $pending->delete($url, $payload),
            default => $pending->get($url),
        };
    }

    public function listDomains(DomainRegistrar $account): array
    {
        $out = [];
        $skip = 0;
        $take = 100;
        for ($page = 0; $page < 50; $page++) {
            $response = $this->request($account, 'get', self::BASE.'/v1/domains', ['take' => $take, 'skip' => $skip]);
            if (! $response->successful()) {
                break;
            }
            $items = $response->json('items') ?? [];
            foreach ($items as $row) {
                if (is_array($row)) {
                    $out[] = $this->normalizeDomain($row);
                }
            }
            $total = (int) ($response->json('total') ?? 0);
            $skip += $take;
            if ($skip >= $total || $items === []) {
                break;
            }
        }

        return $out;
    }

    public function getDomainInfo(DomainRegistrar $account, string $domain): ?array
    {
        $response = $this->request($account, 'get', self::BASE.'/v1/domains/'.urlencode($domain));
        if (! $response->successful()) {
            return null;
        }

        return $this->normalizeDomain($response->json() ?? []);
    }

    public function setNameservers(DomainRegistrar $account, string $domain, string $provider, array $hosts = []): array
    {
        $provider = $provider === 'custom' ? 'custom' : 'basic';
        $payload = ['provider' => $provider];
        if ($provider === 'custom') {
            $hosts = array_values(array_filter(array_map(fn ($h) => strtolower(trim((string) $h)), $hosts)));
            if (count($hosts) < 2) {
                return ['ok' => false, 'message' => 'Özel nameserver için en az 2 adet sunucu gerekir.'];
            }
            $payload['hosts'] = $hosts;
        }

        $response = $this->request($account, 'put', self::BASE.'/v1/domains/'.urlencode($domain).'/nameservers', $payload);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful() ? 'Nameserver güncellendi.' : $this->errorMessage($response, 'Nameserver güncellenemedi'),
        ];
    }

    public function setPrivacy(DomainRegistrar $account, string $domain, string $level): array
    {
        $level = in_array($level, ['public', 'high'], true) ? $level : 'high';
        $response = $this->request($account, 'put', self::BASE.'/v1/domains/'.urlencode($domain).'/privacy/preference', [
            'privacyLevel' => $level,
            'userConsent' => true,
        ]);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful()
                ? ($level === 'high' ? 'WHOIS gizliliği açıldı.' : 'WHOIS gizliliği kapatıldı.')
                : $this->errorMessage($response, 'Gizlilik güncellenemedi'),
        ];
    }

    public function setAutoRenew(DomainRegistrar $account, string $domain, bool $enabled): array
    {
        $response = $this->request($account, 'put', self::BASE.'/v1/domains/'.urlencode($domain).'/autorenew', [
            'isEnabled' => $enabled,
        ]);

        return [
            'ok' => $response->successful(),
            'message' => $response->successful()
                ? ($enabled ? 'Otomatik yenileme açıldı.' : 'Otomatik yenileme kapatıldı.')
                : $this->errorMessage($response, 'Otomatik yenileme güncellenemedi'),
        ];
    }

    public function renewDomain(DomainRegistrar $account, string $domain, int $years): array
    {
        $years = max(1, min(10, $years));
        $info = $this->getDomainInfo($account, $domain);
        $currentExpiry = $info['expires_at'] ?? null;
        if (! $currentExpiry) {
            return ['ok' => false, 'message' => 'Domain bitiş tarihi alınamadı; yenileme yapılamadı.'];
        }

        $response = $this->request($account, 'post', self::BASE.'/v1/domains/'.urlencode($domain).'/renew', [
            'years' => $years,
            'currentExpirationDate' => $currentExpiry,
        ]);
        if (! $response->successful()) {
            return ['ok' => false, 'message' => $this->errorMessage($response, 'Yenileme başarısız')];
        }

        $fresh = $this->getDomainInfo($account, $domain);

        return [
            'ok' => true,
            'message' => $years.' yıl yenileme başarılı.',
            'expires_at' => $fresh['expires_at'] ?? null,
        ];
    }

    public function getDnsRecords(DomainRegistrar $account, string $domain): array
    {
        $out = [];
        foreach ($this->getDnsRecordsRaw($account, $domain) as $raw) {
            $out[] = $this->normalizeDnsRecord($raw);
        }

        return $out;
    }

    public function syncDnsRecords(DomainRegistrar $account, string $domain, array $records): array
    {
        $rawCurrent = $this->getDnsRecordsRaw($account, $domain);

        // Hedef kayitlari Spaceship item formatina cevir.
        $targetItems = [];
        $targetKeys = [];
        foreach ($records as $rec) {
            $item = $this->buildDnsItem($rec);
            if ($item === null) {
                continue;
            }
            $targetItems[] = $item;
            $targetKeys[$this->dnsKey($this->normalizeDnsRecord($item))] = true;
        }

        // Hedefte olmayan mevcut kayitlari sil.
        $toDelete = [];
        foreach ($rawCurrent as $raw) {
            $norm = $this->normalizeDnsRecord($raw);
            // Sistemsel/silinemez kayitlari atla (SOA, default NS bos isim).
            if (in_array(strtoupper($norm['type']), ['SOA'], true)) {
                continue;
            }
            if (! isset($targetKeys[$this->dnsKey($norm)])) {
                $del = ['type' => $raw['type'] ?? $norm['type'], 'name' => $raw['name'] ?? $norm['name']];
                foreach (['address', 'cname', 'exchange', 'value'] as $vf) {
                    if (isset($raw[$vf])) {
                        $del[$vf] = $raw[$vf];
                    }
                }
                $toDelete[] = $del;
            }
        }

        if ($toDelete !== []) {
            $delResp = $this->request($account, 'delete', self::BASE.'/v1/dns/records/'.urlencode($domain), $toDelete);
            if (! $delResp->successful() && $delResp->status() !== 204) {
                return ['ok' => false, 'message' => $this->errorMessage($delResp, 'DNS kayıtları silinemedi')];
            }
        }

        if ($targetItems !== []) {
            $saveResp = $this->request($account, 'put', self::BASE.'/v1/dns/records/'.urlencode($domain), [
                'force' => true,
                'items' => $targetItems,
            ]);
            if (! $saveResp->successful() && $saveResp->status() !== 204) {
                return ['ok' => false, 'message' => $this->errorMessage($saveResp, 'DNS kayıtları kaydedilemedi')];
            }
        }

        return ['ok' => true, 'message' => 'DNS kayıtları güncellendi.'];
    }

    public function registerDomain(DomainRegistrar $account, string $domain, int $years, bool $autoRenew, bool $privacyHigh, ?array $registrant = null): array
    {
        // Once domaini musteri adina kaydetmeyi dene; veri eksik/gecersizse hesabin
        // varsayilan contact'ina dusulur (domain yine de kaydedilir).
        $contacts = null;
        if ($registrant !== null) {
            $contacts = $this->customerContacts($account, $registrant);
        }
        if ($contacts === null) {
            $contacts = $this->defaultContacts($account);
        }
        if ($contacts === null) {
            return ['ok' => false, 'message' => 'Spaceship hesabınızda kayıtlı bir iletişim (contact) bulunamadı. Otomatik kayıt için hesabınızda en az bir domain/iletişim tanımlı olmalı.'];
        }

        $response = $this->request($account, 'post', self::BASE.'/v1/domains/'.urlencode($domain), [
            'autoRenew' => $autoRenew,
            'years' => max(1, min(10, $years)),
            'privacyProtection' => ['level' => $privacyHigh ? 'high' : 'public', 'userConsent' => true],
            'contacts' => $contacts,
        ]);
        if (! $response->successful() && $response->status() !== 202) {
            return ['ok' => false, 'message' => $this->errorMessage($response, 'Domain kaydı başarısız')];
        }

        $info = $this->getDomainInfo($account, $domain);

        return [
            'ok' => true,
            'message' => 'Domain başarıyla kaydedildi.',
            'expires_at' => $info['expires_at'] ?? null,
            'status' => $info['status'] ?? 'registered',
        ];
    }

    /**
     * Musteri bilgileriyle yeni bir Spaceship contact'i olusturur ve domain icin
     * contact setini doner. Zorunlu alan eksik veya API hatasi olursa null doner.
     *
     * @param  array<string, mixed>  $registrant
     * @return array{registrant: string, admin: string, tech: string, billing: string, attributes: list<string>}|null
     */
    private function customerContacts(DomainRegistrar $account, array $registrant): ?array
    {
        $payload = $this->mapRegistrant($registrant);
        if ($payload === null) {
            return null;
        }

        $contactId = $this->createContact($account, $payload);
        if ($contactId === null) {
            return null;
        }

        // Bazi TLD'ler register sirasinda attribute bekler; mevcut domainden devral.
        $attributes = $this->defaultContacts($account)['attributes'] ?? [];

        return [
            'registrant' => $contactId,
            'admin' => $contactId,
            'tech' => $contactId,
            'billing' => $contactId,
            'attributes' => $attributes,
        ];
    }

    /**
     * Spaceship'te contact olusturur, contactId doner.
     *
     * @param  array<string, mixed>  $payload
     */
    private function createContact(DomainRegistrar $account, array $payload): ?string
    {
        $response = $this->request($account, 'put', self::BASE.'/v1/contacts', $payload);
        if (! $response->successful()) {
            Log::warning('spaceship.contact_create_failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return null;
        }

        $id = $response->json('contactId');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * Musteri verisini Spaceship contact body'sine cevirir. Zorunlu alan eksikse null.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>|null
     */
    private function mapRegistrant(array $r): ?array
    {
        [$first, $last] = $this->splitName((string) ($r['name'] ?? ''));
        $email = trim((string) ($r['email'] ?? ''));
        $address = trim((string) ($r['address'] ?? ''));
        $city = trim((string) ($r['city'] ?? ''));
        $country = $this->normalizeCountry($r['country'] ?? null);
        $phone = $this->normalizePhone($r['phone'] ?? null);

        if ($first === '' || $last === '' || $email === '' || $address === '' || $city === '' || $phone === null) {
            return null;
        }
        // Spaceship ad/soyad deseni yalnizca ASCII harf kabul eder.
        if (! preg_match('/^[A-Za-z][A-Za-z\s\'-]*$/', $first) || ! preg_match('/^[A-Za-z][A-Za-z\s\'-]*$/', $last)) {
            return null;
        }

        $payload = [
            'firstName' => mb_substr($first, 0, 125),
            'lastName' => mb_substr($last, 0, 125),
            'email' => $email,
            'address1' => mb_substr($address, 0, 255),
            'city' => mb_substr($city, 0, 255),
            'country' => $country,
            'phone' => $phone,
        ];
        if (! empty($r['postal_code'])) {
            $payload['postalCode'] = mb_substr((string) $r['postal_code'], 0, 16);
        }
        if (! empty($r['company'])) {
            $payload['organization'] = mb_substr((string) $r['company'], 0, 255);
        }

        return $payload;
    }

    /** @return array{0: string, 1: string} [firstName, lastName] */
    private function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if ($name === '') {
            return ['', ''];
        }
        $parts = explode(' ', $name);
        if (count($parts) === 1) {
            return [$parts[0], $parts[0]];
        }
        $last = array_pop($parts);

        return [implode(' ', $parts), $last];
    }

    private function normalizeCountry(mixed $country): string
    {
        $c = strtoupper(trim((string) $country));
        if (preg_match('/^[A-Z]{2}$/', $c)) {
            return $c;
        }
        $map = ['TÜRKIYE' => 'TR', 'TURKIYE' => 'TR', 'TURKEY' => 'TR', 'TÜRKİYE' => 'TR'];

        return $map[$c] ?? 'TR';
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        $digits = ltrim($digits, '0');
        if (str_starts_with($digits, '90') && strlen($digits) > 10) {
            $rest = substr($digits, 2);
        } else {
            $rest = $digits;
        }
        if (strlen($rest) < 4) {
            return null;
        }

        return '+90.'.$rest;
    }

    /**
     * Hesaptaki mevcut bir domainin contact ID'lerini doner (yeni kayit icin yeniden kullanim).
     *
     * @return array{registrant: string, admin: string, tech: string, billing: string, attributes: list<string>}|null
     */
    private function defaultContacts(DomainRegistrar $account): ?array
    {
        $response = $this->request($account, 'get', self::BASE.'/v1/domains', ['take' => 1, 'skip' => 0]);
        if (! $response->successful()) {
            return null;
        }
        $items = $response->json('items') ?? [];
        $c = $items[0]['contacts'] ?? null;
        if (! is_array($c) || empty($c['registrant'])) {
            return null;
        }

        $registrant = (string) $c['registrant'];

        return [
            'registrant' => $registrant,
            'admin' => (string) ($c['admin'] ?? $registrant),
            'tech' => (string) ($c['tech'] ?? $registrant),
            'billing' => (string) ($c['billing'] ?? $registrant),
            'attributes' => is_array($c['attributes'] ?? null) ? array_values($c['attributes']) : [],
        ];
    }

    public function getAuthCode(DomainRegistrar $account, string $domain): array
    {
        $response = $this->request($account, 'get', self::BASE.'/v1/domains/'.urlencode($domain).'/transfer/auth-code');
        if (! $response->successful()) {
            return ['ok' => false, 'code' => null, 'message' => $this->errorMessage($response, 'Auth kodu alınamadı')];
        }

        $code = $response->json('authCode') ?? $response->json('code') ?? $response->json('eppCode');

        return ['ok' => true, 'code' => is_string($code) ? $code : null, 'message' => 'Auth kodu alındı.'];
    }

    /** @return list<array<string, mixed>> */
    private function getDnsRecordsRaw(DomainRegistrar $account, string $domain): array
    {
        $out = [];
        $skip = 0;
        $take = 100;
        for ($page = 0; $page < 50; $page++) {
            $response = $this->request($account, 'get', self::BASE.'/v1/dns/records/'.urlencode($domain), [
                'take' => $take,
                'skip' => $skip,
            ]);
            if (! $response->successful()) {
                break;
            }
            $items = $response->json('items') ?? [];
            foreach ($items as $row) {
                if (is_array($row)) {
                    $out[] = $row;
                }
            }
            $total = (int) ($response->json('total') ?? 0);
            $skip += $take;
            if ($skip >= $total || $items === []) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  array{type?: string, name?: string, value?: string, ttl?: int, priority?: ?int}  $rec
     * @return array<string, mixed>|null
     */
    private function buildDnsItem(array $rec): ?array
    {
        $type = strtoupper(trim((string) ($rec['type'] ?? '')));
        $name = trim((string) ($rec['name'] ?? '@')) ?: '@';
        $value = trim((string) ($rec['value'] ?? ''));
        $ttl = (int) ($rec['ttl'] ?? 3600);
        if ($ttl < 60) {
            $ttl = 3600;
        }
        if ($type === '' || $value === '') {
            return null;
        }

        $item = ['type' => $type, 'name' => $name, 'ttl' => $ttl];

        return match ($type) {
            'A', 'AAAA' => $item + ['address' => $value],
            'CNAME', 'ALIAS' => $item + ['cname' => rtrim($value, '.').'.'],
            'MX' => $item + ['exchange' => rtrim($value, '.').'.', 'preference' => (int) ($rec['priority'] ?? 10)],
            default => $item + ['value' => $value],
        };
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{type: string, name: string, value: string, ttl: int, priority: ?int}
     */
    private function normalizeDnsRecord(array $raw): array
    {
        $type = strtoupper((string) ($raw['type'] ?? ''));
        $value = (string) ($raw['address'] ?? $raw['cname'] ?? $raw['exchange'] ?? $raw['value'] ?? '');
        $priority = isset($raw['preference']) ? (int) $raw['preference'] : null;

        return [
            'type' => $type,
            'name' => (string) ($raw['name'] ?? '@'),
            'value' => rtrim($value, '.'),
            'ttl' => (int) ($raw['ttl'] ?? 3600),
            'priority' => $priority,
        ];
    }

    /** @param array{type: string, name: string, value: string, priority: ?int} $r */
    private function dnsKey(array $r): string
    {
        return strtoupper($r['type']).'|'.strtolower($r['name']).'|'.strtolower(rtrim($r['value'], '.')).'|'.($r['priority'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{domain: string, expires_at: ?string, registered_at: ?string, auto_renew: bool, privacy: ?string, locked: bool, status: ?string, ns_provider: ?string, nameservers: list<string>}
     */
    private function normalizeDomain(array $row): array
    {
        $ns = $row['nameservers'] ?? [];
        $epp = $row['eppStatuses'] ?? [];

        return [
            'domain' => strtolower((string) ($row['name'] ?? $row['unicodeName'] ?? '')),
            'expires_at' => $row['expirationDate'] ?? null,
            'registered_at' => $row['registrationDate'] ?? null,
            'auto_renew' => (bool) ($row['autoRenew'] ?? false),
            'privacy' => is_array($row['privacyProtection'] ?? null) ? ($row['privacyProtection']['level'] ?? null) : null,
            'locked' => is_array($epp) && in_array('clientTransferProhibited', $epp, true),
            'status' => $row['lifecycleStatus'] ?? null,
            'ns_provider' => is_array($ns) ? ($ns['provider'] ?? null) : null,
            'nameservers' => is_array($ns) && is_array($ns['hosts'] ?? null) ? array_values($ns['hosts']) : [],
        ];
    }

    private function errorMessage($response, string $fallback): string
    {
        $detail = $response->json('detail') ?? $response->json('message');

        return $fallback.': '.(is_string($detail) ? $detail : ('HTTP '.$response->status()));
    }

    /** @return array{register: float, renew: float, currency: string}|null */
    private function extractPricing(array $row): ?array
    {
        $register = 0.0;
        $renew = 0.0;
        $currency = 'USD';

        foreach ($row['premiumPricing'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $operation = strtolower((string) ($item['operation'] ?? ''));
            $price = (float) ($item['price'] ?? 0);
            $currency = strtoupper((string) ($item['currency'] ?? 'USD'));
            if ($operation === 'register') {
                $register = $price;
            }
            if ($operation === 'renew') {
                $renew = $price;
            }
        }

        if ($register <= 0 && $renew <= 0) {
            return null;
        }

        return [
            'register' => $register > 0 ? $register : $renew,
            'renew' => $renew > 0 ? $renew : $register,
            'currency' => $currency,
        ];
    }

    private function extractTld(string $domain): string
    {
        $domain = strtolower(trim($domain));
        if ($domain === '' || ! str_contains($domain, '.')) {
            return '';
        }

        if (preg_match('/\.(com|net|org)\.tr$/', $domain)) {
            return substr($domain, strrpos($domain, '.', -5));
        }

        return '.'.explode('.', $domain, 2)[1] ?? '';
    }
}
