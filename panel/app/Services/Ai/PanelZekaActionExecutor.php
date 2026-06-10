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

        return match ($type) {
            'file_write' => $this->fileWrite($user, $params),
            'read_file' => $this->readFile($user, $params),
            'create_domain' => $this->createDomain($user, $params),
            'create_database' => $this->createDatabase($user, $params),
            'security_toggle' => $this->securityToggle($user, $params),
            'run_command' => $this->runCommand($user, $params),
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
                    'message' => $e->getMessage(),
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

        $parsed = $this->cronParser->parse($command, $user);
        $argv = $parsed['argv'];
        $cwd = $parsed['working_directory'];

        if ($cwd === null && ! empty($params['domain_id'])) {
            try {
                $domain = $this->resolveDomain($user, (int) $params['domain_id']);
                $cwd = $domain->document_root ? rtrim((string) $domain->document_root, '/') : null;
            } catch (\Throwable) {
            }
        }

        $process = new Process($argv, $cwd, ['PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin']);
        $process->setTimeout((int) config('panelze.cron.timeout', 180));
        $process->setIdleTimeout((int) config('panelze.cron.idle_timeout', 120));

        try {
            $process->mustRun();
            $output = trim($process->getOutput()."\n".$process->getErrorOutput());

            return [
                'ok' => true,
                'message' => 'Komut tamamlandı.',
                'data' => ['exit_code' => $process->getExitCode(), 'output' => mb_substr($output, 0, 32_000)],
            ];
        } catch (\Throwable $e) {
            $output = trim(($process->getOutput() ?? '')."\n".($process->getErrorOutput() ?? '')."\n".$e->getMessage());

            return [
                'ok' => false,
                'message' => 'Komut başarısız.',
                'data' => ['output' => mb_substr($output, 0, 32_000)],
            ];
        }
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
}
