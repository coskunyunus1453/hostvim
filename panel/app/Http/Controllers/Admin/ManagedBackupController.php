<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupDestination;
use App\Models\BackupSchedule;
use App\Services\GoogleDriveConfigService;
use App\Services\GoogleDriveService;
use App\Services\ManagedBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Merkezi (şirket yönetimli) otomatik yedekleme yönetimi — sadece admin.
 * Şirketin kendi Google Drive havuz hesaplarını bağlar, günlük yedek ayarlarını yönetir.
 */
class ManagedBackupController extends Controller
{
    public function __construct(
        private ManagedBackupService $managed,
        private GoogleDriveService $googleDrive,
        private GoogleDriveConfigService $googleDriveConfig,
    ) {}

    public function status(Request $request): JsonResponse
    {
        $payload = $this->managed->statusPayload();
        $payload['credential_source'] = $this->googleDriveConfig->credentialSource();
        $payload['redirect_uri'] = $this->googleDrive->redirectUri();

        return response()->json($payload);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'sometimes|boolean',
            'hour' => 'sometimes|integer|min:0|max:23',
            'retention_count' => 'sometimes|integer|min:1|max:100',
            'full_interval_days' => 'sometimes|integer|min:1|max:365',
            'notify_email' => 'sometimes|nullable|string|max:500',
            'folder_name' => 'sometimes|nullable|string|max:120',
        ]);

        $this->managed->updateSettings($validated);
        // Ayarlar değişince zamanlamaları hemen senkronla (aç/kapa, saat, retention).
        $result = $this->managed->provision();

        return response()->json([
            'message' => __('backups.schedule_saved'),
            'provision' => $result,
            'status' => $this->managed->statusPayload(),
        ]);
    }

    public function authUrl(Request $request): JsonResponse
    {
        if (! $this->googleDrive->isConfigured()) {
            return response()->json(['message' => __('backups.google_drive_not_configured')], 422);
        }
        // system=true → sonuç is_system hedef olarak kaydedilir (müşteri hedeflerinden ayrık).
        $payload = $this->googleDrive->authorizationUrl((int) $request->user()->id, true);

        return response()->json($payload);
    }

    public function runNow(Request $request): JsonResponse
    {
        $result = $this->managed->provision();

        return response()->json([
            'message' => __('backups.schedule_saved'),
            'provision' => $result,
        ]);
    }

    public function disconnect(Request $request, BackupDestination $backupDestination): JsonResponse
    {
        if (! $backupDestination->is_system) {
            abort(404);
        }
        $id = $backupDestination->id;
        // Bu hesaba atanmış merkezi zamanlamaları önce boşa çıkar (reassign provision'da olur).
        BackupSchedule::query()
            ->where('is_managed', true)
            ->where('destination_id', $id)
            ->update(['destination_id' => null, 'enabled' => false]);
        $backupDestination->delete();

        // Kalan hesaplara yeniden dağıt.
        $this->managed->provision();

        return response()->json([
            'message' => __('backups.google_drive_disconnected'),
            'status' => $this->managed->statusPayload(),
        ]);
    }
}
