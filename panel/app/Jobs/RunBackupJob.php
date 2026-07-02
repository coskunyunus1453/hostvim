<?php

namespace App\Jobs;

use App\Http\Controllers\Api\BackupController;
use App\Models\Backup;
use App\Services\EngineApiService;
use App\Services\SafeAuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3700;

    public int $tries = 1;

    public function __construct(
        private readonly int $backupId,
    ) {}

    public function handle(EngineApiService $engine): void
    {
        $backup = Backup::query()->with(['domain', 'destination', 'parent'])->find($this->backupId);
        if (! $backup || ! $backup->domain) {
            return;
        }

        if (in_array($backup->status, ['completed', 'failed'], true)) {
            return;
        }

        $backup->update(['status' => 'running']);

        $timeout = (int) config('panelze.engine_backup_timeout', 3700);
        if ($timeout < 120) {
            $timeout = 3700;
        }

        // Arttırımlı yedek: parent'ın engine snapshot (.snar) yolundan devam et.
        $level = (int) ($backup->level ?? 0);
        $parentSnapshot = null;
        if ($level > 0 && $backup->parent) {
            $parentSnapshot = $backup->parent->snapshot_path;
            // Parent snapshot yoksa engine güvenli tarafa (tam yedek) düşer; level'ı da 0'a çekelim.
            if (! $parentSnapshot) {
                $level = 0;
                $backup->update(['level' => 0, 'parent_backup_id' => null, 'base_backup_id' => null]);
            }
        }

        // Engine artık ASENKRON çalışıyor: POST anında "running" döner (büyük siteler
        // 10+ dk sürebilir; senkron HTTP WriteTimeout'a takılıp yanlış "failed" +
        // duplicate üretiyordu). Kısa timeout yeterli.
        $result = $engine->queueBackupLong($backup->domain->name, $backup->type, $backup->id, 120, $level, $parentSnapshot);

        if (! empty($result['error'])) {
            $backup->update(['status' => 'failed']);
            SafeAuditLogger::warning('panelze.backup_job_failed', [
                'backup_id' => $backup->id,
                'domain_id' => $backup->domain_id,
                'error' => (string) $result['error'],
            ]);

            return;
        }

        $engineId = isset($result['id']) ? (string) $result['id'] : null;
        // Engine kaydını hemen sakla ki poll timeout olsa bile reconciler bulup sonlandırabilsin.
        $seed = [
            'status' => 'running',
            'file_path' => $result['path'] ?? null,
            'snapshot_path' => $result['snapshot_path'] ?? null,
            'engine_backup_id' => $engineId,
        ];
        if (isset($result['level'])) {
            $seed['level'] = (int) $result['level'];
        }
        $backup->update($seed);

        if ($engineId === null || $engineId === '') {
            // Engine id dönmediyse durumu sorgulayamayız; reconciler'a bırakmak yerine failed.
            $backup->update(['status' => 'failed']);

            return;
        }

        // Durumu belirli bir süre yokla (poll). Bu sürede tamamlanırsa hemen sonlandır +
        // hedefe senkronla. Daha uzun sürerse "running" bırak; backups:reconcile-running
        // arka planda tamamlar (worker'ı süresiz meşgul etmeyelim).
        $pollSeconds = (int) config('panelze.engine_backup_poll_seconds', 1500);
        if ($pollSeconds < 30) {
            $pollSeconds = 1500;
        }
        $deadline = microtime(true) + $pollSeconds;
        $finalStatus = null;
        $engineRow = null;

        do {
            sleep(5);
            $engineRow = $this->findEngineBackup($engine, $engineId);
            $st = is_array($engineRow) ? (string) ($engineRow['status'] ?? '') : '';
            if ($st === 'completed' || $st === 'failed') {
                $finalStatus = $st;
                break;
            }
        } while (microtime(true) < $deadline);

        if ($finalStatus === null) {
            // Hâlâ çalışıyor: reconciler devralacak.
            return;
        }

        $this->finalizeFromEngineRow($backup->fresh(), $engineRow, $finalStatus);
    }

    /**
     * Engine backups listesinden verilen id'yi bulur.
     *
     * @return array<string, mixed>|null
     */
    private function findEngineBackup(EngineApiService $engine, string $engineId): ?array
    {
        foreach ($engine->listBackups() as $row) {
            if (is_array($row) && (string) ($row['id'] ?? '') === $engineId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Engine kaydına göre panel yedeğini sonlandırır (completed→sync, failed).
     *
     * @param  array<string, mixed>|null  $engineRow
     */
    private function finalizeFromEngineRow(Backup $backup, ?array $engineRow, string $status): void
    {
        if ($status === 'failed') {
            $backup->update(['status' => 'failed']);
            SafeAuditLogger::warning('panelze.backup_job_failed', [
                'backup_id' => $backup->id,
                'domain_id' => $backup->domain_id,
                'error' => (string) ($engineRow['error'] ?? 'engine backup failed'),
            ]);

            return;
        }

        $update = ['status' => 'completed', 'completed_at' => now()];
        if (isset($engineRow['level'])) {
            $engineLevel = (int) $engineRow['level'];
            $update['level'] = $engineLevel;
            if ($engineLevel === 0) {
                $update['parent_backup_id'] = null;
                $update['base_backup_id'] = null;
            }
        }
        if (! empty($engineRow['size_bytes'])) {
            $update['size_mb'] = round(((float) $engineRow['size_bytes']) / 1048576, 4);
        }
        if (! empty($engineRow['path'])) {
            $update['file_path'] = (string) $engineRow['path'];
        }
        if (! empty($engineRow['snapshot_path'])) {
            $update['snapshot_path'] = (string) $engineRow['snapshot_path'];
        }
        $backup->update($update);
        $backup = $backup->fresh();

        if (! $backup->destination_id) {
            return;
        }

        $backup->update(['status' => 'syncing']);
        $sync = app(BackupController::class)->syncToDestination($backup->fresh());
        if (empty($sync['ok'])) {
            SafeAuditLogger::warning('panelze.backup_sync_failed', [
                'backup_id' => $backup->id,
                'error' => (string) ($sync['error'] ?? 'sync failed'),
            ]);
        }

        $backup->update(['status' => 'completed', 'completed_at' => $backup->completed_at ?? now()]);
    }

    public function failed(?Throwable $e): void
    {
        $backup = Backup::query()->find($this->backupId);
        // Engine'e iş verildiyse (engine_backup_id var) ve hâlâ çalışıyor olabilir:
        // yanlışlıkla "failed" yapma; reconciler durumu engine'den doğrulayıp sonlandırır.
        if ($backup
            && ! in_array($backup->status, ['completed', 'failed'], true)
            && empty($backup->engine_backup_id)) {
            $backup->update(['status' => 'failed']);
        }
        SafeAuditLogger::warning('panelze.backup_job_exception', [
            'backup_id' => $this->backupId,
            'error' => $e?->getMessage() ?? 'job failed',
        ]);
    }
}
