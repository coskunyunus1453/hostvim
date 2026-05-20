<?php

namespace App\Jobs;

use App\Models\Database;
use App\Models\DatabaseImportRun;
use App\Services\DatabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3700;

    public function __construct(
        private readonly int $runId,
    ) {}

    public function handle(DatabaseService $databaseService): void
    {
        $run = DatabaseImportRun::query()->find($this->runId);
        if (! $run) {
            return;
        }

        $database = Database::query()->find($run->database_id);
        if (! $database) {
            $this->failRun($run, __('databases.import_failed'));

            return;
        }

        $relative = (string) $run->file_path;
        if ($relative === '' || ! Storage::disk('local')->exists($relative)) {
            $this->failRun($run, __('databases.import_file_unreadable'));

            return;
        }

        $absolute = Storage::disk('local')->path($relative);

        $run->status = 'running';
        $run->started_at = now();
        $run->touchProgress(8, 'validating', __('databases.import_phase_validating'));

        try {
            if ($database->type === 'mysql') {
                $databaseService->importMysqlFromSqlFile(
                    $database,
                    $absolute,
                    function (int $progress, string $phase, ?string $message = null) use ($run): void {
                        $run->touchProgress($progress, $phase, $message);
                    },
                );
            } else {
                $databaseService->importPostgresFromSqlFile(
                    $database,
                    $absolute,
                    function (int $progress, string $phase, ?string $message = null) use ($run): void {
                        $run->touchProgress($progress, $phase, $message);
                    },
                );
            }
        } catch (Throwable $e) {
            report($e);
            $this->failRun($run, $e->getMessage() ?: __('databases.import_failed'));

            return;
        } finally {
            Storage::disk('local')->delete($relative);
        }

        $run->status = 'completed';
        $run->touchProgress(100, 'completed', __('databases.imported'));
        $run->finished_at = now();
        $run->save();
    }

    private function failRun(DatabaseImportRun $run, string $message): void
    {
        $run->status = 'failed';
        $run->error_message = $message;
        $run->message = $message;
        $run->progress = min((int) $run->progress, 99);
        $run->phase = 'failed';
        $run->finished_at = now();
        $run->save();
    }
}
