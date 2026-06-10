<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Merkezi lisans sunucusu (ör. panelze.com / landing) ile konuşur.
 * LICENSE_SERVER_URL .env’de doluysa önce buraya POST edilir.
 */
class LicenseHubClient
{
    /**
     * @return array<string, mixed> Boş dizi = hub yapılandırılmamış veya hata
     */
    public function validate(string $key): array
    {
        $key = trim($key);
        if ($key === '') {
            return [];
        }

        $base = (string) config('panelze.license_server', '');
        if ($base === '') {
            return [];
        }

        try {
            $req = Http::timeout(10)->acceptJson()->asJson();
            $secret = trim((string) config('panelze.license_server_api_secret', ''));
            if ($secret !== '') {
                $req = $req->withToken($secret);
            }
            $response = $req->post($base.'/api/v1/license/validate', ['key' => $key]);
            if ($response->successful()) {
                $json = $response->json();
                if (is_array($json) && array_key_exists('valid', $json)) {
                    return $json;
                }
                Log::warning('License hub unexpected JSON', ['url' => $base]);

                return [];
            }

            if (in_array($response->status(), [401, 403], true)) {
                Log::warning('License hub auth rejected', ['status' => $response->status(), 'url' => $base]);

                return [
                    'valid' => false,
                    'code' => 'hub_unauthorized',
                    'message' => 'License hub rejected API token. Set LICENSE_SERVER_API_SECRET on the panel to match PANELZE_LICENSE_API_SECRET on the landing server.',
                    'http_status' => $response->status(),
                ];
            }

            Log::warning('License hub HTTP '.$response->status(), ['url' => $base]);
        } catch (\Throwable $e) {
            Log::warning('License hub error: '.$e->getMessage());
        }

        return [];
    }
}
