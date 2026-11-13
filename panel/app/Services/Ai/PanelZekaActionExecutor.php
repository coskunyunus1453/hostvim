<?php

namespace App\Services\Ai;

use App\Models\CronJob;
use App\Models\Domain;
use App\Models\User;
use App\Services\Cron\CronCommandParser;
use App\Services\Cron\CronJobExecutor;
use App\Services\DatabaseService;
use App\Services\DomainService;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PanelZekaActionExecutor
{
    public function __construct(
        private EngineApiService $engine,
        private DomainService $domainService,
        private DatabaseService $databaseService,
        private HostingQuotaService $quota,
        private CronCommandParser $cronParser,
        private CronJobExecutor $cronExecutor,
    ) {}

    /**
     * @param  array<string, mixed>  $action
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    public function execute(User $user, array $action): array
    {
        $type = strtolower(trim((string) ($action['type'] ?? '')));
        $params = is_array($action['params'] ?? null) ? $action['params'] : [];

        if (! $this->userMayRunAction($user, $type)) {
            return ['ok' => false, 'message' => 'Bu işlem için yetkiniz yok: '.$type];
        }

        return match ($type) {
            'file_write' => $this->fileWrite($user, $params),
            'read_file' => $this->readFile($user, $params),
            'create_domain' => $this->createDomain($user, $params),
            'create_database' => $this->createDatabase($user, $params),
            'security_toggle' => $this->securityToggle($user, $params),
            'run_command' => $this->runCommand($user, $params),
            'list_largest_files' => $this->listLargestFiles($user, $params),
            'run_cron_now' => $this->runCronNow($user, $params),
            default => ['ok' => false, 'message' => 'Bilinmeyen aksiyon türü: '.$type],
        };
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     * @return list<array<string, mixed>>
     */
    public function executeBatch(User $user, array $actions): array
    {
        $results = [];
        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }
            $id = (string) ($action['id'] ?? Str::uuid()->toString());
            try {
                $out = $this->execute($user, $action);
                $results[] = array_merge(['id' => $id, 'type' => $action['type'] ?? ''], $out);
            } catch (\Throwable $e) {
                $results[] = [
                    'id' => $id,
                    'type' => $action['type'] ?? '',
                    'ok' => false,
                    'message' => $this->exceptionMessage($e),
                ];
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    private function fileWrite(User $user, array $params): array
    {
        $domain = $this->resolveDomain($user, (int) ($params['domain_id'] ?? 0));
        $path = trim((string) ($params['path'] ?? ''));
        $content = (string) ($params['content'] ?? '');
        if ($path === '' || str_contains($path, '..') || $this->isSensitivePath($path)) {
            return ['ok' => false, 'message' => 'Geçersiz veya yasak dosya yolu.'];
        }

        $resp = $this->engine->writeFile($domain->name, $path, $content);
        if (! empty($resp['error'])) {
            return ['ok' => false, 'message' => (string) $resp['error']];
        }

        return ['ok' => true, 'message' => 'Dosya kaydedildi: '.$path, 'data' => ['path' => $path]];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    private function readFile(User $user, array $params): array
    {
        $domain = $this->resolveDomain($user, (int) ($params['domain_id'] ?? 0));
        $path = trim((string) ($params['path'] ?? ''));
        if ($path === '' || str_contains($path, '..') || $this->isSensitivePath($path)) {
            return ['ok' => false, 'message' => 'Geçersiz veya yasak dosya yolu.'];
        }

        try {
            $content = $this->engine->readFile($domain->name, $path);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return [
            'ok' => true,
            'message' => 'Dosya okundu.',
            'data' => ['path' => $path, 'content' => mb_substr($content, 0, 64_000)],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    private function createDomain(User $user, array $params): array
    {
        $name = strtolower(trim((string) ($params['name'] ?? '')));
        if ($name === '') {
            return ['ok' => false, 'message' => 'Alan adı gerekli.'];
        }

        $this->quota->ensureCanCreateDomain($user);

        $domain = $this->domainService->create(
            $user,
            $name,
            (string) ($params['php_version'] ?? '8.2'),
            (string) ($params['server_type'] ?? 'nginx'),
        );

        return [
            'ok' => true,
            'message' => 'Alan adı oluşturuldu: '.$domain->name,
            'data' => ['domain_id' => $domain->id, 'name' => $domain->name],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    private function createDatabase(User $user, array $params): array
    {
        $name = trim((string) ($params['name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'message' => 'Veritabanı adı gerekli.'];
        }

        $domainId = isset($params['domain_id']) ? (int) $params['domain_id'] : null;
        if ($domainId) {
            $this->resolveDomain($user, $domainId);
        }

        $this->quota->ensureCanCreateDatabase($user);

        $result = $this->databaseService->create(
            $user,
            $name,
            (string) ($params['type'] ?? 'mysql'),
            $domainId,
            isset($params['grant_host']) ? (string) $params['grant_host'] : null,
        );

        return [
            'ok' => true,
            'message' => 'Veritabanı oluşturuldu: '.$result['database']->name,
            'data' => [
                'database_id' => $result['database']->id,
                'name' => $result['database']->name,
                'password_plain' => $result['password_plain'] ?? null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    private function securityToggle(User $user, array $params): array
    {
        if (! $user->isAdmin()) {
            return ['ok' => false, 'message' => 'Güvenlik ayarları yalnızca yönetici tarafından değiştirilebilir.'];
        }

        $feature = strtolower(trim((string) ($params['feature'] ?? '')));
        $enabled = (bool) ($params['enabled'] ?? true);

        $resp = match ($feature) {
            'fail2ban' => $this->engine->toggleFail2ban($enabled),
            'modsecurity' => $this->engine->toggleModSecurity($enabled),
            'clamav' => $this->engine->toggleClamav($enabled),
            default => ['error' => 'Geçersiz özellik: '.$feature],
        };

        if (! empty($resp['error'])) {
            return ['ok' => false, 'message' => (string) $resp['error']];
        }

        return [
            'ok' => true,
            'message' => sprintf('%s %s.', $feature, $enabled ? 'etkinleştirildi' : 'devre dışı bırakıldı'),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    private function runCommand(User $user, array $params): array
    {
        $command = trim((string) ($params['command'] ?? ''));
        if ($command === '') {
            return ['ok' => false, 'message' => 'Komut boş.'];
        }

        try {
            $parsed = $this->cronParser->parse($command, $user);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $this->exceptionMessage($e)];
        }

        // CronCommandParser argv boş döner; komut satırını shell ile çalıştır (cron executor ile aynı).
        $shellCommand = trim((string) ($parsed['command'] ?? ''));
        if ($shellCommand === '') {
            return ['ok' => false, 'message' => 'Komut ayrıştırılamadı.'];
        }

        $cwd = $parsed['working_directory'] ?? null;
        if ($cwd === null && ! empty($params['domain_id'])) {
            try {
                $domain = $this->resolveDomain($user, (int) $params['domain_id']);
                $cwd = $domain->document_root ? rtrim((string) $domain->document_root, '/') : null;
            } catch (\Throwable) {
            }
        }

        $env = ['PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'];
        $process = Process::fromShellCommandline($shellCommand, $cwd, $env);
        $process->setTimeout((int) config('panelze.cron.timeout', 180));
        $process->setIdleTimeout((int) config('panelze.cron.idle_timeout', 120));

        try {
            $process->mustRun();
            $output = trim($process->getOutput()."\n".$process->getErrorOutput());

            return [
                'ok' => true,
                'message' => 'Komut tamamlandı.',
                'data' => [
                    'exit_code' => $process->getExitCode(),
                    'command' => $shellCommand,
                    'output' => mb_substr($output, 0, 32_000),
                ],
            ];
        } catch (\Throwable $e) {
            $output = trim(($process->getOutput() ?? '')."\n".($process->getErrorOutput() ?? '')."\n".$e->getMessage());

            return [
                'ok' => false,
                'message' => 'Komut başarısız: '.Str::limit($this->exceptionMessage($e), 240),
                'data' => [
                    'command' => $shellCommand,
                    'output' => mb_substr($output, 0, 32_000),
                ],
            ];
        }
    }

    /**
     * Diskte en büyük dosyaları bul (güvenli, panel aksiyonu).
     *
     * @param  array<string, mixed>  $params
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    private function listLargestFiles(User $user, array $params): array
    {
        $limit = max(5, min(100, (int) ($params['limit'] ?? 20)));
        $path = trim((string) ($params['path'] ?? ''));

        if ($path === '') {
            if (! empty($params['domain_id'])) {
                try {
                    $domain = $this->resolveDomain($user, (int) $params['domain_id']);
                    $path = $domain->document_root
                        ? rtrim((string) $domain->document_root, '/')
                        : '';
                } catch (\Throwable) {
                }
            }
            if ($path === '') {
                $configured = rtrim(str_replace('\\', '/', (string) config('panelze.hosting_web_root', '')), '/');
                $path = $configured !== '' && is_dir($configured)
                    ? $configured
                    : (is_dir('/var/www') ? '/var/www' : base_path());
            }
        }

        $path = rtrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || str_contains($path, '..') || ! is_dir($path)) {
            return ['ok' => false, 'message' => 'Geçersiz dizin: '.$path];
        }

        if (! \App\Services\Cron\CronAllowedPaths::isAllowed($path, $user)) {
            return ['ok' => false, 'message' => 'Bu dizine erişim izni yok: '.$path];
        }

        // find + sort -n: güvenli salt okuma analizi (silme yok).
        $escaped = escapeshellarg($path);
        $shell = sprintf(
            'find %s -xdev -type f -printf \'%%s\\t%%p\\n\' 2>/dev/null | sort -nr | head -n %d',
            $escaped,
            $limit
        );

        $process = Process::fromShellCommandline($shell, null, [
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ]);
        $process->setTimeout(120);

        try {
            $process->mustRun();
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Dosya taraması başarısız: '.$this->exceptionMessage($e),
                'data' => ['path' => $path, 'output' => mb_substr($process->getErrorOutput(), 0, 4000)],
            ];
        }

        $lines = preg_split("/\r\n|\n|\r/", trim($process->getOutput())) ?: [];
        $files = [];
        foreach ($lines as $line) {
            if ($line === '' || ! str_contains($line, "\t")) {
                continue;
            }
            [$size, $filePath] = explode("\t", $line, 2);
            $bytes = (int) $size;
            $files[] = [
                'path' => $filePath,
                'bytes' => $bytes,
                'size_human' => $this->formatBytes($bytes),
            ];
        }

        $summary = $files === []
            ? 'Dosya bulunamadı: '.$path
            : sprintf('%d büyük dosya listelendi (%s altında).', count($files), $path);

        $text = $summary."\n";
        foreach ($files as $i => $f) {
            $text .= sprintf("%d) %s — %s\n", $i + 1, $f['size_human'], $f['path']);
        }

        return [
            'ok' => true,
            'message' => $summary,
            'data' => [
                'path' => $path,
                'limit' => $limit,
                'files' => $files,
                'output' => mb_substr(trim($text), 0, 32_000),
            ],
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $n = (float) max(0, $bytes);
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }

        return ($i === 0 ? (string) (int) $n : number_format($n, $n >= 10 ? 1 : 2)).' '.$units[$i];
    }

    private function exceptionMessage(\Throwable $e): string
    {
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $flat = collect($e->errors())->flatten()->filter()->values();
            if ($flat->isNotEmpty()) {
                return (string) $flat->first();
            }
        }

        $msg = trim($e->getMessage());

        return $msg !== '' ? $msg : class_basename($e);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    private function runCronNow(User $user, array $params): array
    {
        $cronId = (int) ($params['cron_job_id'] ?? 0);
        $job = CronJob::query()->where('user_id', $user->id)->find($cronId);
        if ($job === null) {
            return ['ok' => false, 'message' => 'Cron görevi bulunamadı.'];
        }

        $run = $this->cronExecutor->execute($job, $user->id);

        return [
            'ok' => $run->status === 'success',
            'message' => $run->status === 'success' ? 'Cron görevi çalıştırıldı.' : 'Cron çalıştırması: '.$run->status,
            'data' => ['status' => $run->status, 'output' => mb_substr((string) $run->output, 0, 16_000)],
        ];
    }

    private function isSensitivePath(string $path): bool
    {
        $base = strtolower(basename(str_replace('\\', '/', $path)));
        $norm = strtolower(str_replace('\\', '/', $path));

        $denyNames = [
            '.env',
            'wp-config.php',
            'id_rsa',
            'id_dsa',
            'id_ed25519',
            'authorized_keys',
            'database.php',
        ];
        if (in_array($base, $denyNames, true)) {
            return true;
        }
        if (str_ends_with($base, '.pem') || str_ends_with($base, '.key')) {
            return true;
        }
        if (preg_match('#(^|/)\.env(\.|$)#', $norm)) {
            return true;
        }

        return false;
    }

    private function resolveDomain(User $user, int $domainId): Domain
    {
        $domain = Domain::query()->where('user_id', $user->id)->find($domainId);
        if ($domain === null && $user->isAdmin()) {
            $domain = Domain::query()->find($domainId);
        }
        if ($domain === null) {
            throw new \InvalidArgumentException('Domain bulunamadı veya erişim yok.');
        }

        return $domain;
    }

    private function userMayRunAction(User $user, string $type): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $ability = match ($type) {
            'file_write' => 'files:write',
            'read_file', 'list_largest_files' => 'files:read',
            'create_domain' => 'domains:write',
            'create_database' => 'databases:write',
            'security_toggle' => 'security:write',
            'run_command' => 'tools:run',
            'run_cron_now' => 'cron:write',
            default => null,
        };

        return $ability !== null && $user->can($ability);
    }
}
