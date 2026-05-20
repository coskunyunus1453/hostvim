<?php

namespace App\Services\Cron;

use Illuminate\Validation\ValidationException;

class CronCommandParser
{
    /**
     * @return array{argv: array<int, string>, working_directory: string|null}
     */
    public function parse(string $command): array
    {
        $cmd = trim($command);
        $workingDirectory = null;

        if (preg_match('#^cd\s+(/[A-Za-z0-9_./-]+)\s+(.+)$#', $cmd, $matches) === 1) {
            $workingDirectory = $matches[1];
            $cmd = trim($matches[2]);
        }

        $argv = $this->argvFromCommand($cmd);

        if ($workingDirectory !== null) {
            $this->assertPathUnderHostingRoot($workingDirectory);
        }

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '/')) {
                $this->assertPathUnderHostingRoot($arg);
            }
        }

        return [
            'argv' => $argv,
            'working_directory' => $workingDirectory,
        ];
    }

    public function assertValid(string $command): void
    {
        $this->parse($command);
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
                'command' => 'Güvenlik nedeniyle shell operatörleri (|, ;, &, >, <, `) kullanılamaz. Çalışma dizini için komut başına "cd /mutlak/yol" yazabilirsiniz.',
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

    private function assertPathUnderHostingRoot(string $path): void
    {
        $normalized = str_replace('\\', '/', $path);
        $allowedRoots = array_values(array_filter([
            rtrim(str_replace('\\', '/', (string) config('hostvim.hosting_web_root', '')), '/'),
            rtrim(str_replace('\\', '/', base_path()), '/'),
        ]));

        foreach ($allowedRoots as $root) {
            if ($root !== '' && ($normalized === $root || str_starts_with($normalized.'/', $root.'/'))) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'command' => 'Komut yalnızca site veya panel dizini altındaki mutlak yollara izin verir.',
        ]);
    }
}
