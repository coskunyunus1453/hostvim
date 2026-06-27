<?php

namespace App\Services;

/**
 * Çevrimdışı (offline) imzalı lisans anahtarları — Ed25519.
 *
 * Anahtar biçimi:  PLZ1.<base64url(payload_json)>.<base64url(signature)>
 *
 * - İmza, ortadaki base64url(payload) parçasının ham baytları üzerinden atılır
 *   (yeniden kodlama/canonicalization sorunlarını önlemek için).
 * - Doğrulama satıcının (vendor) GÖMÜLÜ public key'i ile yapılır; private key
 *   asla ürünle dağıtılmaz. Böylece panel/engine internet olmadan da lisansı
 *   doğrular; online hub yalnızca uzaktan İPTAL için opsiyoneldir.
 *
 * payload (claims) alanları:
 *   v     int     biçim sürümü (1)
 *   lid   string  lisans referansı (ör. HV-2026-0001)
 *   to    string  lisans sahibi / firma adı
 *   plan  string  plan kodu (enterprise, pro, standard, community...)
 *   feat  array   modül anahtarları (liste) ya da {anahtar: bool} haritası
 *   dom   array   bağlı host(lar); boş ya da ["*"] = her host. "*.x.com" desteklenir
 *   iat   int     üretim zamanı (unix)
 *   exp   int     bitiş zamanı (unix); 0 = süresiz
 *   grace int     bitiş sonrası ek gün (varsayılan config)
 */
class OfflineLicenseService
{
    public const PREFIX = 'PLZ1';

    /**
     * Yeni bir Ed25519 anahtar çifti üretir (satıcı kurulumu için).
     *
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
     * Verilen claim'leri private key ile imzalayıp lisans anahtarı üretir (satıcı).
     *
     * @param  array<string, mixed>  $claims
     */
    public function issue(array $claims, string $secretKeyB64): string
    {
        $secret = base64_decode(trim($secretKeyB64), true);
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
     * Anahtarı doğrular. Her zaman normalize edilmiş bir dizi döner; geçersizse
     * valid=false ve uygun bir 'code' içerir.
     *
     * @return array<string, mixed>
     */
    public function verify(string $key, ?string $host = null, ?string $publicKeyB64 = null): array
    {
        $key = trim($key);
        $public = base64_decode(trim($publicKeyB64 ?? $this->publicKey()), true);
        if ($public === false || strlen($public) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return $this->fail('no_public_key', 'Sunucuda gömülü lisans public key yapılandırılmamış.');
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
            return $this->fail('signature_invalid', 'Lisans imzası geçersiz (anahtar değiştirilmiş olabilir).');
        }

        $claims = json_decode($payloadJson, true);
        if (! is_array($claims)) {
            return $this->fail('malformed', 'Lisans içeriği okunamadı.');
        }

        // Süre / grace kontrolü
        $exp = (int) ($claims['exp'] ?? 0);
        $graceDays = (int) ($claims['grace'] ?? (int) config('panelze.license.offline_grace_days', 14));
        $now = time();
        $status = 'active';
        if ($exp > 0) {
            if ($now > $exp + $graceDays * 86400) {
                return array_merge($this->fail('expired', 'Lisans süresi doldu.'), [
                    'plan' => (string) ($claims['plan'] ?? ''),
                    'expires_at' => $this->iso($exp),
                    'tenant' => $this->tenant($claims),
                ]);
            }
            if ($now > $exp) {
                $status = 'grace';
            }
        }

        // Domain bağlama
        $domains = $claims['dom'] ?? [];
        if (! is_array($domains)) {
            $domains = [];
        }
        $domainOk = $this->hostMatches($host, $domains);
        if ($host !== null && ! $domainOk) {
            return array_merge($this->fail('domain_mismatch', 'Lisans bu alan adına/host\'a bağlı değil.'), [
                'plan' => (string) ($claims['plan'] ?? ''),
                'bound_domains' => array_values($domains),
                'host' => $host,
            ]);
        }

        return [
            'valid' => true,
            'plan' => (string) ($claims['plan'] ?? 'standard'),
            'plan_name' => (string) ($claims['plan_name'] ?? ($claims['plan'] ?? 'standard')),
            'features' => $this->normalizeFeatures($claims['feat'] ?? []),
            'expires_at' => $exp > 0 ? $this->iso($exp) : null,
            'status' => $status,
            'code' => $status === 'grace' ? 'grace' : 'ok',
            'message' => $status === 'grace' ? 'Lisans süresi doldu, ek süre (grace) içinde.' : null,
            'source' => 'offline',
            'license_id' => (string) ($claims['lid'] ?? ''),
            'tenant' => $this->tenant($claims),
            'bound_domains' => array_values($domains),
        ];
    }

    /**
     * Doğrulamadan içeriği çözer (destek/teşhis için).
     *
     * @return array<string, mixed>
     */
    public function inspect(string $key, ?string $publicKeyB64 = null): array
    {
        $parts = explode('.', trim($key));
        $claims = null;
        if (count($parts) === 3 && $parts[0] === self::PREFIX) {
            $json = $this->b64urlDecode($parts[1]);
            if ($json !== false) {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    $claims = $decoded;
                }
            }
        }

        return [
            'claims' => $claims,
            'verification' => $this->verify($key, null, $publicKeyB64),
        ];
    }

    public function publicKey(): string
    {
        return trim((string) config('panelze.license.public_key', ''));
    }

    /**
     * @param  array<int|string, mixed>  $feat
     * @return array<string, array{enabled:bool, quota:int|null}>
     */
    private function normalizeFeatures(array $feat): array
    {
        $out = [];
        foreach ($feat as $k => $v) {
            if (is_int($k)) {
                // liste biçimi: ["phpmyadmin_sso", "security_pro"]
                $out[(string) $v] = ['enabled' => true, 'quota' => null];
            } elseif (is_array($v)) {
                $out[(string) $k] = [
                    'enabled' => (bool) ($v['enabled'] ?? true),
                    'quota' => isset($v['quota']) ? (int) $v['quota'] : null,
                ];
            } else {
                $out[(string) $k] = ['enabled' => (bool) $v, 'quota' => null];
            }
        }

        return $out;
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
                $suffix = substr($d, 1); // ".x.com"
                if ($host === substr($d, 2) || str_ends_with($host, $suffix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $claims
     * @return array<string, mixed>
     */
    private function tenant(array $claims): array
    {
        return [
            'name' => (string) ($claims['to'] ?? ''),
            'license_id' => (string) ($claims['lid'] ?? ''),
        ];
    }

    private function iso(int $unix): string
    {
        return gmdate('c', $unix);
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
