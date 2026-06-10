<?php

namespace App\Services\Cron;

use App\Models\CronJob;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class CronCommandDiscoveryService
{
    private const MAX_SUGGESTIONS = 64;

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
     *   domain: string,
     *   scan_steps: list<array{key: string, label: string, status: string}>,
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
    public function discover(Domain $domain, User $user, bool $deep = false): array
    {
        $phpBin = $this->phpBinary();
        $steps = [];
        $byCommand = [];
        $profile = 'unknown';
        $projectRoot = null;

        $addStep = function (string $key, string $label, string $status = 'done') use (&$steps): void {
            $steps[] = ['key' => $key, 'label' => $label, 'status' => $status];
        };

        $addStep('start', (string) __('cron.discover_step_start'), 'running');
        $roots = $this->resolveProjectRoots($domain);
        $addStep('roots', (string) __('cron.discover_step_roots', ['count' => count($roots)]));

        $owner = $domain->user ?? User::query()->find($domain->user_id);
        $pathUser = $owner ?? $user;

        foreach ($this->discoverExistingPanelCrons($domain) as $item) {
            $byCommand[$item['command']] = $item;
        }
        $addStep('existing', (string) __('cron.discover_step_existing', ['count' => count($byCommand)]));

        foreach ($roots as $root) {
            if (! CronAllowedPaths::isAllowed($root, $pathUser, $domain)) {
                continue;
            }

            foreach ($this->locateArtisanRoots($root) as $artisanRoot) {
                if (! is_file($artisanRoot.'/artisan')) {
                    continue;
                }
                $profile = 'laravel';
                $projectRoot ??= $artisanRoot;
                $addStep('artisan', (string) __('cron.discover_step_artisan', ['path' => $artisanRoot]), 'running');
                foreach ($this->discoverArtisan($artisanRoot, $phpBin, $pathUser, $domain, $deep) as $item) {
                    $byCommand[$item['command']] = $item;
                }
                $addStep('artisan', (string) __('cron.discover_step_artisan_done'));
            }

            foreach ($this->locateSparkRoots($root) as $sparkRoot) {
                if (! is_file($sparkRoot.'/spark')) {
                    continue;
                }
                $profile = $profile === 'laravel' ? 'laravel' : 'codeigniter';
                $projectRoot ??= $sparkRoot;
                $addStep('spark', (string) __('cron.discover_step_spark', ['path' => $sparkRoot]), 'running');
                foreach ($this->discoverSpark($sparkRoot, $phpBin, $pathUser, $domain) as $item) {
                    $byCommand[$item['command']] = $item;
                }
                $addStep('spark', (string) __('cron.discover_step_spark_done'));
            }

            if ($profile === 'unknown') {
                foreach ($this->locateNpmRoots($root) as $npmRoot) {
                    $pkg = $npmRoot.'/package.json';
                    if (! is_file($pkg)) {
                        continue;
                    }
                    $profile = 'node';
                    $projectRoot ??= $npmRoot;
                    $addStep('npm', (string) __('cron.discover_step_npm'), 'running');
                    foreach ($this->discoverNpmScripts($npmRoot, $pathUser, $domain) as $item) {
                        $byCommand[$item['command']] = $item;
                    }
                    $addStep('npm', (string) __('cron.discover_step_npm_done'));
                }
            }
        }

        if ($profile === 'unknown' && $byCommand === []) {
            $addStep('fallback', (string) __('cron.discover_step_fallback'));
            foreach ($this->fallbackSuggestions($domain, $phpBin) as $item) {
                $byCommand[$item['command']] = $item;
            }
        }

        $suggestions = array_values($byCommand);
        usort($suggestions, static function (array $a, array $b): int {
            $rank = static fn (array $x): int => match ($x['kind'] ?? '') {
                'existing' => 4,
                'artisan', 'spark' => 3,
                'scheduler' => 2,
                default => 1,
            };
            $ra = $rank($a) + (($a['scheduled'] ?? false) ? 10 : 0);
            $rb = $rank($b) + (($b['scheduled'] ?? false) ? 10 : 0);
            if ($ra !== $rb) {
                return $rb <=> $ra;
            }

            return strcasecmp((string) $a['label'], (string) $b['label']);
        });

        if (count($suggestions) > self::MAX_SUGGESTIONS) {
            $suggestions = array_slice($suggestions, 0, self::MAX_SUGGESTIONS);
        }

        $addStep('finish', (string) __('cron.discover_step_finish', ['count' => count($suggestions)]));

        return [
            'profile' => $profile,
            'project_root' => $projectRoot,
            'php_binary' => $phpBin,
            'domain' => $domain->name,
            'scan_steps' => $steps,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * @return list<array{id: string, kind: string, label: string, description: string, command: string, recommended_schedule: string|null, scheduled: bool}>
     */
    private function discoverExistingPanelCrons(Domain $domain): array
    {
        $needle = strtolower(trim((string) $domain->name));
        if ($needle === '') {
            return [];
        }

        $items = [];
        $jobs = CronJob::query()
            ->where('user_id', $domain->user_id)
            ->where('is_system', false)
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        foreach ($jobs as $job) {
            $cmdLower = strtolower($job->command);
            $matchesName = str_contains($cmdLower, $needle);
            $matchesRoot = false;
            foreach (CronAllowedPaths::rootsFromDomainRecord($domain) as $root) {
                if ($root !== '' && str_contains($cmdLower, strtolower($root))) {
                    $matchesRoot = true;
                    break;
                }
            }
            if (! $matchesName && ! $matchesRoot) {
                continue;
            }
            $items[] = [
                'id' => 'existing:'.$job->id,
                'kind' => 'existing',
                'label' => $job->description ?: __('cron.discover_existing_job'),
                'description' => $job->schedule.' — '.__('cron.discover_existing_hint'),
                'command' => $job->command,
                'recommended_schedule' => $job->schedule,
                'scheduled' => false,
            ];
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function locateArtisanRoots(string $base): array
    {
        $candidates = [$base];
        if (str_ends_with($base, '/public_html') || str_ends_with($base, '/public')) {
            $candidates[] = dirname($base);
        }
        $candidates[] = $base.'/public_html';

        return array_values(array_unique(array_filter($candidates, static fn (string $p) => $p !== '' && $p !== '.' && is_dir($p))));
    }

    /**
     * @return list<string>
     */
    private function locateSparkRoots(string $base): array
    {
        $candidates = [
            $base,
            $base.'/public_html',
            dirname($base),
            dirname($base).'/public_html',
        ];

        return array_values(array_unique(array_filter($candidates, static fn (string $p) => $p !== '' && $p !== '.' && is_dir($p))));
    }

    /**
     * @return list<string>
     */
    private function locateNpmRoots(string $base): array
    {
        return $this->locateSparkRoots($base);
    }

    /**
     * @return list<array{id: string, kind: string, label: string, description: string, command: string, recommended_schedule: string|null, scheduled: bool}>
     */
    private function discoverArtisan(string $root, string $phpBin, User $user, Domain $domain, bool $deep): array
    {
        $artisan = $root.'/artisan';
        if (! CronAllowedPaths::isAllowed($artisan, $user, $domain)) {
            return [];
        }

        $items = [];
        $scheduledNames = [];

        $scheduleOut = $this->run([$phpBin, $artisan, 'schedule:list'], $root, 25);
        foreach ($this->parseScheduleList($scheduleOut) as $row) {
            $name = $row['command'];
            $scheduledNames[$name] = true;
            $items[] = $this->artisanSuggestion($phpBin, $artisan, $name, $row['description'], $row['schedule'], true);
        }

        $this->mergeArtisanFromConsoleRoutes($root, $items, $scheduledNames, $phpBin, $artisan);

        if ($deep || $items === []) {
            $listOut = $this->run([$phpBin, $artisan, 'list', '--format=json'], $root, 90);
            $decoded = json_decode($listOut, true);
            if (is_array($decoded)) {
                foreach ($this->parseArtisanJson($decoded) as $row) {
                    $name = $row['name'];
                    if (isset($scheduledNames[$name]) || $this->shouldSkipArtisanCommand($name)) {
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
     * @param  list<array{id: string, kind: string, label: string, description: string, command: string, recommended_schedule: string|null, scheduled: bool}>  $items
     * @param  array<string, bool>  $scheduledNames
     */
    private function mergeArtisanFromConsoleRoutes(string $root, array &$items, array &$scheduledNames, string $phpBin, string $artisan): void
    {
        $files = [
            $root.'/routes/console.php',
            $root.'/app/Console/Kernel.php',
        ];
        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }
            $content = @file_get_contents($file);
            if (! is_string($content)) {
                continue;
            }
            if (preg_match_all("#(?:Schedule::|schedule->)command\(\s*['\"]([^'\"]+)['\"]#", $content, $m) !== false) {
                foreach ($m[1] as $name) {
                    if ($name === '' || isset($scheduledNames[$name]) || $this->shouldSkipArtisanCommand($name)) {
                        continue;
                    }
                    $scheduledNames[$name] = true;
                    $items[] = $this->artisanSuggestion($phpBin, $artisan, $name, (string) __('cron.discover_from_routes'), '*/15 * * * *', true);
                }
            }
        }
    }

    /**
     * @return list<array{id: string, kind: string, label: string, description: string, command: string, recommended_schedule: string|null, scheduled: bool}>
     */
    private function discoverSpark(string $root, string $phpBin, User $user, Domain $domain): array
    {
        $spark = $root.'/spark';
        if (! CronAllowedPaths::isAllowed($spark, $user, $domain)) {
            return [];
        }

        $items = [];
        $out = $this->run([$phpBin, $spark, 'list'], $root, 30);
        foreach ($this->parseSparkList($out) as $row) {
            $items[] = [
                'id' => 'spark:'.$row['name'],
                'kind' => 'spark',
                'label' => $row['name'],
                'description' => $row['description'],
                'command' => $phpBin.' '.$spark.' '.$row['name'],
                'recommended_schedule' => $this->guessSchedule($row['name']),
                'scheduled' => false,
            ];
        }

        return $items;
    }

    /**
     * @return list<array{id: string, kind: string, label: string, description: string, command: string, recommended_schedule: string|null, scheduled: bool}>
     */
    private function discoverNpmScripts(string $root, User $user, Domain $domain): array
    {
        $pkgPath = $root.'/package.json';
        if (! is_file($pkgPath) || ! CronAllowedPaths::isAllowed($pkgPath, $user, $domain)) {
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

        $npm = is_executable('/usr/bin/npm') ? '/usr/bin/npm' : 'npm';
        $items = [];
        foreach ($scripts as $name => $script) {
            if (! is_string($name) || $name === '' || ! is_string($script)) {
                continue;
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
     * @return list<array{id: string, kind: string, label: string, description: string, command: string, recommended_schedule: string|null, scheduled: bool}>
     */
    private function fallbackSuggestions(Domain $domain, string $phpBin): array
    {
        $items = [];
        $name = strtolower(trim((string) $domain->name));
        $configured = rtrim(str_replace('\\', '/', (string) config('panelze.hosting_web_root', '')), '/');
        if ($configured === '' || $name === '') {
            return [];
        }

        $site = $configured.'/'.$name;
        $artisan = $site.'/artisan';
        $spark = $site.'/public_html/spark';
        if (is_file($artisan)) {
            $items[] = $this->artisanSuggestion($phpBin, $artisan, 'schedule:run', (string) __('cron.discover_schedule_run_desc'), '* * * * *', false, 'scheduler');
        }
        if (is_file($spark)) {
            $items[] = [
                'id' => 'spark:custom',
                'kind' => 'spark',
                'label' => 'spark …',
                'description' => (string) __('cron.discover_spark_manual'),
                'command' => $phpBin.' '.$spark.' ',
                'recommended_schedule' => '*/5 * * * *',
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
            $desc = is_array($def) ? (string) ($def['description'] ?? $def['help'] ?? '') : '';
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
            } elseif (preg_match('#^([a-z0-9][a-z0-9:\-_/]+)$#i', $line, $m) === 1) {
                $rows[] = ['name' => trim($m[1]), 'description' => ''];
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
        $unique = [];
        foreach (CronAllowedPaths::rootsFromDomainRecord($domain) as $root) {
            if (is_dir($root)) {
                $unique[rtrim($root, '/')] = true;
            }
        }

        return array_keys($unique);
    }

    private function phpBinary(): string
    {
        foreach (['/usr/bin/php', '/usr/local/bin/php'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        $configured = trim((string) config('panelze.cron.php_binary', ''));
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
                'LANG' => 'C.UTF-8',
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
