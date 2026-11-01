<?php

namespace App\Services\Domain\Registrar;

use App\Models\DomainRegistrar;
use DomainNameApi\DomainNameAPI_PHPLibrary;
use Throwable;

/**
 * DomainNameAPI (domainnameapi.com) — resmi php-dna kütüphanesi üzerinden.
 *
 * Kimlik bilgisi:
 * - Yeni panel: Reseller ID (UUID) + API Key → REST
 * - Eski panel: API kullanıcı adı + şifre → SOAP
 *
 * @see https://github.com/domainreseller/php-dna
 */
class DomainNameApiRegistrarDriver implements DomainRegistrarDriverInterface, DomainManagementInterface
{
    public function apiName(): string
    {
        return 'domainnameapi';
    }

    public function isConfigured(DomainRegistrar $account): bool
    {
        $creds = $account->credentials ?? [];

        return ! empty($creds['username']) && ! empty($creds['password']);
    }

    public function testConnection(DomainRegistrar $account): array
    {
        if (! $this->isConfigured($account)) {
            return ['ok' => false, 'message' => 'Reseller ID/API kullanıcı adı ve API Key/şifre gerekli.'];
        }

        try {
            $client = $this->client($account);
            $result = $client->getResellerDetails();
            if (! $this->isOk($result)) {
                return ['ok' => false, 'message' => $this->errorMessage($result, 'DomainNameAPI bağlantısı başarısız.')];
            }

            $name = (string) ($result['name'] ?? $result['data']['name'] ?? 'hesap');
            $balance = $result['balance'] ?? $result['data']['balance'] ?? null;
            $currency = (string) ($result['currency'] ?? $result['data']['currency'] ?? 'USD');
            $msg = 'DomainNameAPI bağlantısı başarılı ('.$name.')';
            if ($balance !== null && $balance !== '') {
                $msg .= ' — bakiye: '.$balance.' '.$currency;
            }

            return ['ok' => true, 'message' => $msg];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function fetchTldPricing(DomainRegistrar $account): array
    {
        if (! $this->isConfigured($account)) {
            return [];
        }

        try {
            $client = $this->client($account);
            // Büyük katalog; API MaxResultCount ile sınırlı.
            $result = $client->getTldList(2000);
        } catch (Throwable) {
            return [];
        }

        if (! $this->isOk($result)) {
            return [];
        }

        $rows = $result['data'] ?? [];
        if (! is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $tldRaw = (string) ($row['tld'] ?? $row['Tld'] ?? $row['name'] ?? '');
            if ($tldRaw === '') {
                continue;
            }
            $status = strtolower((string) ($row['status'] ?? 'active'));
            if ($status !== '' && ! in_array($status, ['active', '1', 'true'], true)) {
                continue;
            }

            $pricing = is_array($row['pricing'] ?? null) ? $row['pricing'] : [];
            $currencies = is_array($row['currencies'] ?? null) ? $row['currencies'] : [];

            $register = $this->periodPrice($pricing['registration'] ?? $pricing['register'] ?? null);
            $renew = $this->periodPrice($pricing['renew'] ?? $pricing['renewal'] ?? null);
            $transfer = $this->periodPrice($pricing['transfer'] ?? null);
            if ($register <= 0 && $renew <= 0) {
                continue;
            }

            $currency = (string) (
                $currencies['registration']
                ?? $currencies['register']
                ?? $currencies['renew']
                ?? 'USD'
            );
            if ($currency === '') {
                $currency = 'USD';
            }

            $tld = '.'.ltrim(strtolower($tldRaw), '.');
            // HostVim'de DomainNameAPI yalnızca .tr ailesi için kullanılır.
            if (! $account->supportsTld($tld)) {
                continue;
            }
            $out[$tld] = [
                'register' => $register > 0 ? $register : $renew,
                'renew' => $renew > 0 ? $renew : $register,
                'transfer' => $transfer > 0 ? $transfer : null,
                'currency' => strtoupper($currency),
            ];
        }

        return $out;
    }

    public function checkAvailability(DomainRegistrar $account, string $domain): array
    {
        if (! $this->isConfigured($account)) {
            return ['available' => false, 'currency' => 'USD', 'reason' => 'not_configured'];
        }

        [$sld, $tld] = $this->splitDomain($domain);
        if ($sld === '' || $tld === '') {
            return ['available' => false, 'currency' => 'USD', 'reason' => 'invalid_domain'];
        }
        if (! $account->supportsTld('.'.$tld)) {
            return ['available' => false, 'currency' => 'USD', 'reason' => 'tld_not_supported'];
        }

        try {
            $client = $this->client($account);
            $result = $client->checkAvailability([$sld], [$tld], 1, 'create');
        } catch (Throwable $e) {
            return ['available' => false, 'currency' => 'USD', 'reason' => $e->getMessage()];
        }

        $row = $this->matchAvailabilityRow($result, $sld, $tld);
        if ($row === null) {
            return [
                'available' => false,
                'currency' => 'USD',
                'reason' => $this->errorMessage($result, 'api_error'),
            ];
        }

        $status = strtolower((string) ($row['Status'] ?? $row['status'] ?? ''));
        $available = in_array($status, ['available', 'avail'], true);
        $price = $this->money($row['Price'] ?? $row['price'] ?? null);
        $currency = strtoupper((string) ($row['Currency'] ?? $row['currency'] ?? 'USD')) ?: 'USD';

        return [
            'available' => $available,
            'register_price' => $price > 0 ? $price : null,
            'renew_price' => $price > 0 ? $price : null,
            'currency' => $currency,
            'reason' => $available ? null : (string) ($row['Reason'] ?? $row['reason'] ?? 'unavailable'),
        ];
    }

    public function listDomains(DomainRegistrar $account): array
    {
        if (! $this->isConfigured($account)) {
            return [];
        }

        try {
            $client = $this->client($account);
            $result = $client->getList([
                'OrderColumn' => 'Id',
                'OrderDirection' => 'ASC',
                'PageNumber' => 0,
                'PageSize' => 1000,
            ]);
        } catch (Throwable) {
            return [];
        }

        if (! $this->isOk($result)) {
            return [];
        }

        $domains = $result['data']['Domains'] ?? $result['data']['domains'] ?? $result['data'] ?? [];
        if (! is_array($domains)) {
            return [];
        }

        $out = [];
        foreach ($domains as $row) {
            if (is_array($row)) {
                $out[] = $this->normalizeDomain($row);
            }
        }

        return $out;
    }

    public function getDomainInfo(DomainRegistrar $account, string $domain): ?array
    {
        if (! $this->isConfigured($account)) {
            return null;
        }

        try {
            $client = $this->client($account);
            $result = $client->getDetails($domain);
        } catch (Throwable) {
            return null;
        }

        if (! $this->isOk($result)) {
            return null;
        }

        $data = $result['data'] ?? null;

        return is_array($data) ? $this->normalizeDomain($data) : null;
    }

    public function setNameservers(DomainRegistrar $account, string $domain, string $provider, array $hosts = []): array
    {
        $hosts = array_values(array_filter(array_map(
            fn ($h) => strtolower(trim((string) $h)),
            $hosts
        )));
        if (count($hosts) < 2) {
            return ['ok' => false, 'message' => 'En az 2 nameserver gerekli.'];
        }

        try {
            $result = $this->client($account)->modifyNameServer($domain, $hosts);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return [
            'ok' => $this->isOk($result),
            'message' => $this->isOk($result) ? 'Nameserver güncellendi.' : $this->errorMessage($result, 'Nameserver güncellenemedi'),
        ];
    }

    public function setPrivacy(DomainRegistrar $account, string $domain, string $level): array
    {
        $enabled = $level === 'high';
        try {
            $result = $this->client($account)->modifyPrivacyProtectionStatus($domain, $enabled, 'Owner request');
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return [
            'ok' => $this->isOk($result),
            'message' => $this->isOk($result)
                ? ($enabled ? 'WHOIS gizliliği açıldı.' : 'WHOIS gizliliği kapatıldı.')
                : $this->errorMessage($result, 'Gizlilik güncellenemedi'),
        ];
    }

    public function setAutoRenew(DomainRegistrar $account, string $domain, bool $enabled): array
    {
        return [
            'ok' => false,
            'message' => 'DomainNameAPI bu API üzerinden otomatik yenileme aç/kapa desteklemiyor; panelden yönetin.',
        ];
    }

    public function renewDomain(DomainRegistrar $account, string $domain, int $years): array
    {
        $years = max(1, min(10, $years));
        try {
            $result = $this->client($account)->renew($domain, $years);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        if (! $this->isOk($result)) {
            return ['ok' => false, 'message' => $this->errorMessage($result, 'Yenileme başarısız')];
        }

        $expires = $result['data']['ExpirationDate'] ?? $result['data']['Dates']['Expiration'] ?? null;
        if (! $expires) {
            $info = $this->getDomainInfo($account, $domain);
            $expires = $info['expires_at'] ?? null;
        }

        return [
            'ok' => true,
            'message' => $years.' yıl yenileme başarılı.',
            'expires_at' => is_string($expires) ? $expires : null,
        ];
    }

    public function getDnsRecords(DomainRegistrar $account, string $domain): array
    {
        try {
            $result = $this->client($account)->getResourceRecords($domain);
        } catch (Throwable) {
            return [];
        }

        if (! $this->isOk($result)) {
            return [];
        }

        $records = $result['data']['records'] ?? $result['data'] ?? [];
        if (! is_array($records)) {
            return [];
        }

        $out = [];
        foreach ($records as $row) {
            if (! is_array($row)) {
                continue;
            }
            $type = strtoupper((string) ($row['Type'] ?? $row['type'] ?? ''));
            $value = (string) ($row['Content'] ?? $row['content'] ?? $row['Value'] ?? '');
            if ($type === '' || $value === '') {
                continue;
            }
            $out[] = [
                'type' => $type,
                'name' => (string) ($row['Name'] ?? $row['name'] ?? '@'),
                'value' => $value,
                'ttl' => (int) ($row['TTL'] ?? $row['ttl'] ?? 3600),
                'priority' => isset($row['Priority']) ? (int) $row['Priority'] : (isset($row['priority']) ? (int) $row['priority'] : null),
            ];
        }

        return $out;
    }

    public function syncDnsRecords(DomainRegistrar $account, string $domain, array $records): array
    {
        try {
            $client = $this->client($account);
            $current = $this->getDnsRecords($account, $domain);

            // Mevcut kayıtları temizle (SOA hariç).
            foreach ($current as $rec) {
                $type = strtoupper((string) ($rec['type'] ?? ''));
                if ($type === '' || $type === 'SOA') {
                    continue;
                }
                $client->deleteResourceRecord(
                    $domain,
                    (string) ($rec['name'] ?? ''),
                    (string) ($rec['value'] ?? ''),
                    $type,
                    false
                );
            }

            foreach ($records as $rec) {
                if (! is_array($rec)) {
                    continue;
                }
                $type = strtoupper((string) ($rec['type'] ?? ''));
                $name = (string) ($rec['name'] ?? '@');
                $value = (string) ($rec['value'] ?? '');
                if ($type === '' || $value === '' || $type === 'SOA') {
                    continue;
                }
                $ttl = (int) ($rec['ttl'] ?? 3600);
                $client->addResourceRecord($domain, $name, $type, $value, max(60, $ttl), false);
            }

            $apply = $client->applyResourceRecords($domain);
            if (! $this->isOk($apply)) {
                return ['ok' => false, 'message' => $this->errorMessage($apply, 'DNS kayıtları uygulanamadı')];
            }

            return ['ok' => true, 'message' => 'DNS kayıtları güncellendi.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAuthCode(DomainRegistrar $account, string $domain): array
    {
        try {
            $result = $this->client($account)->getDetails($domain);
        } catch (Throwable $e) {
            return ['ok' => false, 'code' => null, 'message' => $e->getMessage()];
        }

        if (! $this->isOk($result)) {
            return ['ok' => false, 'code' => null, 'message' => $this->errorMessage($result, 'Auth kodu alınamadı')];
        }

        $code = $result['data']['AuthCode'] ?? $result['data']['authCode'] ?? null;

        return [
            'ok' => is_string($code) && $code !== '',
            'code' => is_string($code) ? $code : null,
            'message' => is_string($code) && $code !== '' ? 'Auth kodu alındı.' : 'Auth kodu bulunamadı.',
        ];
    }

    public function registerDomain(DomainRegistrar $account, string $domain, int $years, bool $autoRenew, bool $privacyHigh, ?array $registrant = null): array
    {
        $years = max(1, min(10, $years));
        $contact = $this->mapContact($registrant);
        if ($contact === null) {
            return [
                'ok' => false,
                'message' => 'DomainNameAPI kaydı için ad, e-posta, telefon, adres, şehir ve ülke gerekli. Hesap profilini tamamlayın.',
            ];
        }

        $contacts = [
            'Administrative' => $contact,
            'Billing' => $contact,
            'Technical' => $contact,
            'Registrant' => $contact,
        ];

        $additional = $this->trAdditional($domain, $registrant);

        try {
            $client = $this->client($account);
            $result = $client->registerWithContactInfo(
                $domain,
                $years,
                $contacts,
                [],
                true,
                $privacyHigh,
                $additional
            );
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        if (! $this->isOk($result)) {
            return ['ok' => false, 'message' => $this->errorMessage($result, 'Domain kaydı başarısız')];
        }

        $info = $this->getDomainInfo($account, $domain);

        return [
            'ok' => true,
            'message' => 'Domain başarıyla kaydedildi.',
            'expires_at' => $info['expires_at'] ?? ($result['data']['Dates']['Expiration'] ?? null),
            'status' => $info['status'] ?? (string) ($result['data']['Status'] ?? 'registered'),
        ];
    }

    public function transferDomain(DomainRegistrar $account, string $domain, string $authCode, bool $autoRenew, bool $privacyHigh, ?array $registrant = null): array
    {
        $authCode = trim($authCode);
        if ($authCode === '') {
            return ['ok' => false, 'message' => 'Transfer (EPP/Auth) kodu gerekli.'];
        }

        try {
            $result = $this->client($account)->transfer($domain, $authCode, 1);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        if (! $this->isOk($result)) {
            return ['ok' => false, 'message' => $this->errorMessage($result, 'Domain transferi başlatılamadı')];
        }

        return [
            'ok' => true,
            'message' => 'Transfer talebi başlatıldı. Onay süreci genelde birkaç gün sürer.',
            'status' => 'transferring',
        ];
    }

    public function getTransferStatus(DomainRegistrar $account, string $domain): array
    {
        try {
            $info = $this->getDomainInfo($account, $domain);
            if ($info !== null) {
                return [
                    'ok' => true,
                    'status' => $info['status'] ?? 'active',
                    'message' => 'Domain hesapta görünüyor.',
                ];
            }

            return [
                'ok' => true,
                'status' => 'pending',
                'message' => 'Transfer henüz tamamlanmamış olabilir.',
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'status' => null, 'message' => $e->getMessage()];
        }
    }

    public function setTransferLock(DomainRegistrar $account, string $domain, bool $locked): array
    {
        try {
            $client = $this->client($account);
            $result = $locked
                ? $client->enableTheftProtectionLock($domain)
                : $client->disableTheftProtectionLock($domain);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return [
            'ok' => $this->isOk($result),
            'message' => $this->isOk($result)
                ? ($locked ? 'Transfer kilidi etkinleştirildi.' : 'Transfer kilidi kaldırıldı.')
                : $this->errorMessage($result, 'Transfer kilidi güncellenemedi'),
        ];
    }

    private function client(DomainRegistrar $account): DomainNameAPI_PHPLibrary
    {
        $creds = $account->credentials ?? [];
        $username = trim((string) ($creds['username'] ?? ''));
        $password = trim((string) ($creds['password'] ?? ''));
        $testMode = filter_var($creds['test_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return new DomainNameAPI_PHPLibrary($username, $password, $testMode);
    }

    /** @param  array<string, mixed>|list<mixed>|null  $result */
    private function isOk(mixed $result): bool
    {
        if (! is_array($result)) {
            return false;
        }
        // checkAvailability bazen doğrudan liste döner.
        if (array_is_list($result) && $result !== []) {
            return true;
        }
        $code = strtoupper((string) ($result['result'] ?? $result['Result'] ?? ''));

        return $code === 'OK' || $code === 'SUCCESS' || $code === '1';
    }

    /** @param  array<string, mixed>|list<mixed>|null  $result */
    private function errorMessage(mixed $result, string $fallback): string
    {
        if (! is_array($result)) {
            return $fallback;
        }
        $error = $result['error'] ?? $result['Error'] ?? null;
        if (is_array($error)) {
            $msg = (string) ($error['Message'] ?? $error['message'] ?? $error['Details'] ?? '');
            if ($msg !== '') {
                return $msg;
            }
        }
        foreach (['message', 'Message', 'errorMessage', 'ErrorMessage'] as $key) {
            if (! empty($result[$key]) && is_scalar($result[$key])) {
                return (string) $result[$key];
            }
        }

        return $fallback;
    }

    /**
     * @return array{0: string, 1: string} [sld, tld without leading dot]
     */
    private function splitDomain(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = explode('/', $domain)[0] ?? $domain;
        $domain = rtrim($domain, '.');
        $parts = explode('.', $domain);
        if (count($parts) < 2) {
            return ['', ''];
        }

        // Çok parçalı TLD'ler (.com.tr, .co.uk …)
        $multi = ['com.tr', 'net.tr', 'org.tr', 'gen.tr', 'biz.tr', 'info.tr', 'name.tr', 'web.tr', 'tv.tr', 'av.tr', 'dr.tr', 'co.uk', 'org.uk', 'me.uk', 'com.au', 'net.au', 'org.au', 'co.nz', 'com.br'];
        $lastTwo = $parts[count($parts) - 2].'.'.$parts[count($parts) - 1];
        if (in_array($lastTwo, $multi, true) && count($parts) >= 3) {
            $tld = $lastTwo;
            $sld = implode('.', array_slice($parts, 0, -2));

            return [$sld, $tld];
        }

        $tld = array_pop($parts);

        return [implode('.', $parts), (string) $tld];
    }

    /** @param  array<string, mixed>|list<mixed>  $result */
    private function matchAvailabilityRow(array $result, string $sld, string $tld): ?array
    {
        $rows = array_is_list($result) ? $result : ($result['data'] ?? $result['Data'] ?? []);
        if (! is_array($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rowSld = strtolower((string) ($row['DomainName'] ?? $row['domainName'] ?? $row['sld'] ?? ''));
            $rowTld = strtolower(ltrim((string) ($row['TLD'] ?? $row['tld'] ?? ''), '.'));
            if ($rowSld === strtolower($sld) && $rowTld === strtolower($tld)) {
                return $row;
            }
        }

        // Tek sonuç varsa onu kullan.
        $first = $rows[0] ?? null;

        return is_array($first) ? $first : null;
    }

    private function periodPrice(mixed $value): float
    {
        if (is_array($value)) {
            if (isset($value[1])) {
                return $this->money($value[1]);
            }
            foreach ($value as $amount) {
                $m = $this->money($amount);
                if ($m > 0) {
                    return $m;
                }
            }

            return 0.0;
        }

        return $this->money($value);
    }

    private function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) $value, 2);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{domain: string, expires_at: ?string, registered_at: ?string, auto_renew: bool, privacy: ?string, locked: bool, status: ?string, ns_provider: ?string, nameservers: list<string>}
     */
    private function normalizeDomain(array $row): array
    {
        $domain = (string) ($row['DomainName'] ?? $row['domainName'] ?? $row['domain'] ?? '');
        $dates = is_array($row['Dates'] ?? null) ? $row['Dates'] : [];
        $ns = $row['NameServers'] ?? $row['nameservers'] ?? [];
        if (! is_array($ns)) {
            $ns = [];
        }
        $ns = array_values(array_filter(array_map(fn ($h) => strtolower(trim((string) $h)), $ns)));

        $privacy = filter_var($row['PrivacyProtectionStatus'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $locked = filter_var($row['LockStatus'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'domain' => strtolower($domain),
            'expires_at' => isset($dates['Expiration']) ? (string) $dates['Expiration'] : (isset($row['ExpirationDate']) ? (string) $row['ExpirationDate'] : null),
            'registered_at' => isset($dates['Start']) ? (string) $dates['Start'] : null,
            'auto_renew' => false,
            'privacy' => $privacy ? 'high' : 'public',
            'locked' => $locked,
            'status' => isset($row['Status']) ? (string) $row['Status'] : null,
            'ns_provider' => $ns === [] ? null : 'custom',
            'nameservers' => $ns,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $registrant
     * @return array<string, mixed>|null
     */
    private function mapContact(?array $registrant): ?array
    {
        if ($registrant === null) {
            return null;
        }

        $name = trim((string) ($registrant['name'] ?? ''));
        $email = trim((string) ($registrant['email'] ?? ''));
        $phone = preg_replace('/\D+/', '', (string) ($registrant['phone'] ?? '')) ?: '';
        $address = trim((string) ($registrant['address'] ?? $registrant['address1'] ?? ''));
        $city = trim((string) ($registrant['city'] ?? ''));
        $country = strtoupper(trim((string) ($registrant['country'] ?? 'TR')));
        $zip = trim((string) ($registrant['postal_code'] ?? $registrant['zip'] ?? ''));
        $company = trim((string) ($registrant['company'] ?? ''));
        $state = trim((string) ($registrant['state'] ?? $registrant['province'] ?? $city));

        if ($name === '' || $email === '' || $phone === '' || $address === '' || $city === '' || $country === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $name, 2) ?: [$name];
        $first = trim((string) ($parts[0] ?? $name));
        $last = trim((string) ($parts[1] ?? $first));

        $phoneCc = (int) ($registrant['phone_country_code'] ?? ($country === 'TR' ? 90 : 1));
        if (str_starts_with($phone, (string) $phoneCc) && strlen($phone) > 10) {
            $phone = substr($phone, strlen((string) $phoneCc));
        }

        return [
            'FirstName' => $first,
            'LastName' => $last,
            'Company' => $company !== '' ? $company : $first.' '.$last,
            'EMail' => $email,
            'AddressLine1' => $address,
            'AddressLine2' => (string) ($registrant['address2'] ?? ''),
            'AddressLine3' => '',
            'City' => $city,
            'Country' => $country,
            'Fax' => '',
            'FaxCountryCode' => '',
            'Phone' => $phone,
            'PhoneCountryCode' => $phoneCc,
            'Type' => 'Contact',
            'ZipCode' => $zip !== '' ? $zip : '34000',
            'State' => $state !== '' ? $state : $city,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $registrant
     * @return array<string, mixed>
     */
    private function trAdditional(string $domain, ?array $registrant): array
    {
        [, $tld] = $this->splitDomain($domain);
        if (! str_ends_with('.'.$tld, '.tr') && $tld !== 'tr') {
            return [];
        }

        $citizenId = (string) ($registrant['national_id'] ?? $registrant['tc_kimlik'] ?? $registrant['trabis_citizen_id'] ?? '');
        $fullName = trim((string) ($registrant['name'] ?? ''));
        if ($citizenId === '' || $fullName === '') {
            return [];
        }

        return [
            'TRABISDOMAINCATEGORY' => (int) ($registrant['trabis_category'] ?? 1),
            'TRABISCITIZIENID' => $citizenId,
            'TRABISNAMESURNAME' => $fullName,
            'TRABISCOUNTRYID' => (string) ($registrant['trabis_country_id'] ?? '792'),
            'TRABISCITYID' => (string) ($registrant['trabis_city_id'] ?? '34'),
        ];
    }
}
