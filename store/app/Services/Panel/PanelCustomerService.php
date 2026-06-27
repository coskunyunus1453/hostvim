<?php

namespace App\Services\Panel;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PanelCustomerService
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(private PanelzeApiService $api) {}

    public static function forgetUserCache(int $userId): void
    {
        foreach (['summary', 'domains', 'hosting', 'invoices'] as $segment) {
            Cache::forget("panel:{$segment}:{$userId}");
        }
    }

    public function syncPanelUserId(User $user): void
    {
        if ($user->panel_user_id || ! $this->api->isConfigured()) {
            return;
        }

        try {
            $result = $this->api->customerLinkByEmail($user->email);
            if (! empty($result['linked']) && ! empty($result['panel_user_id'])) {
                $user->forceFill(['panel_user_id' => (int) $result['panel_user_id']])->save();
                self::forgetUserCache($user->id);
            }
        } catch (RuntimeException $e) {
            Log::debug('Panel müşteri eşleme atlandı', ['email' => $user->email, 'message' => $e->getMessage()]);
        }
    }

    public function assignPanelUserId(User $user, int $panelUserId): void
    {
        if ($panelUserId < 1) {
            return;
        }
        if ($user->panel_user_id === null) {
            $user->forceFill(['panel_user_id' => $panelUserId])->save();
        }
    }

    /** @return array<string, mixed> */
    public function summary(User $user): array
    {
        return $this->cachedGet($user, 'summary', '/api/integrations/store/customer/summary');
    }

    /** @return array<string, mixed> */
    public function domains(User $user): array
    {
        return $this->cachedGet($user, 'domains', '/api/integrations/store/customer/domains');
    }

    /** @return array<string, mixed> */
    public function hosting(User $user): array
    {
        return $this->cachedGet($user, 'hosting', '/api/integrations/store/customer/hosting');
    }

    /** @return array<string, mixed> */
    public function invoices(User $user, int $page = 1): array
    {
        return $this->cachedGet($user, 'invoices', '/api/integrations/store/customer/invoices', ['page' => $page]);
    }

    /** @return array<string, mixed> */
    public function invoice(User $user, int $invoiceId): array
    {
        return $this->forUser($user, 'get', '/api/integrations/store/customer/invoices/'.$invoiceId);
    }

    /** @return array<string, mixed> */
    public function payInvoice(User $user, int $invoiceId): array
    {
        return $this->forUser($user, 'post', '/api/integrations/store/customer/invoices/'.$invoiceId.'/pay');
    }

    /** @return array<string, mixed> */
    public function updatePanelProfile(User $user, array $data): array
    {
        return $this->forUser($user, 'patch', '/api/integrations/store/customer/profile', $data);
    }

    public function updatePanelPassword(User $user, string $current, string $password, string $confirmation): void
    {
        $this->forUser($user, 'post', '/api/integrations/store/customer/password', [
            'current_password' => $current,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ]);
    }

    /** @return array<string, mixed> */
    public function requestTransfer(User $user, array $payload): array
    {
        return $this->forUser($user, 'post', '/api/integrations/store/customer/domains/transfers', $payload);
    }

    /** @return array<string, mixed> */
    public function updateRegistration(User $user, int $registrationId, array $payload): array
    {
        return $this->forUser($user, 'patch', '/api/integrations/store/customer/domains/registrations/'.$registrationId, $payload);
    }

    /**
     * Hesaplar arası domain/hosting sahipliği devri (panel tarafında uygular).
     *
     * @param  array{type: string, domain: string, target_email: string}  $payload
     * @return array<string, mixed>
     */
    public function transferOwnership(User $user, array $payload): array
    {
        $result = $this->forUser($user, 'post', '/api/integrations/store/customer/ownership/transfer', $payload);
        self::forgetUserCache($user->id);

        return $result;
    }

    /** @return array{redirect_url: string, expires_in: int} */
    public function panelSso(User $user): array
    {
        /** @var array{redirect_url: string, expires_in: int} */
        return $this->forUser($user, 'post', '/api/integrations/store/customer/panel-sso');
    }

    /** @param  array<string, mixed>  $payload */
    private function cachedGet(User $user, string $segment, string $path, array $payload = []): array
    {
        $cacheKey = 'panel:'.$segment.':'.$user->id;
        if ($payload !== []) {
            $cacheKey .= ':'.hash('xxh128', json_encode($payload));
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn () => $this->forUser($user, 'get', $path, $payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function forUser(User $user, string $method, string $path, array $payload = []): array
    {
        if (! $user->panel_user_id) {
            throw new RuntimeException('Henüz aktif bir hosting hesabınız yok. Sipariş sonrası otomatik eşleşir.');
        }
        if (! $this->api->isConfigured()) {
            throw new RuntimeException('Panel bağlantısı yapılandırılmamış.');
        }

        $payload['panel_user_id'] = (int) $user->panel_user_id;

        return match (strtolower($method)) {
            'get' => $this->api->customerRequest('get', $path, $payload),
            'post' => $this->api->customerRequest('post', $path, $payload),
            'patch' => $this->api->customerRequest('patch', $path, $payload),
            default => throw new RuntimeException('Desteklenmeyen metod.'),
        };
    }
}
