<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PanelUpdateHubService
{
    /**
     * @return array<string, mixed>|null
     */
    public function checkForUpdate(?string $currentVersion = null): ?array
    {
        $current = $currentVersion ?? (string) config('hostvim.version', '0.0.0');
        $profile = $this->panelProfile();
        $channel = (string) config('hostvim.updates.channel', 'stable');
        $hub = rtrim((string) config('hostvim.updates.hub_url', config('hostvim.license_server', '')), '/');
        if ($hub === '') {
            return null;
        }

        $cacheKey = 'hostvim:panel-update-check:'.md5($hub.'|'.$current.'|'.$profile.'|'.$channel);
        $ttl = max(60, (int) config('hostvim.updates.check_cache_seconds', 300));

        return Cache::remember($cacheKey, $ttl, function () use ($hub, $current, $profile, $channel): ?array {
            $url = $hub.'/api/v1/panel-updates/check';
            $request = Http::timeout(12)->acceptJson();
            $secret = trim((string) config('hostvim.updates.api_secret', ''));
            if ($secret !== '') {
                $request = $request->withToken($secret);
            }

            try {
                $response = $request->get($url, [
                    'current' => $current,
                    'profile' => $profile,
                    'channel' => $channel,
                ]);
            } catch (\Throwable $e) {
                Log::warning('panel_update_hub_unreachable', ['error' => $e->getMessage()]);

                return null;
            }

            if (! $response->successful()) {
                Log::warning('panel_update_hub_error', ['status' => $response->status()]);

                return null;
            }

            $body = $response->json();
            if (! is_array($body) || empty($body['ok'])) {
                return null;
            }

            return $body;
        });
    }

    public function panelProfile(): string
    {
        $profile = strtolower((string) config('hostvim.profile', 'customer'));
        if ($profile === 'vendor') {
            return 'pro';
        }

        return $profile === 'pro' ? 'pro' : 'customer';
    }

    public function updateAvailable(?array $hubPayload = null): bool
    {
        $payload = $hubPayload ?? $this->checkForUpdate();

        return is_array($payload) && ! empty($payload['update_available']) && is_array($payload['latest'] ?? null);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestRelease(?array $hubPayload = null): ?array
    {
        $payload = $hubPayload ?? $this->checkForUpdate();
        if (! $this->updateAvailable($payload)) {
            return null;
        }

        $latest = $payload['latest'] ?? null;

        return is_array($latest) ? $latest : null;
    }
}
