<?php

namespace App\Services\Cron;

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
        if (preg_match('#^(\S+\s+\S+\s+\S+\s+\S+\s+\S+)\s+(.+)$#s', $cmd, $m) === 1) {
            $schedule = trim($m[1]);
            $rest = trim($m[2]);
            $parts = preg_split('/\s+/', $schedule, -1, PREG_SPLIT_NO_EMPTY);
            if (is_array($parts) && count($parts) === 5) {
                return ['command' => $rest, 'stripped_schedule' => $schedule];
            }
        }

        return ['command' => $cmd, 'stripped_schedule' => null];
    }

    /**
     * @return array{shell: true, command: string, working_directory: string|null, argv: array<int, string>}
     */
    public function parse(string $command, ?User $user = null): array
    {
        $normalized = $this->normalizeInput($command);
        $cmd = trim($normalized['command']);

        $this->assertShellCommandSafe($cmd, $user);

        $workingDirectory = null;
        if (preg_match('#^cd\s+(/[^\s#;&|><]+)\s+(.+)$#', $cmd, $matches) === 1) {
            $workingDirectory = rtrim($matches[1], '/');
            $this->assertPathAllowed($workingDirectory, $user);
        }

        return [
            'shell' => true,
            'command' => $cmd,
            'working_directory' => $workingDirectory,
            'argv' => [],
        ];
    }

    public function assertValid(string $command, ?User $user = null): void
    {
        $normalized = $this->normalizeInput($command);
        if ($normalized['stripped_schedule'] !== null) {
            throw ValidationException::withMessages([
                'command' => __('cron.schedule_in_command_field'),
            ]);
        }

        $this->parse($command, $user);
    }

    private function assertShellCommandSafe(string $cmd, ?User $user): void
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

        $this->assertPathsInCommandAllowed($cmd, $user);
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
            '/>\s*\/dev\/(?:null|zero|tcp|udp)\b/i',
        ];
    }

    private function assertPathsInCommandAllowed(string $cmd, ?User $user): void
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
                $this->assertPathAllowed($path, $user);
            }
        }
    }

    private function assertPathAllowed(string $path, ?User $user): void
    {
        if (CronAllowedPaths::isAllowed($path, $user)) {
            return;
        }

        $roots = CronAllowedPaths::rootsFor($user);
        $hint = $roots !== [] ? ' '.Str::limit($roots[0], 80) : '';

        throw ValidationException::withMessages([
            'command' => __('cron.command_path_not_allowed', ['hint' => $hint]),
        ]);
    }
}
