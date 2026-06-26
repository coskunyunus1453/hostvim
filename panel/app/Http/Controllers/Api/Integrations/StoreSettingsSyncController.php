<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Services\Integrations\StoreSettingsApplier;
use App\Services\SafeAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreSettingsSyncController extends Controller
{
    public function __construct(private StoreSettingsApplier $applier) {}

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'billing' => ['nullable', 'array'],
            'mail' => ['nullable', 'array'],
        ]);

        $applied = $this->applier->apply($validated);

        SafeAuditLogger::info('panelze.store.settings_sync', [
            'billing' => $applied['billing'],
            'mail' => $applied['mail'],
        ], $request);

        return response()->json([
            'ok' => true,
            'applied' => $applied,
        ]);
    }
}
