<?php

namespace App\Services;

/**
 * Çevrimdışı (offline) imzalı lisans anahtarları — Ed25519 (hub/satıcı tarafı).
 *
 * Anahtar biçimi:  PLZ1.<base64url(payload_json)>.<base64url(signature)>
 *
 * Bu sınıf panel'deki App\Services\OfflineLicenseService ile birebir uyumludur;
 * panel/engine GÖMÜLÜ public key ile doğrular, hub burada private key ile imzalar.
 * Private key yalnızca hub sunucusunda (.env: PANELZE_LICENSE_SIGNING_SECRET) kalır.
 *
 * payload (claims) alanları:
 *   v     int     biçim sürümü (1)
 *   lid   string  lisans referansı
 *   to    string  lisans sahibi / firma adı
 *   plan  string  plan kodu
 *   feat  array   modül anahtarları (liste) ya da {anahtar: bool} haritası
 *   dom   array   bağlı host(lar); boş ya da ["*"] = her host
 *   iat   int     üretim zamanı (unix)
 *   exp   int     bitiş zamanı (unix); 0 = süresiz
 *   grace int     bitiş sonrası ek gün
 */
class OfflineLicenseService
{
    public const PREFIX = 'PLZ1';

    /**
     * @return array{public:string, secret:string}
     */
    public function generateKeypair(): array
    {
        $kp = sodium_crypto_sign_keypair();

        return [
            'public' => base64_encode(sodium_crypto_sign_publickey($kp)),
            'secret' => base64_encode(sodium_crypto_sign_secretkey($kp)),
        ];
    }

    /**
     * Verilen claim'leri private key ile imzalayıp lisans anahtarı üretir.
     *
     * @param  array<string, mixed>  $claims
     */
    public function issue(array $claims, ?string $secretKeyB64 = null): string
    {
        $secretKeyB64 = $secretKeyB64 ?? $this->signingSecret();
        $secret = base64_decode(trim((string) $secretKeyB64), true);
        if ($secret === false || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new \InvalidArgumentException('Geçersiz private key (base64 Ed25519 secret key bekleniyor).');
        }

        $claims['v'] = $claims['v'] ?? 1;
        $claims['iat'] = $claims['iat'] ?? time();

        $payloadJson = json_encode($claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $payloadSeg = $this->b64urlEncode($payloadJson);
        $sig = sodium_crypto_sign_detached($payloadSeg, $secret);

        return self::PREFIX.'.'.$payloadSeg.'.'.$this->b64urlEncode($sig);
    }

    /**
     * Anahtarı doğrular (teşhis/test için; asıl doğrulama panelde).
     *
     * @return array<string, mixed>
     */
    public function verify(string $key, ?string $host = null, ?string $publicKeyB64 = null): array
    {
        $key = trim($key);
        $public = base64_decode(trim($publicKeyB64 ?? $this->publicKey()), true);
        if ($public === false || strlen($public) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return $this->fail('no_public_key', 'Public key yapılandırılmamış.');
        }

        $parts = explode('.', $key);
        if (count($parts) !== 3 || $parts[0] !== self::PREFIX) {
            return $this->fail('malformed', 'Lisans anahtarı biçimi geçersiz.');
        }

        [, $payloadSeg, $sigSeg] = $parts;
        $sig = $this->b64urlDecode($sigSeg);
        $payloadJson = $this->b64urlDecode($payloadSeg);
        if ($sig === false || $payloadJson === false || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return $this->fail('malformed', 'Lisans anahtarı çözümlenemedi.');
        }

        if (! sodium_crypto_sign_verify_detached($sig, $payloadSeg, $public)) {
            return $this->fail('signature_invalid', 'Lisans imzası geçersiz.');
        }

        $claims = json_decode($payloadJson, true);
        if (! is_array($claims)) {
            return $this->fail('malformed', 'Lisans içeriği okunamadı.');
        }

        $exp = (int) ($claims['exp'] ?? 0);
        $graceDays = (int) ($claims['grace'] ?? (int) config('panelze_saas.offline_grace_days', 14));
        $now = time();
        $status = 'active';
        if ($exp > 0) {
            if ($now > $exp + $graceDays * 86400) {
                return $this->fail('expired', 'Lisans süresi doldu.');
            }
            if ($now > $exp) {
                $status = 'grace';
            }
        }

        $domains = $claims['dom'] ?? [];
        if (! is_array($domains)) {
            $domains = [];
        }
        if ($host !== null && ! $this->hostMatches($host, $domains)) {
            return $this->fail('domain_mismatch', 'Lisans bu host\'a bağlı değil.');
        }

        return [
            'valid' => true,
            'plan' => (string) ($claims['plan'] ?? 'standard'),
            'status' => $status,
            'expires_at' => $exp > 0 ? gmdate('c', $exp) : null,
            'license_id' => (string) ($claims['lid'] ?? ''),
            'bound_domains' => array_values($domains),
            'source' => 'offline',
        ];
    }

    public function publicKey(): string
    {
        return trim((string) config('panelze_saas.offline_public_key', ''));
    }

    public function signingSecret(): string
    {
        return trim((string) config('panelze_saas.offline_signing_secret', ''));
    }

    public function canSign(): bool
    {
        $secret = base64_decode($this->signingSecret(), true);

        return $secret !== false && strlen($secret) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES;
    }

    /**
     * @param  array<int, string>  $domains
     */
    private function hostMatches(?string $host, array $domains): bool
    {
        if ($host === null) {
            return true;
        }
        if ($domains === [] || in_array('*', $domains, true)) {
            return true;
        }
        $host = strtolower(trim($host));
        foreach ($domains as $d) {
            $d = strtolower(trim((string) $d));
            if ($d === '') {
                continue;
            }
            if ($d === $host) {
                return true;
            }
            if (str_starts_with($d, '*.')) {
                $suffix = substr($d, 1);
                if ($host === substr($d, 2) || str_ends_with($host, $suffix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function fail(string $code, string $message): array
    {
        return [
            'valid' => false,
            'code' => $code,
            'message' => $message,
            'source' => 'offline',
        ];
    }

    private function b64urlEncode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    /**
     * @return string|false
     */
    private function b64urlDecode(string $s)
    {
        $s = strtr($s, '-_', '+/');
        $pad = strlen($s) % 4;
        if ($pad > 0) {
            $s .= str_repeat('=', 4 - $pad);
        }

        return base64_decode($s, true);
    }
}
