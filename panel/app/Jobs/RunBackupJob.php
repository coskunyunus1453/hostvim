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
        $backup = Backup::query()->with(['domain', 'destination'])->find($this->backupId);
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

        $result = $engine->queueBackupLong($backup->domain->name, $backup->type, $backup->id, $timeout);

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
        $engineStatus = is_string($result['status'] ?? null) ? (string) $result['status'] : '';
        $panelStatus = $engineStatus === 'completed' || $engineStatus === 'failed' ? $engineStatus : 'running';
        $update = [
            'status' => $panelStatus,
            'file_path' => $result['path'] ?? null,
            'engine_backup_id' => $engineId,
        ];
        if (! empty($result['size_bytes'])) {
            $update['size_mb'] = round(((float) $result['size_bytes']) / 1048576, 4);
        }
        if ($panelStatus === 'completed') {
            $update['completed_at'] = now();
        }
        $backup->update($update);
        $backup = $backup->fresh();

        if ($panelStatus !== 'completed' || ! $backup->destination_id) {
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
        if ($backup && ! in_array($backup->status, ['completed', 'failed'], true)) {
            $backup->update(['status' => 'failed']);
        }
        SafeAuditLogger::warning('panelze.backup_job_exception', [
            'backup_id' => $this->backupId,
            'error' => $e?->getMessage() ?? 'job failed',
        ]);
    }
}
