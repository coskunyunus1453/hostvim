<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\BackupController;
use App\Models\Backup;
use App\Services\EngineApiService;
use App\Services\SafeAuditLogger;
use Illuminate\Console\Command;

/**
 * Asenkron yedeklerde "running/syncing" kalan (worker poll'u dolmuş, worker/engine
 * yeniden başlamış) ve engine tarafında aslında tamamlanmış/başarısız olan yedekleri
 * engine durumundan doğrulayıp sonlandırır. Ayrıca engine "completed" derken panelde
 * yanlışlıkla "failed" görünen kayıtları da düzeltir (senkron dönemden kalma tutarsızlık).
 */
class BackupsReconcileRunningCommand extends Command
{
    protected $signature = 'backups:reconcile-running {--stale-hours=6 : Engine kaydı olmayan bu kadar saatten eski running/failed kayıtları başarısız say}';

    protected $description = 'Engine ile senkronize et: running/syncing kalan veya yanlış failed yedekleri sonlandırır';

    public function handle(EngineApiService $engine, BackupController $ctrl): int
    {
        $rows = $engine->listBackups();
        $byId = [];
        foreach ($rows as $r) {
            if (is_array($r) && isset($r['id'])) {
                $byId[(string) $r['id']] = $r;
            }
        }

        $staleHours = max(1, (int) $this->option('stale-hours'));

        // running/syncing: her zaman; failed: sadece engine_backup_id varsa (engine'de tamamlanmış olabilir).
        $candidates = Backup::query()
            ->whereIn('status', ['running', 'syncing'])
            ->orWhere(function ($q) {
                $q->where('status', 'failed')->whereNotNull('engine_backup_id');
            })
            ->orderBy('id')
            ->limit(200)
            ->get();

        $finalized = 0;
        foreach ($candidates as $b) {
            $eid = trim((string) $b->engine_backup_id);
            $engineRow = $eid !== '' ? ($byId[$eid] ?? null) : null;
            $engineStatus = is_array($engineRow) ? (string) ($engineRow['status'] ?? '') : '';

            if ($engineStatus === 'completed') {
                $this->finalize($ctrl, $b, $engineRow, true);
                $finalized++;

                continue;
            }
            if ($engineStatus === 'failed') {
                if ($b->status !== 'failed') {
                    $b->update(['status' => 'failed']);
                    $finalized++;
                }

                continue;
            }

            // Engine kaydı yok/hâlâ running. Yeni failed kayıtlara dokunma (zaten failed).
            if ($b->status === 'failed') {
                continue;
            }
            // running/syncing ama engine'de iz yok ve kayıt eski → engine muhtemelen yeniden
            // başladı ve arşiv goroutine'i kayboldu; başarısız say.
            if ($engineRow === null && $b->updated_at && $b->updated_at->lt(now()->subHours($staleHours))) {
                $b->update(['status' => 'failed']);
                SafeAuditLogger::warning('panelze.backup_reconcile_stale_failed', [
                    'backup_id' => $b->id,
                    'engine_backup_id' => $eid,
                ]);
                $finalized++;
            }
        }

        $this->info($finalized.' yedek sonlandırıldı/senkronlandı.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $engineRow
     */
    private function finalize(BackupController $ctrl, Backup $b, array $engineRow, bool $completed): void
    {
        $update = ['status' => 'completed', 'completed_at' => $b->completed_at ?? now()];
        if (isset($engineRow['level'])) {
            $lvl = (int) $engineRow['level'];
            $update['level'] = $lvl;
            if ($lvl === 0) {
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
        $b->update($update);
        $b = $b->fresh();

        // Hedefe henüz yüklenmemişse senkronla.
        if ($b->destination_id && ! $b->remote_path && ! $b->remote_file_id) {
            $b->update(['status' => 'syncing']);
            $sync = $ctrl->syncToDestination($b->fresh());
            if (empty($sync['ok'])) {
                SafeAuditLogger::warning('panelze.backup_sync_failed', [
                    'backup_id' => $b->id,
                    'error' => (string) ($sync['error'] ?? 'sync failed'),
                ]);
            }
            $b->update(['status' => 'completed', 'completed_at' => $b->completed_at ?? now()]);
        }
    }
}
