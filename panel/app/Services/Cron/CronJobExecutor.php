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
            return CronJobRun::create([
                'cron_job_id' => $job->id,
                'user_id' => $triggerUserId ?? $job->user_id,
                'status' => 'failed',
                'exit_code' => null,
                'output' => 'Görev zaten çalışıyor (önceki çalıştırma bitmedi).',
                'started_at' => now(),
                'finished_at' => now(),
            ]);
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
        $shellCommand = trim((string) $parsed['command']);
        $cwd = $this->resolveWorkingDirectory(
            $shellCommand,
            $parsed['working_directory'] ?? null,
            (int) $job->user_id,
        );

        $run = CronJobRun::create([
            'cron_job_id' => $job->id,
            'user_id' => $triggerUserId ?? $job->user_id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $process = $this->createProcess($shellCommand, $cwd);
        $process->setTimeout((int) config('hostvim.cron.timeout', 180));
        $idleTimeout = (int) config('hostvim.cron.idle_timeout', 0);
        if ($idleTimeout > 0) {
            $process->setIdleTimeout($idleTimeout);
        }

        try {
            $process->mustRun();
            $run->update([
                'status' => 'success',
                'exit_code' => $process->getExitCode(),
                'output' => $this->formatRunOutput($shellCommand, $this->trimOutput($process)),
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

    private function resolveWorkingDirectory(string $shellCommand, ?string $fromParser, int $userId): ?string
    {
        if ($fromParser !== null && $fromParser !== '' && is_dir($fromParser)) {
            return $fromParser;
        }

        if ($this->hasAbsoluteScriptPath($shellCommand)) {
            return null;
        }

        return $this->inferWorkingDirectory($userId, $shellCommand);
    }

    private function hasAbsoluteScriptPath(string $cmd): bool
    {
        return preg_match('#(?:^|\s)(/[^\s;|&"\'<>]+/(?:artisan|spark))(?:\s|$)#', $cmd) === 1;
    }

    private function createProcess(string $shellCommand, ?string $cwd): Process
    {
        $env = $this->processEnvironment($cwd);

        if ($this->requiresShell($shellCommand)) {
            return Process::fromShellCommandline($shellCommand, $cwd, $env);
        }

        $argv = $this->toArgv($shellCommand);
        if ($argv !== null) {
            return new Process($argv, $cwd, $env);
        }

        return Process::fromShellCommandline($shellCommand, $cwd, $env);
    }

    private function requiresShell(string $cmd): bool
    {
        return preg_match('#[|;&`$]|\$\(|&&|\|\||>>|(?<![>])>(?![>])#', $cmd) === 1;
    }

    /**
     * @return list<string>|null
     */
    private function toArgv(string $cmd): ?array
    {
        if (preg_match('#^(/usr/bin/php\d*|/usr/local/bin/php\d*|php)\s+(/[^\s;|&]+)(?:\s+(.*))?$#', $cmd, $m) !== 1) {
            return null;
        }

        $argv = [$m[1], $m[2]];
        $rest = trim((string) ($m[3] ?? ''));
        if ($rest !== '') {
            $argv = array_merge($argv, preg_split('/\s+/', $rest, -1, PREG_SPLIT_NO_EMPTY) ?: []);
        }

        return $argv;
    }

    private function inferWorkingDirectory(int $userId, string $shellCommand): ?string
    {
        if (preg_match_all('#(/[A-Za-z0-9][A-Za-z0-9_./-]*(?:artisan|spark))(?:\s|$)#', $shellCommand, $matches) !== false) {
            foreach ($matches[1] as $script) {
                if (is_file($script)) {
                    return dirname($script);
                }
            }
        }

        return $this->primaryDocumentRootForUser($userId);
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
        $env = [
            'PATH' => $path,
            'LANG' => 'C.UTF-8',
            'LC_ALL' => 'C.UTF-8',
        ];
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

    private function formatRunOutput(string $shellCommand, string $output): string
    {
        $out = trim($output);
        if ($out !== '') {
            return $out;
        }

        if (preg_match('#>>\s*/dev/null|2>&1\s*/dev/null#', $shellCommand) === 1) {
            return __('cron.output_discarded_dev_null');
        }

        if (preg_match('#\bartisan\s+schedule:run\b#', $shellCommand) === 1) {
            return __('cron.output_empty_schedule_run');
        }

        return $out;
    }
}
