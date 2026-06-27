<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Licensing\SaasLicenseSigningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Panel kurulumda buraya opak anahtarı + kendi host'unu gönderir; geçerliyse
 * hub, o host'a bağlı çevrimdışı imzalı (PLZ1) anahtarı döner. Panel bundan
 * sonra internet olmadan da lisansı doğrular.
 */
class LicenseActivateController extends Controller
{
    public function __invoke(Request $request, SaasLicenseSigningService $signing): JsonResponse
    {
        $secret = (string) config('panelze_saas.license_api_secret', '');
        if ($secret !== '') {
            $bearer = $request->bearerToken();
            if ($bearer !== $secret) {
                return response()->json(['valid' => false, 'code' => 'unauthorized', 'message' => 'Invalid API token'], 401);
            }
        }

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:128'],
            'host' => ['nullable', 'string', 'max:253'],
        ]);

        $payload = $signing->activate(
            $validated['key'],
            $validated['host'] ?? null,
            $request->ip(),
            (string) $request->userAgent(),
        );

        return response()->json($payload);
    }
}
