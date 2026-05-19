<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PanelReleaseQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanelUpdateCheckController extends Controller
{
    public function __invoke(Request $request, PanelReleaseQueryService $releases): JsonResponse
    {
        $secret = (string) config('hostvim_saas.panel_updates_api_secret', '');
        if ($secret !== '') {
            if ($request->bearerToken() !== $secret) {
                return response()->json([
                    'ok' => false,
                    'code' => 'unauthorized',
                    'message' => 'Invalid API token',
                ], 401);
            }
        }

        $validated = $request->validate([
            'current' => ['required', 'string', 'max:32', 'regex:/^\d+\.\d+\.\d+([\-+][0-9A-Za-z\.\-]+)?$/'],
            'profile' => ['nullable', 'string', 'max:32', 'in:customer,vendor,pro,all'],
            'channel' => ['nullable', 'string', 'max:32'],
        ]);

        $current = $validated['current'];
        $profile = strtolower((string) ($validated['profile'] ?? 'customer'));
        if ($profile === 'vendor') {
            $profile = 'pro';
        }
        $channel = strtolower((string) ($validated['channel'] ?? 'stable'));

        $latest = $releases->latestFor($channel, $profile);
        if ($latest === null || ! $releases->canUpgradeFrom($current, $latest)) {
            return response()->json([
                'ok' => true,
                'update_available' => false,
                'current' => $current,
                'channel' => $channel,
                'profile' => $profile,
            ]);
        }

        return response()->json([
            'ok' => true,
            'update_available' => true,
            'current' => $current,
            'channel' => $channel,
            'profile' => $profile,
            'latest' => $releases->serializeRelease($latest),
        ]);
    }
}
