<?php

namespace App\Console\Commands;

use App\Models\CronJob;
use App\Services\Cron\CronJobExecutor;
use App\Services\Cron\CronScheduleHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunDueCronJobsCommand extends Command
{
    protected $signature = 'cron:run-due {--force-id= : Tek bir cron job id (test)}';

    protected $description = 'Run customer cron jobs that are due for the current minute';

    public function handle(CronJobExecutor $executor): int
    {
        $forceId = $this->option('force-id');
        $minuteStart = CronScheduleHelper::currentMinuteStart();

        $query = CronJob::query()
            ->where('status', 'active')
            ->where('is_system', false);

        if ($forceId !== null && $forceId !== '') {
            $query->where('id', (int) $forceId);
        }

        $ran = 0;
        $skipped = 0;

        foreach ($query->cursor() as $job) {
            if ($forceId === null && ! CronScheduleHelper::isDue($job->schedule)) {
                continue;
            }

            if ($forceId === null && $this->alreadyRanThisMinute($job, $minuteStart)) {
                $skipped++;

                continue;
            }

            try {
                $run = $executor->execute($job);
                $ran++;
                $this->line(sprintf(
                    'cron #%d %s (exit %s)',
                    $job->id,
                    $run->status,
                    $run->exit_code === null ? 'n/a' : (string) $run->exit_code
                ));
            } catch (\Throwable $e) {
                Log::warning('cron:run-due failed', [
                    'cron_job_id' => $job->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("cron #{$job->id} failed: ".$e->getMessage());
            }
        }

        $this->info("Due cron run finished: executed={$ran}, skipped_already_ran={$skipped}");

        return self::SUCCESS;
    }

    private function alreadyRanThisMinute(CronJob $job, \DateTimeImmutable $minuteStart): bool
    {
        if ($job->last_run_at === null) {
            return false;
        }

        $last = $job->last_run_at->setTimezone(CronScheduleHelper::timezone());
        $lastMinute = $last->setTime((int) $last->format('H'), (int) $last->format('i'), 0);

        return $lastMinute->getTimestamp() >= $minuteStart->getTimestamp();
    }
}
