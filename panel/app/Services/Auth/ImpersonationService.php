<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\SafeAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class ImpersonationService
{
    public function canImpersonate(User $admin, User $target): void
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages(['user' => 'Yalnızca yöneticiler müşteri oturumu açabilir.']);
        }

        if ((int) $admin->id === (int) $target->id) {
            throw ValidationException::withMessages(['user' => 'Kendi hesabınızı taklit edemezsiniz.']);
        }

        if ($target->isAdmin() || $target->isVendorOperator()) {
            throw ValidationException::withMessages(['user' => 'Yönetici veya operatör hesapları taklit edilemez.']);
        }

        if ($target->status !== 'active') {
            throw ValidationException::withMessages(['user' => 'Yalnızca aktif müşteri hesapları açılabilir.']);
        }
    }

    /**
     * @return array{token: string, expires_at: string, user: User, impersonated_by: array{id: int, name: string, email: string}}
     */
    public function start(User $admin, User $target, ?Request $request = null): array
    {
        $this->canImpersonate($admin, $target);

        $expiresAt = now()->addMinutes(30);
        $tokenName = 'panel-impersonate-'.$admin->id;
        $abilities = $target->sanctumAbilities();
        $accessToken = $target->createToken($tokenName, $abilities, $expiresAt);

        Cache::put(
            $this->cacheKey($accessToken->accessToken->id),
            ['admin_id' => $admin->id, 'target_id' => $target->id],
            $expiresAt,
        );

        SafeAuditLogger::info('panelze.admin.impersonate_start', [
            'admin_id' => $admin->id,
            'target_user_id' => $target->id,
            'target_email_hash' => hash('sha256', strtolower(trim((string) $target->email))),
        ], $request);

        $userPayload = $target->load(['roles', 'hostingPackage'])->toArray();
        $userPayload['abilities'] = $abilities;

        return [
            'token' => $accessToken->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => $userPayload,
            'impersonated_by' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ],
        ];
    }

    public function isImpersonationToken(?PersonalAccessToken $token): bool
    {
        if ($token === null) {
            return false;
        }

        return str_starts_with((string) $token->name, 'panel-impersonate-');
    }

    /**
     * @return array{id: int, name: string, email: string}|null
     */
    public function impersonatorMeta(?PersonalAccessToken $token): ?array
    {
        if (! $this->isImpersonationToken($token)) {
            return null;
        }

        $adminId = (int) str_replace('panel-impersonate-', '', (string) $token->name);
        $admin = User::query()->find($adminId);
        if ($admin === null) {
            return null;
        }

        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
        ];
    }

    public function end(User $user, ?Request $request = null): void
    {
        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken && $this->isImpersonationToken($token)) {
            Cache::forget($this->cacheKey($token->id));
            SafeAuditLogger::info('panelze.admin.impersonate_end', [
                'target_user_id' => $user->id,
                'token_id' => $token->id,
            ], $request);
            $token->delete();
        }
    }

    private function cacheKey(int $tokenId): string
    {
        return 'panelze:impersonate:'.$tokenId;
    }
}
