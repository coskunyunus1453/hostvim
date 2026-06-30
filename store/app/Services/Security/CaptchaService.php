<?php

namespace App\Services\Security;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Bot/spam korumasi icin captcha dogrulama servisi.
 *
 * Desteklenen saglayicilar:
 *  - native     : Harici anahtar gerektirmeyen, sunucu-tarafli basit matematik dogrulamasi (varsayilan)
 *  - turnstile  : Cloudflare Turnstile (ucretsiz, modern) — site/secret anahtari gerektirir
 *  - recaptcha  : Google reCAPTCHA v2 — site/secret anahtari gerektirir
 *
 * Her formda gizli bir honeypot alani da bulunur; doldurulmussa istek bot kabul edilir.
 */
class CaptchaService
{
    public const HONEYPOT_FIELD = 'hv_website_url';

    public const NATIVE_SESSION_KEY = 'captcha_native_answer';

    /** Captcha uygulanabilecek baglamlar (varsayilan: hepsi acik). */
    public const CONTEXTS = ['login', 'register', 'checkout', 'password', 'contact'];

    public function __construct(private readonly SettingsService $settings) {}

    public function provider(): string
    {
        $p = strtolower(trim((string) $this->settings->get('captcha_provider', 'native')));

        return in_array($p, ['native', 'turnstile', 'recaptcha'], true) ? $p : 'native';
    }

    /** Captcha genel olarak aktif mi (anahtar gereksinimleri dahil)? */
    public function enabled(): bool
    {
        if (! $this->boolSetting('captcha_enabled', true)) {
            return false;
        }

        if ($this->provider() === 'native') {
            return true;
        }

        return $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    /** Belirli bir baglam (login/checkout vb.) icin captcha uygulanmali mi? */
    public function enabledFor(string $context): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        // Giris yapmis kullanicilar zaten dogrulanmistir; tekrar captcha istemeyiz.
        if (auth()->check()) {
            return false;
        }

        $default = in_array($context, self::CONTEXTS, true);

        return $this->boolSetting('captcha_ctx_'.$context, $default);
    }

    public function siteKey(): string
    {
        return trim((string) $this->settings->get('captcha_site_key', ''));
    }

    public function secretKey(): string
    {
        return trim((string) $this->settings->get('captcha_secret_key', ''));
    }

    /**
     * Native saglayici icin yeni bir matematik sorusu uretir ve cevabi oturuma yazar.
     *
     * @return array{a:int,b:int}
     */
    public function newNativeChallenge(): array
    {
        $a = random_int(2, 9);
        $b = random_int(2, 9);
        session([self::NATIVE_SESSION_KEY => (string) ($a + $b)]);

        return ['a' => $a, 'b' => $b];
    }

    /** Gelen istegi dogrular. */
    public function verify(Request $request): bool
    {
        // Honeypot: insan gormez, bot doldurur.
        if (trim((string) $request->input(self::HONEYPOT_FIELD, '')) !== '') {
            return false;
        }

        return match ($this->provider()) {
            'turnstile' => $this->verifyTurnstile($request),
            'recaptcha' => $this->verifyRecaptcha($request),
            default => $this->verifyNative($request),
        };
    }

    private function verifyNative(Request $request): bool
    {
        $expected = (string) session(self::NATIVE_SESSION_KEY, '');
        $given = trim((string) $request->input('captcha_answer', ''));
        session()->forget(self::NATIVE_SESSION_KEY);

        return $expected !== '' && $given !== '' && hash_equals($expected, $given);
    }

    private function verifyTurnstile(Request $request): bool
    {
        $token = (string) $request->input('cf-turnstile-response', '');
        if ($token === '') {
            return false;
        }

        try {
            $resp = Http::asForm()->timeout(8)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                ['secret' => $this->secretKey(), 'response' => $token, 'remoteip' => $request->ip()],
            );

            return (bool) ($resp->json('success') ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    private function verifyRecaptcha(Request $request): bool
    {
        $token = (string) $request->input('g-recaptcha-response', '');
        if ($token === '') {
            return false;
        }

        try {
            $resp = Http::asForm()->timeout(8)->post(
                'https://www.google.com/recaptcha/api/siteverify',
                ['secret' => $this->secretKey(), 'response' => $token, 'remoteip' => $request->ip()],
            );

            return (bool) ($resp->json('success') ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    private function boolSetting(string $key, bool $default): bool
    {
        $val = $this->settings->get($key, $default ? '1' : '0');

        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }
}
