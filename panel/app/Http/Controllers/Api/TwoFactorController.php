<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorBackupCode;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    private function getIssuer(User $user): string
    {
        return (string) config('app.name', 'Panelze');
    }

    private function makeBackupCode(): string
    {
        // Kullanıcıya gösterilecek format: 5 haneli-5 haneli (örn: 48291-73905)
        $raw = (string) random_int(0, 9999999999);
        $raw = str_pad($raw, 10, '0', STR_PAD_LEFT);

        return substr($raw, 0, 5).'-'.substr($raw, 5, 5);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'two_factor_enabled' => (bool) $user->two_factor_enabled,
        ]);
    }

    public function setup(Request $request, TotpService $totp): JsonResponse
    {
        $user = $request->user();

        if ((bool) $user->two_factor_enabled) {
            $payload = $request->validate([
                'password' => ['required', 'string'],
            ]);
            if (! Hash::check($payload['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'password' => [__('auth.current_password_invalid')],
                ]);
            }
        }

        $secret = $totp->generateSecret(20);

        if ((bool) $user->two_factor_enabled) {
            Cache::put($this->pendingSecretCacheKey($user->id), $secret, now()->addMinutes(20));
        } else {
            $user->two_factor_secret = $secret;
            $user->two_factor_enabled = false;
            $user->save();

            TwoFactorBackupCode::query()
                ->where('user_id', $user->id)
                ->delete();
        }

        $issuer = $this->getIssuer($user);
        $label = rawurlencode($issuer.':'.$user->email);

        // TOTP URI: otpauth://totp/<label>?secret=...&issuer=...&algorithm=SHA1&digits=6&period=30
        $otpauth = sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            $label,
            $secret,
            rawurlencode($issuer)
        );

        return response()->json([
            'two_factor_enabled' => (bool) $user->two_factor_enabled,
            'otpauth_url' => $otpauth,
            'secret' => $secret,
        ]);
    }

    public function verify(Request $request, TotpService $totp): JsonResponse
    {
        $user = $request->user();

        $payload = $request->validate([
            'otp' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $pending = Cache::get($this->pendingSecretCacheKey($user->id));
        $secretToVerify = is_string($pending) && $pending !== ''
            ? $pending
            : (string) $user->two_factor_secret;

        if ($secretToVerify === '') {
            return response()->json([
                'message' => __('auth.two_factor_secret_missing'),
                'code' => 'two_factor_secret_missing',
            ], 409);
        }

        $otp = (string) $payload['otp'];
        $ok = $totp->verifyCode($secretToVerify, $otp, 1, 30, 6);
        if (! $ok) {
            return response()->json([
                'message' => __('auth.two_factor_invalid_code'),
                'code' => 'two_factor_invalid_code',
            ], 422);
        }

        $user->two_factor_secret = $secretToVerify;
        $user->two_factor_enabled = true;
        $user->save();
        Cache::forget($this->pendingSecretCacheKey($user->id));

        TwoFactorBackupCode::query()
            ->where('user_id', $user->id)
            ->delete();

        $codes = [];
        $rows = [];
        for ($i = 0; $i < 8; $i++) {
            $code = $this->makeBackupCode();
            $codes[] = $code;
            $rows[] = [
                'user_id' => $user->id,
                'code_hash' => Hash::make($code),
                'used_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        TwoFactorBackupCode::query()->insert($rows);

        return response()->json([
            'two_factor_enabled' => true,
            'backup_codes' => $codes,
        ]);
    }

    public function regenerateBackupCodes(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! (bool) $user->two_factor_enabled) {
            return response()->json([
                'message' => __('auth.two_factor_not_enabled'),
                'code' => 'two_factor_not_enabled',
            ], 409);
        }

        $payload = $request->validate([
            'password' => ['required', 'string'],
        ]);
        if (! Hash::check($payload['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('auth.current_password_invalid')],
            ]);
        }

        TwoFactorBackupCode::query()
            ->where('user_id', $user->id)
            ->delete();

        $codes = [];
        $rows = [];
        for ($i = 0; $i < 8; $i++) {
            $code = $this->makeBackupCode();
            $codes[] = $code;
            $rows[] = [
                'user_id' => $user->id,
                'code_hash' => Hash::make($code),
                'used_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        TwoFactorBackupCode::query()->insert($rows);

        return response()->json([
            'backup_codes' => $codes,
        ]);
    }

    /**
     * 2FA’yı kapatır: secret ve yedek kodlar silinir (girişte OTP istenmez).
     */
    public function disable(Request $request): JsonResponse
    {
        $user = $request->user();

        $payload = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($payload['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('auth.current_password_invalid')],
            ]);
        }

        if ((bool) config('panelze.enforce_admin_2fa', false) && $user->isVendorOperator()) {
            return response()->json([
                'message' => __('auth.two_factor_disable_forbidden'),
                'code' => 'two_factor_disable_forbidden',
            ], 403);
        }

        $user->two_factor_secret = null;
        $user->two_factor_enabled = false;
        $user->save();

        TwoFactorBackupCode::query()
            ->where('user_id', $user->id)
            ->delete();

        Cache::forget($this->pendingSecretCacheKey($user->id));

        return response()->json([
            'two_factor_enabled' => false,
        ]);
    }

    private function pendingSecretCacheKey(int $userId): string
    {
        return 'twofa_pending_secret:'.$userId;
    }
}
