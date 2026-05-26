<?php

namespace App\Services\Cron;

use App\Models\CronJob;
use App\Models\CronJobRun;
use App\Models\Domain;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class CronJobExecutor
{
    public function __construct(
        private CronCommandParser $parser,
    ) {}

    public function execute(CronJob $job, ?int $triggerUserId = null): CronJobRun
    {
        $lock = Cache::lock('cron_job_run:'.$job->id, (int) config('hostvim.cron.lock_seconds', 600));

        if (! $lock->get()) {
            $run = CronJobRun::create([
                'cron_job_id' => $job->id,
                'user_id' => $triggerUserId ?? $job->user_id,
                'status' => 'failed',
                'exit_code' => null,
                'output' => 'Görev zaten çalışıyor (önceki çalıştırma bitmedi).',
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            return $run;
        }

        try {
            return $this->runProcess($job, $triggerUserId);
        } finally {
            $lock->release();
        }
    }

    private function runProcess(CronJob $job, ?int $triggerUserId): CronJobRun
    {
        $job->loadMissing('user');
        $parsed = $this->parser->parse($job->command, $job->user);
        $shellCommand = (string) $parsed['command'];
        $cwd = $parsed['working_directory'] ?? $this->inferWorkingDirectory($job, $shellCommand);

        $run = CronJobRun::create([
            'cron_job_id' => $job->id,
            'user_id' => $triggerUserId ?? $job->user_id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $process = Process::fromShellCommandline($shellCommand, $cwd, $this->processEnvironment($cwd));
        $process->setTimeout((int) config('hostvim.cron.timeout', 180));
        $process->setIdleTimeout((int) config('hostvim.cron.idle_timeout', 120));

        try {
            $process->mustRun();
            $run->update([
                'status' => 'success',
                'exit_code' => $process->getExitCode(),
                'output' => $this->trimOutput($process),
                'finished_at' => now(),
            ]);
        } catch (ProcessTimedOutException $e) {
            $run->update([
                'status' => 'timeout',
                'exit_code' => $process->getExitCode(),
                'output' => $this->trimOutput($process, $e->getMessage()),
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'exit_code' => $process->getExitCode(),
                'output' => $this->trimOutput($process, $e->getMessage()),
                'finished_at' => now(),
            ]);
        }

        $job->update([
            'last_run_at' => now(),
            'next_run_at' => CronScheduleHelper::nextRunAt($job->schedule),
        ]);

        return $run->fresh();
    }

    private function inferWorkingDirectory(CronJob $job, string $shellCommand): ?string
    {
        if (preg_match('#^cd\s+(/[^\s#;&|><]+)#', $shellCommand, $m) === 1) {
            $dir = rtrim($m[1], '/');

            return is_dir($dir) ? $dir : null;
        }

        if (preg_match_all('#(/[A-Za-z0-9][A-Za-z0-9_./-]*(?:artisan|spark|\.php))#', $shellCommand, $matches) !== false) {
            foreach ($matches[1] as $script) {
                if (is_file($script)) {
                    return dirname($script);
                }
            }
        }

        return $this->primaryDocumentRootForUser((int) $job->user_id);
    }

    private function primaryDocumentRootForUser(int $userId): ?string
    {
        $domain = Domain::query()
            ->where('user_id', $userId)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first(['document_root']);

        $root = $domain?->document_root;
        if (! is_string($root) || trim($root) === '') {
            return null;
        }

        $path = rtrim(str_replace('\\', '/', $root), '/');

        return is_dir($path) ? $path : null;
    }

    /**
     * @return array<string, string>|null
     */
    private function processEnvironment(?string $cwd): ?array
    {
        $path = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
        $env = ['PATH' => $path];
        if ($cwd !== null && $cwd !== '') {
            $env['HOME'] = $cwd;
        }

        return $env;
    }

    private function trimOutput(Process $process, ?string $extra = null): string
    {
        $out = trim(($process->getOutput() ?? '')."\n".($process->getErrorOutput() ?? ''));
        if ($extra !== null && $extra !== '') {
            $out = trim($out."\n".$extra);
        }

        return mb_substr($out, 0, 65535);
    }
}
