<?php

namespace App\Console\Commands;

use App\Jobs\RunBackupJob;
use App\Models\Backup;
use App\Models\BackupSchedule;
use Illuminate\Console\Command;

class BackupsRunDueCommand extends Command
{
    protected $signature = 'backups:run-due';

    protected $description = 'Run due backup schedules';

    public function handle(): int
    {
        $now = now();
        $rows = BackupSchedule::query()
            ->where('enabled', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', $now);
            })
            ->with(['domain', 'user'])
            ->limit(50)
            ->get();

        foreach ($rows as $s) {
            $domain = $s->domain;
            if (! $domain || ! $s->user) {
                continue;
            }
            $backup = Backup::create([
                'user_id' => $s->user_id,
                'domain_id' => $s->domain_id,
                'destination_id' => $s->destination_id,
                'type' => $s->type ?: 'full',
                'status' => 'queued',
            ]);
            RunBackupJob::dispatch($backup->id);
            $s->last_run_at = $now;
            $s->next_run_at = $now->copy()->addDay();
            $s->save();
        }

        return self::SUCCESS;
    }
}
