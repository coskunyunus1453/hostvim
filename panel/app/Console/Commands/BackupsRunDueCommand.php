<?php

namespace App\Console\Commands;

use App\Jobs\RunBackupJob;
use App\Models\Backup;
use App\Models\BackupSchedule;
use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BackupsRunDueCommand extends Command
{
    protected $signature = 'backups:run-due';

    protected $description = 'Zamanı gelen yedek planlarını çalıştırır (tam + arttırımlı)';

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

            [$type, $level, $parentId, $baseId] = $this->resolvePlan($s, $now);

            $backup = Backup::create([
                'user_id' => $s->user_id,
                'domain_id' => $s->domain_id,
                'destination_id' => $s->destination_id,
                'type' => $type,
                'level' => $level,
                'parent_backup_id' => $parentId,
                'base_backup_id' => $baseId,
                'status' => 'queued',
            ]);
            RunBackupJob::dispatch($backup->id);

            $s->last_run_at = $now;
            $s->next_run_at = $this->nextRun((string) $s->schedule, $now);
            $s->save();
        }

        return self::SUCCESS;
    }

    /**
     * Plan tipine göre tam/arttırımlı kararı verir.
     *
     * @return array{0:string,1:int,2:?int,3:?int} [type, level, parentId, baseId]
     */
    private function resolvePlan(BackupSchedule $s, Carbon $now): array
    {
        if (($s->type ?: 'full') !== 'incremental') {
            return [$s->type ?: 'full', 0, null, null];
        }

        $tip = Backup::query()
            ->where('user_id', $s->user_id)
            ->where('domain_id', $s->domain_id)
            ->where('status', 'completed')
            ->whereNotNull('snapshot_path')
            ->when($s->destination_id !== null, fn ($q) => $q->where('destination_id', $s->destination_id))
            ->when($s->destination_id === null, fn ($q) => $q->whereNull('destination_id'))
            ->orderByDesc('id')
            ->first();

        if (! $tip) {
            // Zincir yok → yeni tam (base) yedek.
            return ['full', 0, null, null];
        }

        // Zinciri başlatan base yedek.
        $base = (int) $tip->level === 0 ? $tip : Backup::query()->find($tip->base_backup_id);
        $fullIntervalDays = max(1, (int) ($s->full_interval_days ?: 7));

        // Base çok eskiyse yeni base al (zincir kısalsın, güvenli restore).
        if (! $base || ! $base->completed_at || $base->completed_at->lt($now->copy()->subDays($fullIntervalDays))) {
            return ['full', 0, null, null];
        }

        return ['incremental', (int) $tip->level + 1, $tip->id, $base->id];
    }

    private function nextRun(string $cron, Carbon $now): Carbon
    {
        $cron = trim($cron);
        if ($cron !== '') {
            try {
                if (CronExpression::isValidExpression($cron)) {
                    return Carbon::instance((new CronExpression($cron))->getNextRunDate($now));
                }
            } catch (\Throwable) {
                // düşer: +1 gün
            }
        }

        return $now->copy()->addDay();
    }
}
