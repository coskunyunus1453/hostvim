<?php

namespace App\Services;

use App\Models\StackInstallRun;
use Symfony\Component\Process\Process;

class StackBundleInstaller
{
    private const MAX_OUTPUT = 65535;

    /**
     * @return array{ok: bool, output: string, error?: string}
     */
    public function install(StackInstallRun $run, string $bundleId): array
    {
        $script = $this->resolveScriptPath();
        if ($script === null) {
            return ['ok' => false, 'output' => '', 'error' => 'stack-install betiği bulunamadı (/usr/local/sbin/panelze-stack-install)'];
        }

        $process = new Process(['sudo', '-n', $script, $bundleId]);
        $process->setTimeout((int) config('panelze.stack_install_timeout', 1800));
        $output = '';

        try {
            $process->run(function (string $type, string $buffer) use ($run, $bundleId, &$output, $process): void {
                $output .= $buffer;
                $fresh = StackInstallRun::query()->find($run->id);
                if (! $fresh) {
                    $process->stop(0);

                    return;
                }
                if ($fresh->cancel_requested) {
                    $process->stop(0);

                    return;
                }

                $fresh->update([
                    'message' => $this->stepMessage($bundleId, $output),
                    'progress' => $this->estimateProgress($bundleId, $output, $fresh->started_at),
                    'output' => mb_substr(trim($output), -self::MAX_OUTPUT),
                ]);
            });
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'output' => mb_substr(trim($output), -self::MAX_OUTPUT),
                'error' => $e->getMessage(),
            ];
        }

        $run->refresh();
        if ($run->cancel_requested) {
            return ['ok' => false, 'output' => mb_substr(trim($output), -self::MAX_OUTPUT), 'error' => 'Kurulum iptal edildi.'];
        }

        if (! $process->isSuccessful()) {
            $err = trim($process->getErrorOutput()."\n".$process->getOutput());
            return [
                'ok' => false,
                'output' => mb_substr($err !== '' ? $err : trim($output), -self::MAX_OUTPUT),
                'error' => $err !== '' ? mb_substr($err, 0, 500) : ('Çıkış kodu: '.$process->getExitCode()),
            ];
        }

        return ['ok' => true, 'output' => mb_substr(trim($output), -self::MAX_OUTPUT)];
    }

    private function resolveScriptPath(): ?string
    {
        $configured = trim((string) config('panelze.stack_install_script', ''));
        $candidates = array_filter([
            $configured !== '' ? $configured : null,
            '/usr/local/sbin/panelze-stack-install',
            '/usr/local/sbin/panelze-stack-install',
            '/usr/local/sbin/panelsar-stack-install',
        ]);

        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function stepMessage(string $bundleId, string $output): string
    {
        if (preg_match_all('/^==>\s*(.+)$/m', $output, $m) && ! empty($m[1])) {
            $last = trim((string) end($m[1]));

            return 'Kurulum sürüyor: '.$last;
        }

        if (str_contains($output, 'OK '.$bundleId)) {
            return 'Kurulum tamamlanıyor...';
        }

        return 'Kurulum sürüyor...';
    }

    private function estimateProgress(string $bundleId, string $output, ?\Illuminate\Support\Carbon $startedAt): int
    {
        if (str_contains($output, 'OK '.$bundleId)) {
            return 99;
        }

        if ($bundleId === 'mail-stack-webmail') {
            $steps = [
                'vmail' => 12,
                'Paketler' => 22,
                'Postfix' => 38,
                'Dovecot' => 52,
                'OpenDKIM' => 66,
                'Roundcube' => 76,
                'Nginx' => 86,
                'Servisler' => 94,
                'Mail provision' => 97,
            ];
            $progress = 8;
            foreach ($steps as $needle => $pct) {
                if (stripos($output, $needle) !== false) {
                    $progress = max($progress, $pct);
                }
            }

            return min(98, $progress);
        }

        if ($startedAt !== null) {
            $elapsed = max(0, now()->diffInSeconds($startedAt));
            $expected = max(60, (int) config('panelze.stack_install_expected_seconds', 180));

            return min(95, 10 + (int) round(($elapsed / $expected) * 85));
        }

        return 15;
    }
}
