<?php

namespace App\Services\Cron;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CronCommandParser
{
    /**
     * Komut alanına yanlışlıkla yapıştırılan "0 * * * * ..." önekini ayırır.
     *
     * @return array{command: string, stripped_schedule: string|null}
     */
    public function normalizeInput(string $command): array
    {
        $cmd = trim($command);
        if (preg_match('#^(\S+\s+\S+\s+\S+\s+\S+\s+\S+)\s+(.+)$#s', $cmd, $m) !== 1) {
            return ['command' => $cmd, 'stripped_schedule' => null];
        }

        $schedule = trim($m[1]);
        $rest = trim($m[2]);
        $parts = preg_split('/\s+/', $schedule, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($parts) || count($parts) !== 5 || ! $this->looksLikeCronSchedulePrefix($parts)) {
            return ['command' => $cmd, 'stripped_schedule' => null];
        }

        return ['command' => $rest, 'stripped_schedule' => $schedule];
    }

    /**
     * @param  list<string>  $parts
     */
    private function looksLikeCronSchedulePrefix(array $parts): bool
    {
        $shellStarts = ['cd', 'php', 'bash', 'sh', 'wget', 'curl', 'env', 'export', 'sudo', 'nice', 'nohup'];
        $first = strtolower($parts[0]);
        if (in_array($first, $shellStarts, true)) {
            return false;
        }

        foreach ($parts as $part) {
            if (str_contains($part, '/')) {
                return false;
            }
            if (! $this->looksLikeCronField($part)) {
                return false;
            }
        }

        return true;
    }

    private function looksLikeCronField(string $field): bool
    {
        if ($field === '') {
            return false;
        }

        if (preg_match('/^@(annually|yearly|monthly|weekly|daily|hourly|reboot)$/i', $field) === 1) {
            return true;
        }

        return preg_match('/^[\d*\/,\-A-Za-z#LW?]+$/', $field) === 1;
    }

    /**
     * @return array{shell: true, command: string, working_directory: string|null, argv: array<int, string>}
     */
    public function parse(string $command, ?User $user = null, ?Domain $domain = null): array
    {
        $normalized = $this->normalizeInput($command);
        $cmd = trim($normalized['command']);

        $this->assertShellCommandSafe($cmd, $user, $domain);

        $workingDirectory = null;
        if (preg_match('~^cd\s+(/[^\s;|&><]+)\s+&&\s*(.+)$~', $cmd, $matches) === 1) {
            $workingDirectory = rtrim($matches[1], '/');
            $this->assertPathAllowed($workingDirectory, $user, $domain);
            $cmd = trim($matches[2]);
        } elseif (preg_match('~^cd\s+(/[^\s;|&><]+)\s+(.+)$~', $cmd, $matches) === 1) {
            $workingDirectory = rtrim($matches[1], '/');
            $this->assertPathAllowed($workingDirectory, $user, $domain);
        }

        return [
            'shell' => true,
            'command' => $cmd,
            'working_directory' => $workingDirectory,
            'argv' => [],
        ];
    }

    public function assertValid(string $command, ?User $user = null, ?Domain $domain = null): void
    {
        $normalized = $this->normalizeInput($command);
        if ($normalized['stripped_schedule'] !== null) {
            throw ValidationException::withMessages([
                'command' => __('cron.schedule_in_command_field'),
            ]);
        }

        $this->parse($command, $user, $domain);
    }

    private function assertShellCommandSafe(string $cmd, ?User $user, ?Domain $domain = null): void
    {
        if ($cmd === '') {
            throw ValidationException::withMessages([
                'command' => __('cron.command_empty'),
            ]);
        }

        if (mb_strlen($cmd) > 2000) {
            throw ValidationException::withMessages([
                'command' => __('cron.command_too_long'),
            ]);
        }

        if (preg_match('/[\x00\r\n]/', $cmd) === 1) {
            throw ValidationException::withMessages([
                'command' => __('cron.command_no_multiline'),
            ]);
        }

        if (preg_match('/`|\$\(|(\$\{)/', $cmd) === 1) {
            throw ValidationException::withMessages([
                'command' => __('cron.command_no_substitution'),
            ]);
        }

        foreach ($this->forbiddenPatterns() as $pattern) {
            if (preg_match($pattern, $cmd) === 1) {
                throw ValidationException::withMessages([
                    'command' => __('cron.command_forbidden_pattern'),
                ]);
            }
        }

        $this->assertPathsInCommandAllowed($cmd, $user, $domain);
    }

    /**
     * @return list<string>
     */
    private function forbiddenPatterns(): array
    {
        return [
            '/\|\s*(?:ba)?sh\b/i',
            '/;\s*(?:\/usr)?\/bin\/(?:ba)?sh\b/i',
            '/\b(?:curl|wget)\b[^\n|&;]*\|\s*(?:ba)?sh\b/i',
            '/\brm\s+-rf\s+\/(?:\s|$)/i',
            // /dev/tcp|udp — kabuk ağı tüneli; /dev/null ve /dev/zero cron'da yaygın ve güvenli
            '/\/dev\/(?:tcp|udp)\b/i',
        ];
    }

    private function assertPathsInCommandAllowed(string $cmd, ?User $user, ?Domain $domain = null): void
    {
        $scan = preg_replace('/#.*$/', '', $cmd) ?? $cmd;

        if (preg_match_all('#(/[A-Za-z0-9][A-Za-z0-9_./-]*)#', $scan, $matches) !== false) {
            foreach ($matches[1] as $path) {
                $path = rtrim($path, '/');
                if ($path === '' || $path === '/') {
                    continue;
                }
                if (preg_match('#^/\d+$#', $path) === 1) {
                    continue;
                }
                if (CronAllowedPaths::isSafeDevSink($path)) {
                    continue;
                }
                if (CronAllowedPaths::isPhpBinaryPath($path)) {
                    continue;
                }
                $this->assertPathAllowed($path, $user, $domain);
            }
        }
    }

    private function assertPathAllowed(string $path, ?User $user, ?Domain $domain = null): void
    {
        if (CronAllowedPaths::isAllowed($path, $user, $domain)) {
            return;
        }

        $roots = CronAllowedPaths::rootsFor($user, $domain);
        $hint = $roots !== []
            ? ' '.__('cron.command_path_hint', ['path' => Str::limit($roots[0], 80)])
            : '';

        throw ValidationException::withMessages([
            'command' => __('cron.command_path_not_allowed', ['hint' => $hint]),
        ]);
    }
}
