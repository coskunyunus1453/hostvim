<?php

namespace App\Services\Domain;

use App\Models\DomainName;
use App\Models\DomainRegistrar;
use App\Models\Order;
use App\Services\Domain\Registrar\DomainManagementInterface;
use App\Services\Domain\Registrar\DomainRegistrarResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Tescilli domainlerin gercek yonetimi (Spaceship vb.): senkron, DNS, NS, yenileme,
 * gizlilik, otomatik yenileme, auth kodu. Source-of-truth registrar API'sidir;
 * domain_names tablosu hizli erisim icin yerel kopyadir.
 */
class DomainManagementService
{
    public function __construct(private DomainRegistrarResolver $resolver) {}

    /** @return list<string> Yonetim destekleyen aktif registrar api_name'leri */
    public function manageableApis(): array
    {
        $out = [];
        foreach ($this->resolver->enabledAccounts() as $account) {
            try {
                if ($this->resolver->driver($account->api_name) instanceof DomainManagementInterface) {
                    $out[] = $account->api_name;
                }
            } catch (\Throwable) {
                // yoksay
            }
        }

        return $out;
    }

    /**
     * Tum yonetilebilir saglayicilardan domainleri ceker ve yerel tabloya yazar.
     *
     * @return array{imported: int, updated: int, total: int}
     */
    public function syncAll(): array
    {
        $imported = 0;
        $updated = 0;
        $total = 0;

        foreach ($this->manageableApis() as $apiName) {
            $account = $this->resolver->account($apiName);
            if ($account === null) {
                continue;
            }
            $res = $this->syncRegistrar($account);
            $imported += $res['imported'];
            $updated += $res['updated'];
            $total += $res['total'];
        }

        $linked = $this->backfillOwnership();

        return ['imported' => $imported, 'updated' => $updated, 'total' => $total, 'linked' => $linked];
    }

    /**
     * Sahibi (customer_email) bilinmeyen domainleri store siparislerindeki
     * domain_register kalemleriyle eslestirip musteriye baglar.
     */
    public function backfillOwnership(): int
    {
        $linked = 0;
        $rows = DomainName::query()->whereNull('customer_email')->get();

        foreach ($rows as $row) {
            $item = \App\Models\OrderItem::query()
                ->where('item_type', 'domain_register')
                ->whereRaw('LOWER(domain_name) = ?', [$row->domain])
                ->latest('id')
                ->first();

            if ($item === null) {
                continue;
            }
            $order = Order::query()->find($item->order_id);
            if ($order === null) {
                continue;
            }

            $row->update([
                'customer_email' => $order->customer_email,
                'order_id' => $order->id,
            ]);
            $linked++;
        }

        return $linked;
    }

    /**
     * @return array{imported: int, updated: int, total: int}
     */
    public function syncRegistrar(DomainRegistrar $account): array
    {
        $driver = $this->managementDriver($account);
        $domains = $driver->listDomains($account);
        $imported = 0;
        $updated = 0;

        foreach ($domains as $remote) {
            $domain = strtolower((string) ($remote['domain'] ?? ''));
            if ($domain === '') {
                continue;
            }

            $row = DomainName::query()->where('domain', $domain)->first();
            $attrs = $this->mapRemote($account->api_name, $remote);

            if ($row === null) {
                DomainName::create($attrs);
                $imported++;
            } else {
                $row->update($attrs);
                $updated++;
            }
        }

        return ['imported' => $imported, 'updated' => $updated, 'total' => count($domains)];
    }

