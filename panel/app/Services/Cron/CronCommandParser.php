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
     * @return array{argv: array<int, string>, working_directory: string|null}
     */
    public function parse(string $command, ?User $user = null): array
    {
        $normalized = $this->normalizeInput($command);
        $cmd = $normalized['command'];
        $workingDirectory = null;

        if (preg_match('#^cd\s+(/[A-Za-z0-9_./-]+)\s+(.+)$#', $cmd, $matches) === 1) {
            $workingDirectory = $matches[1];
            $cmd = trim($matches[2]);
        }

        $argv = $this->argvFromCommand($cmd);

        if ($workingDirectory !== null) {
            $this->assertPathAllowed($workingDirectory, $user);
        }

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '/')) {
                $this->assertPathAllowed($arg, $user);
            }
        }

        return [
            'argv' => $argv,
            'working_directory' => $workingDirectory,
        ];
    }

    public function assertValid(string $command, ?User $user = null): void
    {
        $normalized = $this->normalizeInput($command);
        if ($normalized['stripped_schedule'] !== null) {
            throw ValidationException::withMessages([
                'command' => 'Zamanlama komut alanına değil, üstteki "Cron" / zamanlama alanına yazılmalı (örn. 0 * * * *). Komut kutusuna yalnızca çalıştırılacak kısmı girin.',
            ]);
        }

        $this->parse($command, $user);
    }

    /**
     * @return array<int, string>
     */
    private function argvFromCommand(string $cmd): array
    {
        if ($cmd === '') {
            throw ValidationException::withMessages([
                'command' => 'Komut boş olamaz.',
            ]);
        }

        if (preg_match('/[;&|`><\n\r]/', $cmd) === 1) {
            throw ValidationException::withMessages([
                'command' => 'Güvenlik nedeniyle shell operatörleri (|, ;, &, >, <, `) kullanılamaz. Örnek: cd /site/public_html/public /usr/bin/php /site/public_html/spark görev:adı',
            ]);
        }

        $parts = str_getcsv($cmd, ' ', '"', '\\');
        $argv = array_values(array_filter(
            array_map(static fn ($v) => trim((string) $v), $parts),
            static fn ($v) => $v !== ''
        ));

        if ($argv === []) {
            throw ValidationException::withMessages([
                'command' => 'Komut çözümlenemedi.',
            ]);
        }

        $binary = $argv[0];
        if (! preg_match('/^[A-Za-z0-9_\/.\-]+$/', $binary)) {
            throw ValidationException::withMessages([
                'command' => 'Komut adı geçersiz karakter içeriyor.',
            ]);
        }

        foreach ($argv as $arg) {
            if (preg_match('/[\x00]/', $arg) === 1) {
                throw ValidationException::withMessages([
                    'command' => 'Komut argümanlarında geçersiz karakter var.',
                ]);
            }
        }

        return $argv;
    }

    private function assertPathAllowed(string $path, ?User $user): void
    {
        if (CronAllowedPaths::isAllowed($path, $user)) {
            return;
        }

        $roots = CronAllowedPaths::rootsFor($user);
        $hint = $roots !== [] ? ' İzin verilen örnek kök: '.Str::limit($roots[0], 80) : '';

        throw ValidationException::withMessages([
            'command' => 'Komut yolu bu hesabın site dizinleri veya panel kökü altında olmalı.'.$hint,
        ]);
    }
}
