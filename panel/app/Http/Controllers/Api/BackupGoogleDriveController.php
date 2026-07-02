<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackupDestination;
use App\Services\GoogleDriveConfigService;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupGoogleDriveController extends Controller
{
    public function __construct(
        private GoogleDriveService $googleDrive,
        private GoogleDriveConfigService $googleDriveConfig,
    ) {}

    public function status(Request $request): JsonResponse
    {
        $dest = BackupDestination::query()
            ->where('user_id', $request->user()->id)
            ->where('driver', 'google_drive')
            ->where('is_active', true)
            ->where('is_system', false)
            ->latest('id')
            ->first();

        $payload = [
            'configured' => $this->googleDrive->isConfigured(),
            'connected' => $dest !== null,
            'destination' => $dest ? [
                'id' => $dest->id,
                'name' => $dest->name,
                'email' => (array) ($dest->config ?? [])['email'] ?? null,
            ] : null,
        ];

        if ($request->user()->isAdmin()) {
            $payload['credential_source'] = $this->googleDriveConfig->credentialSource();
            $payload['hub_expected'] = $this->googleDriveConfig->hubFeatureExpected();
            $payload['redirect_uri'] = $this->googleDrive->redirectUri();
            $payload['hub_integrations_url'] = $this->hubIntegrationsAdminUrl();
        }

        return response()->json($payload);
    }

    public function authUrl(Request $request): JsonResponse
    {
        if (! $this->googleDrive->isConfigured()) {
            return response()->json(['message' => __('backups.google_drive_not_configured')], 422);
        }
        $payload = $this->googleDrive->authorizationUrl((int) $request->user()->id);

        return response()->json($payload);
    }

    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:2048',
            'state' => 'required|string|max:128',
            'name' => 'nullable|string|max:100',
        ]);
        $result = $this->googleDrive->completeOAuth(
            (int) $request->user()->id,
            $validated['code'],
            $validated['state'],
            (string) ($validated['name'] ?? 'Google Drive'),
        );
        if (! ($result['ok'] ?? false)) {
            return response()->json(['message' => (string) ($result['error'] ?? 'oauth_failed')], 422);
        }

        return response()->json([
            'message' => __('backups.google_drive_connected'),
            'destination' => $result['destination'],
            'system' => (bool) ($result['system'] ?? false),
        ], 201);
    }

    public function disconnect(Request $request): JsonResponse
    {
        BackupDestination::query()
            ->where('user_id', $request->user()->id)
            ->where('driver', 'google_drive')
            ->where('is_system', false)
            ->delete();

        return response()->json(['message' => __('backups.google_drive_disconnected')]);
    }

    public function listFiles(Request $request, BackupDestination $backupDestination): JsonResponse
    {
        if ($backupDestination->user_id !== $request->user()->id || $backupDestination->driver !== 'google_drive') {
            abort(403);
        }
        $domain = trim((string) $request->query('domain', ''));

        return response()->json([
            'files' => app(\App\Services\BackupStorageService::class)
                ->listRemoteFiles($backupDestination, $domain !== '' ? $domain : null),
        ]);
    }

    private function hubIntegrationsAdminUrl(): ?string
    {
        $base = rtrim(trim((string) config('panelze.license_server', '')), '/');
        if ($base === '') {
            return null;
        }

        return $base.'/admin/integrations-settings';
    }
}
