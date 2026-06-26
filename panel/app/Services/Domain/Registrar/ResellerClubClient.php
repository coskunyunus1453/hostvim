<?php

namespace App\Services\Domain\Registrar;

use App\Services\Billing\BillingSettings;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ResellerClubClient
{
    public function __construct(private BillingSettings $settings) {}

    public function isConfigured(): bool
    {
        return $this->authUserId() !== '' && $this->apiKey() !== '';
    }

    public function isDomainAvailable(string $fqdn): bool
    {
        [$label, $tld] = $this->splitDomain($fqdn);

        $result = $this->request('domains/available.json', [
            'domain-name' => $label,
            'tlds' => $tld,
        ]);

        $candidates = [
            strtolower($label.'.'.$tld),
            $label.'.'.$tld,
        ];
        foreach ($candidates as $key) {
            if (isset($result[$key]) && is_array($result[$key])) {
                return ($result[$key]['status'] ?? '') === 'available';
            }
        }

        foreach ($result as $entry) {
            if (is_array($entry) && ($entry['status'] ?? '') === 'available') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $nameservers
     * @return array{entityid: ?string, actionstatus: string}
     */
    public function registerDomain(string $fqdn, int $years, int $customerId, int $contactId, array $nameservers): array
    {
        $params = [
            'domain-name' => $fqdn,
            'years' => (string) max(1, min(10, $years)),
            'customer-id' => (string) $customerId,
            'reg-contact-id' => (string) $contactId,
            'admin-contact-id' => (string) $contactId,
            'tech-contact-id' => (string) $contactId,
            'billing-contact-id' => (string) $contactId,
            'invoice-option' => 'NoInvoice',
            'purchase-privacy' => 'false',
        ];

        $ns = array_values(array_filter(array_map('strtolower', $nameservers)));
        if (count($ns) < 2) {
            throw new RuntimeException('En az iki nameserver gerekli.');
        }
        $params['ns'] = $ns;

        $result = $this->request('domains/register.json', $params, 'POST');

        return [
            'entityid' => isset($result['entityid']) ? (string) $result['entityid'] : (isset($result['orderid']) ? (string) $result['orderid'] : null),
            'actionstatus' => (string) ($result['actionstatus'] ?? $result['status'] ?? 'Success'),
        ];
    }

    public function ensureCustomer(\App\Models\User $user): int
    {
        if ($user->resellerclub_customer_id) {
            return (int) $user->resellerclub_customer_id;
        }

        $preset = (int) $this->settings->get('resellerclub_customer_id', 0);
        if ($preset > 0) {
            $user->forceFill(['resellerclub_customer_id' => $preset])->save();

            return $preset;
        }

        $username = 'pz'.$user->id.'_'.substr(sha1($user->email), 0, 10);
        $parts = explode(' ', trim($user->name ?: 'Müşteri'), 2);

        $result = $this->request('customers/signup.json', [
            'username' => $username,
            'passwd' => bin2hex(random_bytes(16)),
            'name' => $parts[0] ?: 'Müşteri',
            'company' => $parts[0] ?: 'Müşteri',
            'email' => $user->email,
            'address-line-1' => 'N/A',
            'city' => 'Istanbul',
            'state' => 'Istanbul',
            'country' => 'TR',
            'zipcode' => '34000',
            'phone-cc' => '90',
            'phone' => '5000000000',
            'lang-pref' => 'tr',
        ], 'POST');

        $customerId = (int) ($result['customerid'] ?? $result['customer_id'] ?? 0);
        if ($customerId <= 0) {
            throw new RuntimeException('ResellerClub müşteri oluşturulamadı.');
        }

        $user->forceFill(['resellerclub_customer_id' => $customerId])->save();

        return $customerId;
    }

    public function ensureContact(int $customerId, \App\Models\User $user): int
    {
        if ($user->resellerclub_contact_id) {
            return (int) $user->resellerclub_contact_id;
        }

        $parts = explode(' ', trim($user->name ?: 'Müşteri'), 2);

        $result = $this->request('contacts/add.json', [
            'customer-id' => (string) $customerId,
            'name' => $parts[0] ?: 'Müşteri',
            'company' => $parts[0] ?: 'Müşteri',
            'email' => $user->email,
            'address-line-1' => 'N/A',
            'city' => 'Istanbul',
            'state' => 'Istanbul',
            'country' => 'TR',
            'zipcode' => '34000',
            'phone-cc' => '90',
            'phone' => '5000000000',
            'type' => 'Contact',
        ], 'POST');

        $contactId = (int) ($result['contactid'] ?? $result['contact_id'] ?? 0);
        if ($contactId <= 0) {
            throw new RuntimeException('ResellerClub iletişim kaydı oluşturulamadı.');
        }

        $user->forceFill(['resellerclub_contact_id' => $contactId])->save();

        return $contactId;
    }

    /** @return array{ok: bool, message: string} */
    public function ping(): array
    {
        try {
            $this->request('domains/available.json', [
                'domain-name' => 'panelze-healthcheck',
                'tlds' => 'com',
            ]);

            return ['ok' => true, 'message' => 'ResellerClub API erişilebilir.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /** @return array<string, mixed> */
    private function request(string $path, array $params = [], string $method = 'GET'): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('ResellerClub yapılandırılmamış.');
        }

        $auth = [
            'auth-userid' => $this->authUserId(),
            'api-key' => $this->apiKey(),
        ];

        $url = rtrim($this->baseUrl(), '/').'/'.ltrim($path, '/');

        $response = $method === 'POST'
            ? Http::asForm()->timeout(60)->post($url, array_merge($auth, $params))
            : Http::timeout(45)->get($url, array_merge($auth, $params));

        if (! $response->successful()) {
            throw new RuntimeException('ResellerClub HTTP '.$response->status());
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('ResellerClub geçersiz yanıt.');
        }

        if (isset($json['status']) && strtoupper((string) $json['status']) === 'ERROR') {
            throw new RuntimeException((string) ($json['message'] ?? 'ResellerClub hata'));
        }

        return $json;
    }

    /** @return array{0: string, 1: string} */
    private function splitDomain(string $fqdn): array
    {
        $fqdn = strtolower(trim($fqdn));
        if (preg_match('/\.(com|net|org)\.tr$/', $fqdn)) {
            $parts = explode('.', $fqdn);

            return [implode('.', array_slice($parts, 0, -2)), implode('.', array_slice($parts, -2))];
        }
        $parts = explode('.', $fqdn);

        return [implode('.', array_slice($parts, 0, -1)), (string) end($parts)];
    }

    private function baseUrl(): string
    {
        return (bool) $this->settings->get('resellerclub_test_mode', false)
            ? 'https://test.httpapi.com/api'
            : 'https://httpapi.com/api';
    }

    private function authUserId(): string
    {
        return trim((string) $this->settings->get('resellerclub_auth_userid', ''));
    }

    private function apiKey(): string
    {
        return trim((string) $this->settings->get('resellerclub_api_key', ''));
    }
}