    /**
     * Siparisten gelen bir domaini saglayicida register eder ve musteriye baglar.
     * Kayit basarisiz olsa bile DomainName satiri olusturulur (status=failed) ki musteri/admin gorebilsin.
     *
     * @return array{ok: bool, message: string}
     */
    public function registerForOrder(Order $order, string $domain, int $years, ?string $apiName = null): array
    {
        $domain = strtolower(trim($domain));
        $apiName = $apiName ?: $this->preferredApi();
        if ($apiName === null) {
            return ['ok' => false, 'message' => 'Otomatik alan adı kaydı şu an yapılandırılmamış. Lütfen destek ile iletişime geçin.'];
        }

        $row = DomainName::query()->updateOrCreate(
            ['domain' => $domain],
            [
                'registrar_api' => $apiName,
                'order_id' => $order->id,
                'customer_email' => $order->customer_email,
                'status' => 'registering',
            ],
        );

        $account = $this->resolver->account($apiName);
        if ($account === null) {
            $row->update(['status' => 'failed', 'meta' => ['register_error' => 'Registrar API yapılandırılmamış']]);

            return ['ok' => false, 'message' => $apiName.' API yapılandırılmamış.'];
        }

        try {
            $driver = $this->managementDriver($account);

            // Idempotency: domain saglayicida zaten kayitliysa TEKRAR register etme.
            // (Job retry / timeout sonrasi mukerrer kayit ve cifte ucretlendirmeyi onler.)
            try {
                $existingInfo = $driver->getDomainInfo($account, $domain);
            } catch (\Throwable) {
                $existingInfo = null;
            }
            if (is_array($existingInfo) && ! empty($existingInfo['domain'])) {
                $row->update($this->mapRemote($apiName, $existingInfo));
                $this->refresh($row->fresh());

                return ['ok' => true, 'message' => 'Alan adı sağlayıcıda zaten kayıtlı; bilgiler senkronlandı.'];
            }

            // Domain musteri adina kaydedilir (registrant = musteri bilgileri).
            // WHOIS gizliligi varsayilan acik, otomatik yenileme kapali.
            $result = $driver->registerDomain($account, $domain, $years, false, true, $this->registrantFromOrder($order));
        } catch (\Throwable $e) {
            Log::error('domain.register_failed', ['domain' => $domain, 'error' => $e->getMessage()]);
            $row->update(['status' => 'failed', 'meta' => ['register_error' => $e->getMessage()]]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }

        if (! ($result['ok'] ?? false)) {
            $row->update(['status' => 'failed', 'meta' => ['register_error' => $result['message']]]);

            return ['ok' => false, 'message' => $result['message']];
        }

        $row->update([
            'status' => $result['status'] ?? 'registered',
            'expires_at' => $this->parseDate($result['expires_at'] ?? null) ?? now()->addYears(max(1, $years)),
            'privacy' => 'high',
            'last_synced_at' => now(),
        ]);
        $this->refresh($row->fresh());

        return ['ok' => true, 'message' => $result['message']];
    }

    /**
     * Domain kaydinda registrant olarak kullanilacak musteri bilgilerini hazirlar.
     * Once musteri hesabi (User) profilini, eksik alanlar icin siparis bilgilerini kullanir.
     *
     * @return array<string, mixed>
     */
    private function registrantFromOrder(Order $order): array
    {
        $user = $order->user;

        return [
            'name' => $user?->name ?? $order->customer_name,
            'email' => $user?->email ?? $order->customer_email,
            'phone' => $user?->phone ?? $order->customer_phone,
            'company' => $user?->company ?? $order->customer_company,
            'address' => $user?->address ?? $order->customer_address,
            'city' => $user?->city ?? null,
            'postal_code' => $user?->postal_code ?? null,
            'country' => $user?->country ?? 'TR',
        ];
    }

    /** Tercih edilen otomatik-kayit sağlayicisi (ilk yonetilebilir API). */
    public function preferredApi(): ?string
    {
        return $this->manageableApis()[0] ?? null;
    }

    public function refresh(DomainName $domain): bool
    {
        $account = $this->accountFor($domain);
        $driver = $this->managementDriver($account);
        $remote = $driver->getDomainInfo($account, $domain->domain);
        if ($remote === null) {
            return false;
        }
        $domain->update($this->mapRemote($account->api_name, $remote));

        return true;
    }

    /** @return array{ok: bool, message: string} */
    public function setNameservers(DomainName $domain, string $provider, array $hosts = []): array
    {
        $account = $this->accountFor($domain);
        $result = $this->managementDriver($account)->setNameservers($account, $domain->domain, $provider, $hosts);
        if ($result['ok']) {
            $domain->update([
                'ns_provider' => $provider,
                'nameservers' => $provider === 'custom' ? array_values(array_filter($hosts)) : [],
            ]);
        }

        return $result;
    }

    /** @return array{ok: bool, message: string} */
    public function setPrivacy(DomainName $domain, bool $enabled): array
    {
        $account = $this->accountFor($domain);
        $level = $enabled ? 'high' : 'public';
        $result = $this->managementDriver($account)->setPrivacy($account, $domain->domain, $level);
        if ($result['ok']) {
            $domain->update(['privacy' => $level]);
        }

        return $result;
    }

    /** @return array{ok: bool, message: string} */
    public function setAutoRenew(DomainName $domain, bool $enabled): array
    {
        $account = $this->accountFor($domain);
        $result = $this->managementDriver($account)->setAutoRenew($account, $domain->domain, $enabled);
        if ($result['ok']) {
            $domain->update(['auto_renew' => $enabled]);
        }

        return $result;
    }

    /** @return array{ok: bool, message: string} */
    public function renew(DomainName $domain, int $years): array
    {
        $account = $this->accountFor($domain);
        $result = $this->managementDriver($account)->renewDomain($account, $domain->domain, $years);
        if ($result['ok'] && ! empty($result['expires_at'])) {
            $domain->update(['expires_at' => $this->parseDate($result['expires_at'])]);
        }

        return ['ok' => $result['ok'], 'message' => $result['message']];
    }

    /** @return list<array{type: string, name: string, value: string, ttl: int, priority: ?int}> */
    public function dnsRecords(DomainName $domain): array
    {
        $account = $this->accountFor($domain);

        return $this->managementDriver($account)->getDnsRecords($account, $domain->domain);
    }

    /** @return array{ok: bool, message: string} */
    public function saveDnsRecords(DomainName $domain, array $records): array
    {
        $account = $this->accountFor($domain);

        return $this->managementDriver($account)->syncDnsRecords($account, $domain->domain, $records);
    }

    /** @return array{ok: bool, code: ?string, message: string} */
    public function authCode(DomainName $domain): array
    {
        $account = $this->accountFor($domain);

        return $this->managementDriver($account)->getAuthCode($account, $domain->domain);
    }

    /**
     * @param  array<string, mixed>  $remote
     * @return array<string, mixed>
     */
    private function mapRemote(string $apiName, array $remote): array
    {
        return [
            'registrar_api' => $apiName,
            'domain' => strtolower((string) ($remote['domain'] ?? '')),
            'status' => $remote['status'] ?? null,
            'registered_at' => $this->parseDate($remote['registered_at'] ?? null),
            'expires_at' => $this->parseDate($remote['expires_at'] ?? null),
            'auto_renew' => (bool) ($remote['auto_renew'] ?? false),
            'privacy' => $remote['privacy'] ?? null,
            'locked' => (bool) ($remote['locked'] ?? false),
            'ns_provider' => $remote['ns_provider'] ?? null,
            'nameservers' => $remote['nameservers'] ?? [],
            'last_synced_at' => now(),
        ];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function accountFor(DomainName $domain): DomainRegistrar
    {
        $account = $this->resolver->account($domain->registrar_api);
        if ($account === null) {
            throw new RuntimeException('Registrar API yapılandırılmamış: '.$domain->registrar_api);
        }

        return $account;
    }

    private function managementDriver(DomainRegistrar $account): DomainManagementInterface
    {
        $driver = $this->resolver->driver($account->api_name);
        if (! $driver instanceof DomainManagementInterface) {
            throw new RuntimeException($account->api_name.' sağlayıcısı gerçek domain yönetimini desteklemiyor.');
        }

        return $driver;
    }
}
