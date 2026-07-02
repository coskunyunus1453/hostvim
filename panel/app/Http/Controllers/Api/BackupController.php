<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Jobs\RunBackupJob;
use App\Models\Backup;
use App\Models\BackupDestination;
use App\Models\BackupSchedule;
use App\Models\Domain;
use App\Services\BackupStorageService;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use App\Services\SafeAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private EngineApiService $engine,
        private HostingQuotaService $quota,
        private BackupStorageService $storage,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->backups()->with(['domain', 'destination'])->latest();
        if ($request->filled('domain_id')) {
            $query->where('domain_id', (int) $request->integer('domain_id'));
        }
        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            if ($status === 'active') {
                $query->whereIn('status', ['pending', 'queued', 'running', 'syncing']);
            } elseif (in_array($status, ['pending', 'queued', 'running', 'syncing', 'completed', 'failed'], true)) {
                $query->where('status', $status);
            }
        }
        $perPage = min(max((int) $request->input('per_page', 50), 1), 100);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id',
            'type' => 'nullable|string|in:full,incremental,files,database',
            'destination_id' => 'nullable|integer|exists:backup_destinations,id',
        ]);
        $domain = Domain::findOrFail($validated['domain_id']);
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $destinationId = $validated['destination_id'] ?? null;
        if (! empty($destinationId)) {
            $ownsDestination = BackupDestination::query()
                ->where('id', (int) $destinationId)
                ->where('user_id', $request->user()->id)
                ->exists();
            if (! $ownsDestination) {
                abort(403);
            }
        }

        $this->quota->ensureCanQueueBackup($request->user());

        $type = $validated['type'] ?? 'full';
        $level = 0;
        $parentId = null;
        $baseId = null;
        if ($type === 'incremental') {
            $parent = $this->resolveIncrementalParent($request->user()->id, $domain->id, $destinationId);
            if ($parent) {
                $level = (int) $parent->level + 1;
                $parentId = $parent->id;
                $baseId = $parent->base_backup_id ?: $parent->id;
            } else {
                // Zincir yok → otomatik olarak tam (base) yedek al.
                $type = 'full';
            }
        }

        $backup = Backup::create([
            'user_id' => $request->user()->id,
            'domain_id' => $domain->id,
            'destination_id' => $destinationId,
            'type' => $type,
            'level' => $level,
            'parent_backup_id' => $parentId,
            'base_backup_id' => $baseId,
            'status' => 'queued',
        ]);

        RunBackupJob::dispatch($backup->id);

        $this->audit($request, 'backup_queue', true, null, [
            'domain_id' => $domain->id,
            'backup_id' => $backup->id,
            'destination_id' => $backup->destination_id,
            'type' => $backup->type,
            'level' => $backup->level,
        ]);

        return response()->json([
            'message' => __('backups.queued'),
            'backup' => $backup->fresh(['domain', 'destination']),
        ], 202);
    }

    /**
     * Arttırımlı yedek için zincirin ucundaki (en güncel tamamlanmış, snapshot'lı) yedeği bulur.
     * Zincir, domain + hedef (destination) bazında ayrılır ki farklı hedeflerin snapshot
     * zincirleri birbirine karışmasın.
     */
    private function resolveIncrementalParent(int $userId, int $domainId, ?int $destinationId): ?Backup
    {
        return Backup::query()
            ->where('user_id', $userId)
            ->where('domain_id', $domainId)
            ->where('status', 'completed')
            ->whereNotNull('snapshot_path')
            ->when($destinationId !== null, fn ($q) => $q->where('destination_id', $destinationId))
            ->when($destinationId === null, fn ($q) => $q->whereNull('destination_id'))
            ->orderByDesc('id')
            ->first();
    }

    public function destinations(Request $request): JsonResponse
    {
        $rows = BackupDestination::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->get();

        return response()->json(['destinations' => $rows]);
    }

    public function storeDestination(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'driver' => ['required', 'string', Rule::in(['local', 's3', 'ftp', 'google_drive'])],
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'config' => 'nullable|array',
        ]);
        if (($validated['is_default'] ?? false) === true) {
            BackupDestination::query()->where('user_id', $request->user()->id)->update(['is_default' => false]);
        }
        $dest = BackupDestination::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'driver' => $validated['driver'],
            'config' => $validated['config'] ?? [],
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);
        $this->audit($request, 'backup_destination_create', true, null, ['destination_id' => $dest->id]);

        return response()->json(['message' => __('backups.destination_saved'), 'destination' => $dest], 201);
    }

    public function updateDestination(Request $request, BackupDestination $backupDestination): JsonResponse
    {
        if ($backupDestination->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'driver' => ['sometimes', 'string', Rule::in(['local', 's3', 'ftp', 'google_drive'])],
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'config' => 'nullable|array',
        ]);
        if (($validated['is_default'] ?? false) === true) {
            BackupDestination::query()->where('user_id', $backupDestination->user_id)->update(['is_default' => false]);
        }
        $backupDestination->fill($validated);
        $backupDestination->save();
        $this->audit($request, 'backup_destination_update', true, null, ['destination_id' => $backupDestination->id]);

        return response()->json(['message' => __('backups.destination_saved'), 'destination' => $backupDestination->fresh()]);
    }

    public function destroyDestination(Request $request, BackupDestination $backupDestination): JsonResponse
    {
        if ($backupDestination->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }
        $id = $backupDestination->id;
        $backupDestination->delete();
        $this->audit($request, 'backup_destination_delete', true, null, ['destination_id' => $id]);

        return response()->json(['message' => __('backups.deleted')]);
    }

    public function schedules(Request $request): JsonResponse
    {
        $rows = BackupSchedule::query()
            ->where('user_id', $request->user()->id)
            ->with(['domain:id,name', 'destination:id,name,driver'])
            ->latest('id')
            ->get();

        return response()->json(['schedules' => $rows]);
    }

    public function storeSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_id' => 'required|integer|exists:domains,id',
            'destination_id' => 'nullable|integer|exists:backup_destinations,id',
            'type' => 'nullable|string|in:full,incremental,files,database',
            'full_interval_days' => 'nullable|integer|min:1|max:365',
            'retention_count' => 'nullable|integer|min:1|max:100',
            'schedule' => ['required', 'string', 'regex:/^\S+\s+\S+\s+\S+\s+\S+\s+\S+$/'],
            'enabled' => 'sometimes|boolean',
        ]);
        $domain = Domain::findOrFail($validated['domain_id']);
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        if (! empty($validated['destination_id'])) {
            $ownsDestination = BackupDestination::query()
                ->where('id', (int) $validated['destination_id'])
                ->where('user_id', $request->user()->id)
                ->exists();
            if (! $ownsDestination) {
                abort(403);
            }
        }
        $row = BackupSchedule::create([
            'user_id' => $request->user()->id,
            'domain_id' => $domain->id,
            'destination_id' => $validated['destination_id'] ?? null,
            'type' => $validated['type'] ?? 'full',
            'full_interval_days' => $validated['full_interval_days'] ?? 7,
            'retention_count' => $validated['retention_count'] ?? null,
            'schedule' => $validated['schedule'],
            'enabled' => (bool) ($validated['enabled'] ?? true),
        ]);
        $this->audit($request, 'backup_schedule_create', true, null, ['schedule_id' => $row->id, 'domain_id' => $domain->id]);

        return response()->json(['message' => __('backups.schedule_saved'), 'schedule' => $row->fresh(['domain:id,name', 'destination:id,name,driver'])], 201);
    }

    public function updateSchedule(Request $request, BackupSchedule $backupSchedule): JsonResponse
    {
        if ($backupSchedule->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }
        $validated = $request->validate([
            'destination_id' => 'nullable|integer|exists:backup_destinations,id',
            'type' => 'nullable|string|in:full,incremental,files,database',
            'full_interval_days' => 'nullable|integer|min:1|max:365',
            'retention_count' => 'nullable|integer|min:1|max:100',
            'schedule' => ['sometimes', 'string', 'regex:/^\S+\s+\S+\s+\S+\s+\S+\s+\S+$/'],
            'enabled' => 'sometimes|boolean',
        ]);
        if (! empty($validated['destination_id'])) {
            $ownsDestination = BackupDestination::query()
                ->where('id', (int) $validated['destination_id'])
                ->where('user_id', $request->user()->id)
                ->exists();
            if (! $ownsDestination) {
                abort(403);
            }
        }
        $backupSchedule->fill($validated);
        $backupSchedule->save();
        $this->audit($request, 'backup_schedule_update', true, null, ['schedule_id' => $backupSchedule->id]);

        return response()->json(['message' => __('backups.schedule_saved'), 'schedule' => $backupSchedule->fresh(['domain:id,name', 'destination:id,name,driver'])]);
    }

    public function destroySchedule(Request $request, BackupSchedule $backupSchedule): JsonResponse
    {
        if ($backupSchedule->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }
        $id = $backupSchedule->id;
        $backupSchedule->delete();
        $this->audit($request, 'backup_schedule_delete', true, null, ['schedule_id' => $id]);

        return response()->json(['message' => __('backups.deleted')]);
    }

    public function runSchedule(Request $request, BackupSchedule $backupSchedule): JsonResponse
    {
        if ($backupSchedule->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }
        $domain = $backupSchedule->domain()->first();
        if (! $domain) {
            return response()->json(['message' => __('backups.domain_not_found')], 422);
        }
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $this->quota->ensureCanQueueBackup($request->user());

        $type = $backupSchedule->type ?: 'full';
        $level = 0;
        $parentId = null;
        $baseId = null;
        if ($type === 'incremental') {
            $parent = $this->resolveIncrementalParent($request->user()->id, $domain->id, $backupSchedule->destination_id);
            if ($parent) {
                $level = (int) $parent->level + 1;
                $parentId = $parent->id;
                $baseId = $parent->base_backup_id ?: $parent->id;
            } else {
                $type = 'full';
            }
        }

        $backup = Backup::create([
            'user_id' => $request->user()->id,
            'domain_id' => $domain->id,
            'destination_id' => $backupSchedule->destination_id,
            'type' => $type,
            'level' => $level,
            'parent_backup_id' => $parentId,
            'base_backup_id' => $baseId,
            'status' => 'queued',
        ]);
        RunBackupJob::dispatch($backup->id);
        $backupSchedule->update(['last_run_at' => now()]);
        $this->audit($request, 'backup_schedule_run', true, null, ['schedule_id' => $backupSchedule->id, 'backup_id' => $backup->id]);

        return response()->json([
            'message' => __('backups.queued'),
            'backup' => $backup->fresh(['domain', 'destination']),
        ], 202);
    }

    public function retry(Request $request, Backup $backup): JsonResponse
    {
        if ($backup->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }
        if ($backup->status === 'completed') {
            return response()->json(['message' => __('backups.retry_already_completed')], 422);
        }
        if (in_array($backup->status, ['pending', 'queued', 'running', 'syncing'], true)) {
            return response()->json(['message' => __('backups.retry_already_running')], 422);
        }
        $backup->update(['status' => 'queued']);
        RunBackupJob::dispatch($backup->id);
        $this->audit($request, 'backup_retry', true, null, ['backup_id' => $backup->id]);

        return response()->json([
            'message' => __('backups.queued'),
            'backup' => $backup->fresh(['domain', 'destination']),
        ], 202);
    }

    public function destroy(Request $request, Backup $backup): JsonResponse
    {
        if ($backup->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        // Zincir güvenliği: bu yedeğe bağlı arttırımlı yedek(ler) varsa silmeye izin verme.
        // (Base/parent silinirse zincir bozulur, restore imkânsız hale gelir.)
        $hasDependents = Backup::query()
            ->where('id', '!=', $backup->id)
            ->where(function ($q) use ($backup) {
                $q->where('parent_backup_id', $backup->id)
                    ->orWhere('base_backup_id', $backup->id);
            })
            ->exists();
        if ($hasDependents) {
            return response()->json(['message' => __('backups.delete_has_dependents')], 422);
        }

        $this->purgeBackupArtifacts($backup);
        $backup->delete();
        $this->audit($request, 'backup_delete', true, null, ['backup_id' => $backup->id]);

        return response()->json(['message' => __('backups.deleted')]);
    }

    /**
     * Bir yedeğin engine (arşiv+snapshot) ve uzak hedef dosyalarını temizler (best-effort).
     */
    public function purgeBackupArtifacts(Backup $backup): void
    {
        $eid = trim((string) $backup->engine_backup_id);
        if ($eid !== '') {
            try {
                $this->engine->deleteBackup($eid);
            } catch (\Throwable $e) {
                SafeAuditLogger::warning('panelze.backup_engine_delete_failed', [
                    'backup_id' => $backup->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        if ($backup->destination_id && ($backup->remote_path || $backup->remote_file_id)) {
            try {
                $dest = $backup->destination;
                if ($dest) {
                    $this->storage->deleteRemote($dest, (string) $backup->remote_path, $backup->remote_file_id ? (string) $backup->remote_file_id : null);
                }
            } catch (\Throwable $e) {
                SafeAuditLogger::warning('panelze.backup_remote_delete_failed', [
                    'backup_id' => $backup->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function restore(Request $request, Backup $backup): JsonResponse
    {
        if ($backup->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }
        $domain = $backup->domain;
        if (! $domain || ! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $validated = $request->validate([
            'source' => 'nullable|string|in:engine,remote',
            'destination_id' => 'nullable|integer|exists:backup_destinations,id',
            'backup_set' => 'nullable|string|max:255',
        ]);
        $source = (string) ($validated['source'] ?? 'engine');
        $eid = $backup->engine_backup_id;

        if ($source === 'remote') {
            $destId = $validated['destination_id'] ?? $backup->destination_id;
            $setRaw = (string) ($validated['backup_set'] ?? '');
            if (! $destId || trim($setRaw) === '') {
                return response()->json(['message' => __('backups.remote_restore_missing')], 422);
            }
            $remoteKey = $this->sanitizeRemoteBackupSet($setRaw);
            if ($remoteKey === null) {
                return response()->json(['message' => __('backups.remote_restore_invalid_path')], 422);
            }
            $dest = BackupDestination::query()
                ->where('id', (int) $destId)
                ->where('user_id', $request->user()->id)
                ->first();
            if (! $dest) {
                abort(403);
            }
            if (! $dest->is_active) {
                return response()->json(['message' => __('backups.remote_restore_destination_inactive')], 422);
            }

            $tmpPath = null;
            try {
                $dl = $this->storage->fetchRemoteToTemp($dest, $remoteKey, $dest->driver === 'google_drive' ? $this->storage->parseGoogleFileId($remoteKey) : null);
                if (! $dl['ok']) {
                    $this->audit($request, 'backup_restore_remote', false, $dl['error'] ?? 'download failed', [
                        'backup_id' => $backup->id,
                        'destination_id' => $destId,
                        'backup_set' => $remoteKey,
                    ]);

                    return response()->json(['message' => $dl['error'] ?? __('backups.remote_restore_download_failed')], 422);
                }
                $tmpPath = $dl['path'];
                $result = $this->engine->restoreBackupUpload($tmpPath, basename($remoteKey));
                if (! empty($result['error'])) {
                    $this->audit($request, 'backup_restore_remote', false, (string) $result['error'], [
                        'backup_id' => $backup->id,
                        'destination_id' => $destId,
                        'backup_set' => $remoteKey,
                    ]);

                    return response()->json(['message' => (string) $result['error']], 502);
                }
                $this->audit($request, 'backup_restore_remote', true, null, [
                    'backup_id' => $backup->id,
                    'destination_id' => $destId,
                    'backup_set' => $remoteKey,
                ]);

                return response()->json($this->publicBackupPayload($request, [
                    'message' => __('backups.restore_started'),
                    'engine' => $result,
                ]));
            } finally {
                if (is_string($tmpPath) && $tmpPath !== '' && is_file($tmpPath)) {
                    @unlink($tmpPath);
                }
            }
        }

        if ($eid === null || $eid === '') {
            return response()->json(['message' => __('backups.restore_no_engine_id')], 422);
        }

        // Arttırımlı zincir: base → ... → hedef sırayla geri yüklenmeli.
        // Tek (tam) yedekte zincir tek elemanlıdır.
        $chain = $backup->restoreChain();
        $chainIds = [];
        foreach ($chain as $node) {
            $nid = trim((string) $node->engine_backup_id);
            if ($nid === '') {
                return response()->json(['message' => __('backups.restore_chain_broken')], 422);
            }
            $chainIds[] = $nid;
        }

        if (count($chainIds) > 1) {
            $result = $this->engine->restoreBackupChain($chainIds);
        } else {
            $result = $this->engine->restoreBackup($eid);
        }
        $this->audit($request, 'backup_restore', true, null, [
            'backup_id' => $backup->id,
            'engine_backup_id' => $eid,
            'chain' => $chainIds,
        ]);

        return response()->json($this->publicBackupPayload($request, [
            'message' => __('backups.restore_started'),
            'engine' => $result,
        ]));
    }

    public function engineSnapshot(Request $request): JsonResponse
    {
        $rows = $this->engine->listBackups();
        if ($request->user()->isAdmin()) {
            return response()->json(['remote' => $rows]);
        }

        $allowedDomains = Domain::query()
            ->where('user_id', $request->user()->id)
            ->pluck('name')
            ->map(static fn ($v) => strtolower(trim((string) $v)))
            ->filter()
            ->values()
            ->all();
        $allowedEngineIds = Backup::query()
            ->where('user_id', $request->user()->id)
            ->whereNotNull('engine_backup_id')
            ->pluck('engine_backup_id')
            ->map(static fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();
        $allowedPanelIds = Backup::query()
            ->where('user_id', $request->user()->id)
            ->pluck('id')
            ->map(static fn ($v) => (string) $v)
            ->values()
            ->all();

        $filtered = array_values(array_filter($rows, static function ($row) use ($allowedDomains, $allowedEngineIds, $allowedPanelIds): bool {
            if (! is_array($row)) {
                return false;
            }
            $domain = strtolower(trim((string) ($row['domain'] ?? '')));
            if ($domain !== '' && in_array($domain, $allowedDomains, true)) {
                return true;
            }
            $engineId = trim((string) ($row['id'] ?? ($row['engine_backup_id'] ?? '')));
            if ($engineId !== '' && in_array($engineId, $allowedEngineIds, true)) {
                return true;
            }
            $panelBackupId = trim((string) ($row['panel_backup_id'] ?? ''));

            return $panelBackupId !== '' && in_array($panelBackupId, $allowedPanelIds, true);
        }));

        return response()->json(['remote' => $filtered]);
    }

    public function sync(Request $request, Backup $backup): JsonResponse
    {
        if ($backup->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }
        $result = $this->syncToDestination($backup);
        if (! $result['ok']) {
            $this->audit($request, 'backup_sync', false, $result['error'] ?? 'sync failed', ['backup_id' => $backup->id]);

            return response()->json(['message' => $result['error'] ?? 'sync failed'], 422);
        }
        $this->audit($request, 'backup_sync', true, null, ['backup_id' => $backup->id]);

        return response()->json(['message' => __('backups.synced'), 'remote_path' => $result['remote_path'] ?? null]);
    }

    public function syncToDestination(Backup $backup): array
    {
        $result = $this->storage->syncBackup($backup);
        if ($result['ok'] ?? false) {
            $backup->update([
                'remote_path' => $result['remote_path'] ?? null,
                'remote_file_id' => $result['remote_file_id'] ?? null,
            ]);
        }

        return $result;
    }

    public function download(Request $request, Backup $backup): StreamedResponse|JsonResponse
    {
        if ($backup->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $localPath = trim((string) $backup->file_path);
        if ($localPath !== '' && is_file($localPath)) {
            return response()->streamDownload(static function () use ($localPath): void {
                $stream = fopen($localPath, 'rb');
                if ($stream !== false) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, basename($localPath), ['Content-Type' => 'application/gzip']);
        }

        if ($backup->destination_id && ($backup->remote_path || $backup->remote_file_id)) {
            $dest = BackupDestination::query()->find($backup->destination_id);
            if ($dest && $dest->is_active && $dest->user_id === $backup->user_id) {
                $dl = $this->storage->fetchRemoteToTemp(
                    $dest,
                    (string) ($backup->remote_path ?? ''),
                    $backup->remote_file_id,
                );
                if ($dl['ok'] ?? false) {
                    $tmp = $dl['path'];
                    $name = basename((string) ($backup->remote_path ?: 'backup.tar.gz'));

                    return response()->streamDownload(static function () use ($tmp): void {
                        $stream = fopen($tmp, 'rb');
                        if ($stream !== false) {
                            fpassthru($stream);
                            fclose($stream);
                        }
                        @unlink($tmp);
                    }, $name, ['Content-Type' => 'application/gzip']);
                }

                return response()->json(['message' => $dl['error'] ?? __('backups.download_unavailable')], 422);
            }
        }

        return response()->json(['message' => __('backups.download_unavailable')], 422);
    }

    public function uploadRestore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_id' => 'required|integer|exists:domains,id',
            'archive' => [
                'required',
                'file',
                'max:'.((int) config('panelze.limits.max_upload_size_mb', 256) * 1024),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof \Illuminate\Http\UploadedFile) {
                        return;
                    }
                    $name = strtolower($value->getClientOriginalName());
                    if (! (str_ends_with($name, '.tar.gz') || str_ends_with($name, '.tgz') || str_ends_with($name, '.gz'))) {
                        $fail((string) __('backups.upload_invalid_archive'));
                    }
                },
            ],
        ]);
        $domain = Domain::findOrFail($validated['domain_id']);
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $file = $request->file('archive');
        if ($file === null) {
            return response()->json(['message' => __('backups.upload_required')], 422);
        }
        $tmp = $file->getRealPath();
        if ($tmp === false) {
            return response()->json(['message' => __('backups.upload_failed')], 422);
        }

        $result = $this->engine->restoreBackupUpload($tmp, $file->getClientOriginalName());
        if (! empty($result['error'])) {
            $this->audit($request, 'backup_upload_restore', false, (string) $result['error'], [
                'domain_id' => $domain->id,
            ]);

            return response()->json(['message' => (string) $result['error']], 502);
        }

        $this->audit($request, 'backup_upload_restore', true, null, ['domain_id' => $domain->id]);

        return response()->json($this->publicBackupPayload($request, [
            'message' => __('backups.restore_started'),
            'engine' => $result,
        ]));
    }

    public function restoreRemote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_id' => 'required|integer|exists:domains,id',
            'destination_id' => 'required|integer|exists:backup_destinations,id',
            'remote_file_id' => 'nullable|string|max:128',
            'backup_set' => 'nullable|string|max:512',
        ]);
        $domain = Domain::findOrFail($validated['domain_id']);
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }
        $dest = BackupDestination::query()
            ->where('id', (int) $validated['destination_id'])
            ->where('user_id', $request->user()->id)
            ->first();
        if (! $dest || ! $dest->is_active) {
            return response()->json(['message' => __('backups.remote_restore_destination_inactive')], 422);
        }

        $remoteKey = '';
        $fileId = trim((string) ($validated['remote_file_id'] ?? ''));
        if ($fileId !== '') {
            $remoteKey = 'google_drive:'.$fileId;
        } else {
            $setRaw = trim((string) ($validated['backup_set'] ?? ''));
            $remoteKey = $this->sanitizeRemoteBackupSet($setRaw) ?? '';
        }
        if ($remoteKey === '') {
            return response()->json(['message' => __('backups.remote_restore_missing')], 422);
        }

        $tmpPath = null;
        try {
            $dl = $this->storage->fetchRemoteToTemp($dest, $remoteKey, $fileId !== '' ? $fileId : null);
            if (! ($dl['ok'] ?? false)) {
                return response()->json(['message' => $dl['error'] ?? __('backups.remote_restore_download_failed')], 422);
            }
            $tmpPath = $dl['path'];
            $result = $this->engine->restoreBackupUpload($tmpPath, basename($remoteKey) ?: 'backup.tar.gz');
            if (! empty($result['error'])) {
                return response()->json(['message' => (string) $result['error']], 502);
            }

            return response()->json($this->publicBackupPayload($request, [
                'message' => __('backups.restore_started'),
                'engine' => $result,
            ]));
        } finally {
            if (is_string($tmpPath) && $tmpPath !== '' && is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /**
     * Uzak depodaki nesne anahtarı (sync ile yazılan path ile aynı hiyerarşi, örn. backups/site.tar.gz).
     */
    private function sanitizeRemoteBackupSet(string $set): ?string
    {
        $set = str_replace('\\', '/', trim($set));
        $set = ltrim($set, '/');
        if ($set === '' || str_contains($set, '..')) {
            return null;
        }

        return $set;
    }

    private function audit(Request $request, string $action, bool $success, ?string $error = null, array $extra = []): void
    {
        SafeAuditLogger::info('panelze.backup_audit', array_merge([
            'action' => $action,
            'success' => $success,
            'error' => $error,
        ], $extra), $request);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function publicBackupPayload(Request $request, array $payload): array
    {
        if (! $request->user()->isAdmin()) {
            unset($payload['engine']);
        }

        return $payload;
    }
}
