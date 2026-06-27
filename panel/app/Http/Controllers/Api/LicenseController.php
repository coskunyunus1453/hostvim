<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicenseHubClient;
use App\Services\OfflineLicenseService;
use App\Services\PanelLicenseService;
use App\Services\PanelStoredLicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(
        private LicenseHubClient $licenseHub,
        private OfflineLicenseService $offline,
        private PanelStoredLicenseService $storedLicense,
        private PanelLicenseService $panelLicense,
    ) {}

    public function status(Request $request): JsonResponse
    {
        $key = $this->storedLicense->effectiveKey() ?: (string) $request->query('key', '');
        $key = trim($key);
        $keySource = $this->storedLicense->keySource();
        $hubBase = rtrim(trim((string) config('panelze.license_server', '')), '/');
        $hubConfigured = $hubBase !== '';

        $base = [
            'local_key_set' => $key !== '',
            'key_source' => $keySource,
            'key_preview' => $key !== '' ? PanelStoredLicenseService::maskKey($key) : null,
            'hub_configured' => $hubConfigured,
            'offline_enabled' => $this->offline->publicKey() !== '',
        ];

        // 1) Çevrimdışı imzalı anahtar (ana otorite)
        $offline = ($key !== '' && $this->offline->publicKey() !== '')
            ? $this->offline->verify($key, $this->appHost())
            : null;
        if ($offline !== null && ($offline['valid'] ?? false)) {
            return response()->json(array_merge($base, [
                'source' => 'offline',
                'offline' => $offline,
                'hub' => null,
            ]));
        }

        // 2) Online hub
        $hub = $key !== '' ? $this->licenseHub->validate($key) : [];
        if ($hub !== []) {
            return response()->json(array_merge($base, [
                'source' => 'license_server',
                'hub' => $hub,
                'offline' => $offline,
            ]));
        }

        // 3) Geçersiz / yok
        return response()->json(array_merge($base, [
            'source' => $offline !== null ? 'offline' : 'none',
            'hub' => null,
            'offline' => $offline,
        ]));
    }

    /**
     * Anahtarı kaydetmeden doğrulama (admin testi).
     */
    public function validateWithKey(Request $request): JsonResponse
    {
        $validated = $request->validate(['key' => ['required', 'string', 'max:512']]);
        $key = trim($validated['key']);

        if ($this->offline->publicKey() !== '') {
            $offline = $this->offline->verify($key, $this->appHost());
            if (($offline['valid'] ?? false) || ($offline['code'] ?? '') !== 'malformed') {
                return response()->json($offline);
            }
        }

        $hub = $this->licenseHub->validate($key);
        if ($hub !== []) {
            return response()->json($hub);
        }

        return response()->json([
            'valid' => false,
            'code' => 'unverifiable',
            'message' => 'Anahtar offline imza ile doğrulanamadı ve lisans sunucusu yapılandırılmamış/ulaşılamıyor.',
        ]);
    }

    /**
     * Anahtarı doğrular ve geçerliyse panel veritabanında şifreli saklar (.env gerekmez).
     * Önce çevrimdışı imza, sonra (yapılandırılmışsa) online hub denenir.
     */
    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate(['key' => ['required', 'string', 'max:512']]);
        $key = trim($validated['key']);

        // 1) Çevrimdışı imzalı anahtar
        if ($this->offline->publicKey() !== '') {
            $offline = $this->offline->verify($key, $this->appHost());
            if ($offline['valid'] ?? false) {
                return $this->persist($key, ['offline' => $offline]);
            }
            // İmza geçerli ama süresi dolmuş/domain uyumsuz: net hata ver (hub'a düşme).
            if (($offline['code'] ?? '') !== 'malformed') {
                return response()->json([
                    'message' => (string) ($offline['message'] ?? 'Lisans anahtarı geçersiz.'),
                    'code' => (string) ($offline['code'] ?? 'invalid'),
                ], 422);
            }
        }

        // 2) Online hub (offline imzası olmayan eski anahtarlar)
        $base = rtrim(trim((string) config('panelze.license_server', '')), '/');
        if ($base === '') {
            return response()->json([
                'message' => 'Geçersiz lisans anahtarı ve lisans sunucusu (LICENSE_SERVER_URL) yapılandırılmamış.',
            ], 422);
        }

        $hub = $this->licenseHub->validate($key);
        if ($hub === []) {
            return response()->json([
                'message' => 'Lisans sunucusuna ulaşılamadı. LICENSE_SERVER_URL, ağ/güvenlik duvarını kontrol edin.',
            ], 503);
        }

        if (($hub['code'] ?? '') === 'hub_unauthorized') {
            return response()->json([
                'message' => (string) ($hub['message'] ?? 'License hub API token mismatch'),
                'code' => 'hub_unauthorized',
            ], 502);
        }

        if (! ($hub['valid'] ?? false)) {
            return response()->json([
                'message' => (string) ($hub['message'] ?? 'Invalid license key'),
                'code' => $hub['code'] ?? 'invalid',
            ], 422);
        }

        return $this->persist($key, ['hub' => $hub]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function persist(string $key, array $extra = []): JsonResponse
    {
        try {
            $this->storedLicense->store($key);
            $this->panelLicense->forgetCache();
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Lisans anahtarı kaydedilemedi.'], 500);
        }

        return response()->json(array_merge([
            'ok' => true,
            'key_source' => $this->storedLicense->keySource(),
            'key_preview' => PanelStoredLicenseService::maskKey($key),
        ], $extra));
    }

    private function appHost(): ?string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }

    public function clearStored(): JsonResponse
    {
        if ($this->storedLicense->keySource() === 'env') {
            return response()->json([
                'message' => 'License key is set via server environment (LICENSE_KEY). Remove it from .env to clear.',
            ], 422);
        }

        $this->storedLicense->clearStored();
        $this->panelLicense->forgetCache();

        return response()->json(['ok' => true]);
    }
}
