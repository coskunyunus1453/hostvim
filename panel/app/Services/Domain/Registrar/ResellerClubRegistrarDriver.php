<?php

namespace App\Services\Domain\Registrar;

use App\Models\DomainRegistration;
use App\Models\User;
use App\Services\Billing\BillingSettings;
use App\Services\PanelDnsSettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResellerClubRegistrarDriver implements RegistrarDriverInterface
{
    public function __construct(
        private ResellerClubClient $client,
        private BillingSettings $settings,
        private PanelDnsSettingsService $dnsSettings,
    ) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    public function register(string $domain, int $years, User $user): array
    {
        $domain = strtolower(trim($domain));
        $years = max(1, min(10, $years));

        try {
            if (! $this->client->isDomainAvailable($domain)) {
                return [
                    'status' => DomainRegistration::STATUS_FAILED,
                    'registrar' => 'resellerclub',
                    'notes' => 'Alan adı ResellerClub üzerinde müsait değil.',
                ];
            }

            $customerId = $this->client->ensureCustomer($user);
            $contactId = $this->client->ensureContact($customerId, $user);
            $result = $this->client->registerDomain($domain, $years, $customerId, $contactId, $this->nameservers());

            $ok = in_array(strtolower($result['actionstatus']), ['success', 'pending'], true);

            return [
                'status' => $ok ? DomainRegistration::STATUS_ACTIVE : DomainRegistration::STATUS_PENDING,
                'registrar' => 'resellerclub',
                'ref' => $result['entityid'],
                'expires_at' => Carbon::now()->addYears($years)->toIso8601String(),
                'notes' => $ok ? null : 'Kayıt işlemi beklemede: '.$result['actionstatus'],
            ];
        } catch (Throwable $e) {
            Log::warning('ResellerClub register failed', [
                'domain' => $domain,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => DomainRegistration::STATUS_PENDING,
                'registrar' => 'resellerclub',
                'notes' => 'API hatası (manuel tamamlanacak): '.$e->getMessage(),
            ];
        }
    }

    /** @return list<string> */
    private function nameservers(): array
    {
        $ns1 = trim((string) $this->settings->get('resellerclub_ns1', ''));
        $ns2 = trim((string) $this->settings->get('resellerclub_ns2', ''));
        if ($ns1 !== '' && $ns2 !== '') {
            return [$ns1, $ns2];
        }

        [$dnsNs1, $dnsNs2] = $this->dnsSettings->nameServers();

        return array_values(array_filter([$dnsNs1, $dnsNs2]));
    }
}
