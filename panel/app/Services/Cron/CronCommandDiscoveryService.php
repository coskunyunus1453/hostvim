<?php

namespace App\Services\Cron;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class CronCommandDiscoveryService
{
    private const MAX_SUGGESTIONS = 48;

    /** @var list<string> */
    private const ARTISAN_SKIP_PREFIXES = [
        'make:', 'schema:', 'model:', 'vendor:', 'package:', 'optimize:', 'cache:',
        'config:', 'route:', 'view:', 'event:', 'key:', 'storage:', 'db:', 'composer:',
        'install:', 'migrate', 'queue:', 'horizon:', 'telescope:', 'sail:', 'stub:',
        'lang:', 'auth:', 'sanctum:', 'livewire:', 'inertia:', 'pest:', 'test', 'tinker',
    ];

    /**
     * @return array{
     *   profile: string,
     *   project_root: string|null,
     *   php_binary: string,
     *   suggestions: list<array{
     *     id: string,
     *     kind: string,
     *     label: string,
     *     description: string,
     *     command: string,
     *     recommended_schedule: string|null,
     *     scheduled: bool
     *   }>
     * }
     */
    public function discover(Domain $domain, User $user): array
    {
        $phpBin = $this->phpBinary();
        $roots = $this->resolveProjectRoots($domain);
        $profile = 'unknown';
        $projectRoot = null;
        $byCommand = [];

        foreach ($roots as $root) {
            if (! $this->pathAllowed($root, $user)) {
                continue;
            }

            if (is_file($root.'/artisan')) {
                $profile = 'laravel';
                $projectRoot = $root;
                foreach ($this->discoverArtisan($root, $phpBin, $user) as $item) {
                    $byCommand[$item['command']] = $item;
                }
                break;
            }

            if (is_file($root.'/spark')) {
                $profile = 'codeigniter';
                $projectRoot = $root;
                foreach ($this->discoverSpark($root, $phpBin, $user) as $item) {
                    $byCommand[$item['command']] = $item;
                }
                break;
            }
        }

        if ($profile === 'unknown') {
            foreach ($roots as $root) {
                if (! $this->pathAllowed($root, $user)) {
                    continue;
                }
                $npm = $this->discoverNpmScripts($root, $user);
                if ($npm !== []) {
                    $profile = 'node';
                    $projectRoot = $root;
                    foreach ($npm as $item) {
                        $byCommand[$item['command']] = $item;
                    }
                    break;
                }
            }
        }

        $suggestions = array_values($byCommand);
        usort($suggestions, static function (array $a, array $b): int {
            if (($a['scheduled'] ?? false) !== ($b['scheduled'] ?? false)) {
                return ($b['scheduled'] ?? false) <=> ($a['scheduled'] ?? false);
            }

            return strcasecmp((string) $a['label'], (string) $b['label']);
        });

        if (count($suggestions) > self::MAX_SUGGESTIONS) {
            $suggestions = array_slice($suggestions, 0, self::MAX_SUGGESTIONS);
        }

        return [
            'profile' => $profile,
            'project_root' => $projectRoot,
            'php_binary' => $phpBin,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * @return list<array{id: string, kind: string, label: string, description: string, command: string, recommended_schedule: string|null, scheduled: bool}>
     */
    private function discoverArtisan(string $root, string $phpBin, User $user): array
    {
        $artisan = $root.'/artisan';
        if (! $this->pathAllowed($artisan, $user)) {
            return [];
        }

        $items = [];
        $scheduledNames = [];

        $scheduleOut = $this->run([$phpBin, $artisan, 'schedule:list'], $root, 20);
        foreach ($this->parseScheduleList($scheduleOut) as $row) {
            $name = $row['command'];
            $scheduledNames[$name] = true;
            $items[] = $this->artisanSuggestion($phpBin, $artisan, $name, $row['description'], $row['schedule'], true);
        }

        $listOut = $this->run([$phpBin, $artisan, 'list', '--format=json'], $root, 45);
        $decoded = json_decode($listOut, true);
        if (is_array($decoded)) {
            foreach ($this->parseArtisanJson($decoded) as $row) {
                $name = $row['name'];
                if (isset($scheduledNames[$name])) {
                    continue;
                }
                if ($this->shouldSkipArtisanCommand($name)) {
                    continue;
                }
                $items[] = $this->artisanSuggestion(
                    $phpBin,
                    $artisan,
                    $name,
                    $row['description'],
                    $this->guessSchedule($name),
                    false,
                );
            }
        }

        $items[] = $this->artisanSuggestion(
            $phpBin,
            $artisan,
            'schedule:run',
            (string) __('cron.discover_schedule_run_desc'),
            '* * * * *',
            false,
            'scheduler',
        );

        return $items;
    }

    /**
     * @return list<array{id: string, kind: string, label: string, description: string, command: string, recommended_schedule: string|null, scheduled: bool}>
     */
    private function discoverSpark(string $root, string $phpBin, User $user): array
    {
        $spark = $root.'/spark';
        if (! $this->pathAllowed($spark, $user)) {
            return [];
        }

        $items = [];
        $out = $this->run([$phpBin, $spark, 'list'], $root, 25);
        foreach ($this->parseSparkList($out) as $row) {
            $cmd = $phpBin.' '.$spark.' '.$row['name'];
            $items[] = [
                'id' => 'spark:'.$row['name'],
                'kind' => 'spark',
                'label' => $row['name'],
                'description' => $row['description'],
                'command' => $cmd,
                'recommended_schedule' => $this->guessSchedule($row['name']),
                'scheduled' => false,
            ];
        }

        return $items;
    }

    /**
     * @return list<array{id: string, kind: string, label: string, description: string, command: string, recommended_schedule: string|null, scheduled: bool}>
     */
    private function discoverNpmScripts(string $root, User $user): array
    {
        $pkgPath = $root.'/package.json';
        if (! is_file($pkgPath) || ! $this->pathAllowed($pkgPath, $user)) {
            return [];
        }

        $raw = @file_get_contents($pkgPath);
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $pkg = json_decode($raw, true);
        if (! is_array($pkg)) {
            return [];
        }

        $scripts = $pkg['scripts'] ?? [];
        if (! is_array($scripts) || $scripts === []) {
            return [];
        }

        $npm = '/usr/bin/npm';
        if (! is_executable($npm)) {
            $npm = 'npm';
        }

        $items = [];
        foreach ($scripts as $name => $script) {
            if (! is_string($name) || $name === '' || ! is_string($script)) {
                continue;
            }
            if (in_array($name, ['dev', 'start', 'build', 'lint', 'test'], true) && ! str_contains($name, ':')) {
                // still include but lower priority — include all
            }
            $items[] = [
                'id' => 'npm:'.$name,
                'kind' => 'npm',
                'label' => 'npm run '.$name,
                'description' => Str::limit($script, 120),
                'command' => 'cd '.$root.' && '.$npm.' run '.$name,
                'recommended_schedule' => $this->guessSchedule($name),
                'scheduled' => false,
            ];
        }

        return $items;
    }

    /**
     * @return array{id: string, kind: string, label: string, description: string, command: string, recommended_schedule: string|null, scheduled: bool}
     */
    private function artisanSuggestion(
        string $phpBin,
        string $artisanPath,
        string $name,
        string $description,
        ?string $schedule,
        bool $scheduled,
        string $kind = 'artisan',
    ): array {
        return [
            'id' => $kind.':'.$name,
            'kind' => $kind,
            'label' => $name,
            'description' => $description,
            'command' => $phpBin.' '.$artisanPath.' '.$name,
            'recommended_schedule' => $schedule,
            'scheduled' => $scheduled,
        ];
    }

    /**
     * @param  array<string, mixed>  $json
     * @return list<array{name: string, description: string}>
     */
    private function parseArtisanJson(array $json): array
    {
        $commands = $json['commands'] ?? $json['definitions'] ?? null;
        if (! is_array($commands)) {
            return [];
        }

        $rows = [];
        foreach ($commands as $key => $def) {
            $name = is_string($key) ? $key : (is_array($def) ? (string) ($def['name'] ?? '') : '');
            if ($name === '') {
                continue;
            }
            $desc = '';
            if (is_array($def)) {
                $desc = (string) ($def['description'] ?? $def['help'] ?? '');
            }
            $rows[] = ['name' => $name, 'description' => $desc];
        }

        return $rows;
    }

    /**
     * @return list<array{command: string, description: string, schedule: string|null}>
     */
    private function parseScheduleList(string $output): array
    {
        $rows = [];
        foreach (preg_split('/\r\n|\n/', $output) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '+') || str_contains($line, '---')) {
                continue;
            }
            // "*/5 * * * *  php artisan orders:sync ...."
            if (preg_match('#^([^\s]+(?:\s+[^\s]+){4})\s+.*?artisan\s+([^\s]+)#', $line, $m) === 1) {
                $rows[] = [
                    'schedule' => trim($m[1]),
                    'command' => trim($m[2]),
                    'description' => (string) __('cron.discover_scheduled_in_app'),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return list<array{name: string, description: string}>
     */
    private function parseSparkList(string $output): array
    {
        $rows = [];
        foreach (preg_split('/\r\n|\n/', $output) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '+') || str_contains($line, '---')) {
                continue;
            }
            if (preg_match('#^([a-z0-9][a-z0-9:\-_/]*)\s{2,}(.+)$#i', $line, $m) === 1) {
                $rows[] = ['name' => trim($m[1]), 'description' => trim($m[2])];
            }
        }

        return $rows;
    }

    private function shouldSkipArtisanCommand(string $name): bool
    {
        if ($name === 'list' || $name === 'help' || $name === 'completion' || str_starts_with($name, '_')) {
            return true;
        }
        foreach (self::ARTISAN_SKIP_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function guessSchedule(string $name): ?string
    {
        $lower = strtolower($name);
        if (str_contains($lower, 'scrape') || str_contains($lower, 'sync') || str_contains($lower, 'fetch') || str_contains($lower, 'import')) {
            return '*/5 * * * *';
        }
        if (str_contains($lower, 'report') || str_contains($lower, 'clean')) {
            return '0 3 * * *';
        }
        if ($name === 'schedule:run') {
            return '* * * * *';
        }

        return '*/15 * * * *';
    }

    /**
     * @return list<string>
     */
    private function resolveProjectRoots(Domain $domain): array
    {
        $roots = [];
        $doc = rtrim(str_replace('\\', '/', (string) $domain->document_root), '/');
        if ($doc !== '') {
            $roots[] = $doc;
            $roots[] = dirname($doc);
            $roots[] = dirname($doc, 2);
        }

        $configured = rtrim(str_replace('\\', '/', (string) config('hostvim.hosting_web_root', '')), '/');
        $name = strtolower(trim((string) $domain->name));
        if ($configured !== '' && $name !== '') {
            $roots[] = $configured.'/'.$name;
            $roots[] = $configured.'/'.$name.'/public_html';
        }

        $unique = [];
        foreach ($roots as $root) {
            $root = rtrim($root, '/');
            if ($root === '' || $root === '.' || isset($unique[$root])) {
                continue;
            }
            if (is_dir($root)) {
                $unique[$root] = true;
            }
        }

        return array_keys($unique);
    }

    private function pathAllowed(string $path, User $user): bool
    {
        return CronAllowedPaths::isAllowed(str_replace('\\', '/', $path), $user);
    }

    private function phpBinary(): string
    {
        $configured = trim((string) config('hostvim.cron.php_binary', ''));
        if ($configured !== '' && is_executable($configured)) {
            return $configured;
        }

        return PHP_BINARY;
    }

    /**
     * @param  list<string>  $command
     */
    private function run(array $command, string $cwd, int $timeoutSeconds): string
    {
        try {
            $process = new Process($command, $cwd, [
                'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
                'HOME' => $cwd,
            ]);
            $process->setTimeout($timeoutSeconds);
            $process->run();

            return trim($process->getOutput()."\n".$process->getErrorOutput());
        } catch (ProcessTimedOutException) {
            return '';
        } catch (\Throwable) {
            return '';
        }
    }
}
