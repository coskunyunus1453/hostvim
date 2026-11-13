<?php

namespace App\Services\Ai;

use App\Models\CronJob;
use App\Models\Domain;
use App\Models\User;
use App\Services\Cron\CronAllowedPaths;
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
            'safe_delete_file' => $this->safeDeleteFile($user, $params),
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

        // Admin: sudo önekini temizle — yetki panelze-ai-exec ile verilir.
        $asAdmin = $user->isAdmin();
        if ($asAdmin) {
            $command = $this->stripSudoPrefix($command);
        } elseif (preg_match('/(?:^|[\s;&|(\/])sudo(?:\s|$)/i', $command) === 1) {
            return [
                'ok' => false,
                'message' => 'sudo yalnızca yönetici PanelZeka oturumunda (şifresiz yetkili çalıştırıcı ile) kullanılabilir.',
            ];
        }

        try {
            $parsed = $this->cronParser->parse($command, $user);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $this->exceptionMessage($e)];
        }

        // CronCommandParser argv boş döner; komut satırını shell ile çalıştır.
        $shellCommand = trim((string) ($parsed['command'] ?? ''));
        if ($shellCommand === '') {
            return ['ok' => false, 'message' => 'Komut ayrıştırılamadı.'];
        }
        if ($asAdmin) {
            $shellCommand = $this->stripSudoPrefix($shellCommand);
        }

        $cwd = $parsed['working_directory'] ?? null;
        if ($cwd === null && ! empty($params['domain_id'])) {
            try {
                $domain = $this->resolveDomain($user, (int) $params['domain_id']);
                $cwd = $domain->document_root ? rtrim((string) $domain->document_root, '/') : null;
            } catch (\Throwable) {
            }
        }

        $env = [
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
            'DEBIAN_FRONTEND' => 'noninteractive',
            'TERM' => 'dumb',
        ];

        $process = $this->makeCommandProcess($shellCommand, $cwd, $env, $asAdmin);
        if ($process === null) {
            return [
                'ok' => false,
                'message' => 'Yetkili çalıştırıcı yok. Sunucuda panelze-ai-exec + sudoers kurulu olmalı.',
                'data' => ['command' => $shellCommand],
            ];
        }

        $process->setTimeout((int) config('panelze.cron.timeout', 180));
        $process->setIdleTimeout((int) config('panelze.cron.idle_timeout', 120));

        try {
            $process->mustRun();
            $output = trim($process->getOutput()."\n".$process->getErrorOutput());

            return [
                'ok' => true,
                'message' => $asAdmin ? 'Komut tamamlandı (yetkili).' : 'Komut tamamlandı.',
                'data' => $this->attachLargeFilesUiIfPresent([
                    'exit_code' => $process->getExitCode(),
                    'command' => $shellCommand,
                    'privileged' => $asAdmin,
                    'output' => mb_substr($output, 0, 32_000),
                ], $output),
            ];
        } catch (\Throwable $e) {
            $output = trim(($process->getOutput() ?? '')."\n".($process->getErrorOutput() ?? '')."\n".$e->getMessage());

            return [
                'ok' => false,
                'message' => 'Komut başarısız: '.Str::limit($this->exceptionMessage($e), 240),
                'data' => $this->attachLargeFilesUiIfPresent([
                    'command' => $shellCommand,
                    'privileged' => $asAdmin,
                    'output' => mb_substr($output, 0, 32_000),
                ], $output),
            ];
        }
    }

    /**
     * du/find boyut listesi çıktısını kart UI + güvenli silme için zenginleştir.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attachLargeFilesUiIfPresent(array $data, string $output): array
    {
        $built = app(PanelZekaLargeFileAdvisor::class)->tryBuildLargeFilesFromOutput($output);
        if ($built === null) {
            return $data;
        }
        $data['files'] = $built['files'];
        $data['ui'] = 'large_files';
        // Ham du çıktısı yerine özet; kartlar asıl UI
        $data['output'] = $built['output'];

        return $data;
    }

    /**
     * @param  array<string, string>  $env
     */
    private function makeCommandProcess(string $shellCommand, ?string $cwd, array $env, bool $asAdmin): ?Process
    {
        if ($asAdmin) {
            $helper = '/usr/local/sbin/panelze-ai-exec';
            if (! is_executable($helper)) {
                $repo = dirname(base_path()).'/deploy/host/panelze-ai-exec';
                if (is_file($repo)) {
                    $helper = $repo;
                } else {
                    return null;
                }
            }

            // cwd varsa komutu cd && ile sar (ai-exec kendi cwd kullanmaz)
            $wrapped = $shellCommand;
            if ($cwd !== null && $cwd !== '') {
                $wrapped = 'cd '.escapeshellarg($cwd).' && '.$shellCommand;
            }

            return new Process(
                ['sudo', '-n', $helper, '-c', $wrapped],
                null,
                $env,
            );
        }

        return Process::fromShellCommandline($shellCommand, $cwd, $env);
    }

    private function stripSudoPrefix(string $command): string
    {
        $cmd = trim($command);
        // "sudo -n -E cmd" / "sudo cmd"
        while (preg_match('/^sudo\s+(?:(?:-[nEHu]\s+)|(?:-u\s+\S+\s+))*/i', $cmd) === 1) {
            $cmd = trim((string) preg_replace('/^sudo\s+(?:(?:-[nEHu]\s+)|(?:-u\s+\S+\s+))*/i', '', $cmd, 1));
        }

        return $cmd;
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

        $env = [
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
            'DEBIAN_FRONTEND' => 'noninteractive',
            'TERM' => 'dumb',
        ];
        $process = $this->makeCommandProcess($shell, null, $env, $user->isAdmin())
            ?? Process::fromShellCommandline($shell, null, $env);
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
        $rawFiles = [];
        foreach ($lines as $line) {
            if ($line === '' || ! str_contains($line, "\t")) {
                continue;
            }
            [$size, $filePath] = explode("\t", $line, 2);
            $bytes = (int) $size;
            $rawFiles[] = [
                'path' => $filePath,
                'bytes' => $bytes,
                'size_human' => $this->formatBytes($bytes),
            ];
        }

        $advisor = app(PanelZekaLargeFileAdvisor::class);
        $files = $advisor->enrichMany($rawFiles);

        $summary = $files === []
            ? 'Dosya bulunamadı: '.$path
            : sprintf('%d büyük dosya listelendi (%s altında).', count($files), $path);

        $text = $summary."\n";
        foreach ($files as $i => $f) {
            $a = is_array($f['assessment'] ?? null) ? $f['assessment'] : [];
            $text .= sprintf(
                "%d) %s — %s [%s · %s]\n   %s\n",
                $i + 1,
                $f['size_human'] ?? '',
                $f['path'] ?? '',
                $a['category_label'] ?? '?',
                $a['risk'] ?? '?',
                $a['summary'] ?? ''
            );
        }

        return [
            'ok' => true,
            'message' => $summary,
            'data' => [
                'path' => $path,
                'limit' => $limit,
                'files' => $files,
                'ui' => 'large_files',
                'output' => mb_substr(trim($text), 0, 32_000),
            ],
        ];
    }

    /**
     * Büyük dosyayı güvenli şekilde trash'e taşı (kalıcı silme yok).
     *
     * @param  array<string, mixed>  $params
     * @return array{ok: bool, message: string, data?: array<string, mixed>}
     */
    private function safeDeleteFile(User $user, array $params): array
    {
        $path = trim(str_replace('\\', '/', (string) ($params['path'] ?? '')));
        $confirm = filter_var($params['confirm'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($path === '' || str_contains($path, '..') || ! str_starts_with($path, '/')) {
            return ['ok' => false, 'message' => 'Geçersiz dosya yolu.'];
        }
        if ($this->isSensitivePath($path)) {
            return ['ok' => false, 'message' => 'Hassas dosya silinemez: '.$path];
        }
        if (! CronAllowedPaths::isAllowed($path, $user) && ! CronAllowedPaths::isAllowed(dirname($path), $user)) {
            return ['ok' => false, 'message' => 'Bu yola erişim izni yok.'];
        }

        $advisor = app(PanelZekaLargeFileAdvisor::class);
        $isDir = is_dir($path) && ! is_link($path);
        $bytes = is_file($path) ? (int) (@filesize($path) ?: 0) : null;
        $assessment = $advisor->assess($path, $bytes, $isDir);

        if (! ($assessment['deletable'] ?? false) || ($assessment['risk'] ?? '') === PanelZekaLargeFileAdvisor::RISK_BLOCKED) {
            return [
                'ok' => false,
                'message' => $assessment['reason'] ?? 'Bu dosya silinemez.',
                'data' => ['path' => $path, 'assessment' => $assessment],
            ];
        }

        if (($assessment['requires_confirm'] ?? false) && ! $confirm) {
            return [
                'ok' => false,
                'message' => 'Bu dosya için açık onay gerekir (confirm=true).',
                'data' => ['path' => $path, 'assessment' => $assessment, 'needs_confirm' => true],
            ];
        }

        // Dosya var mı? (admin privileged ile de kontrol)
        $existsCheck = $this->pathExistsForUser($path, $user);
        if (! $existsCheck) {
            return ['ok' => false, 'message' => 'Dosya bulunamadı: '.$path];
        }

        $domain = $this->findDomainForAbsolutePath($user, $path);
        if ($domain !== null) {
            $moved = $this->trashViaEngine($domain, $path);
            if ($moved['ok']) {
                return [
                    'ok' => true,
                    'message' => sprintf(
                        'Dosya «%s» sitesinin Dosya Yöneticisi çöpüne taşındı. Kalıcı silmek için: Dosya Yöneticisi → %s → Çöp kutusu — veya PanelZeka Çöp paneli.',
                        $domain->name,
                        $domain->name
                    ),
                    'data' => [
                        'path' => $path,
                        'method' => 'engine_trash',
                        'trash_id' => $moved['id'] ?? null,
                        'domain' => $domain->name,
                        'domain_id' => $domain->id,
                        'assessment' => $assessment,
                        'ui' => 'delete_result',
                    ],
                ];
            }
            // Engine başarısızsa admin için shell trash dene
            if (! $user->isAdmin()) {
                return ['ok' => false, 'message' => $moved['message'] ?? 'Çöpe taşıma başarısız.'];
            }
        }

        if (! $user->isAdmin()) {
            return ['ok' => false, 'message' => 'Dosya bir site köküne bağlanamadı; müşteri silmesi engellendi.'];
        }

        $shellTrash = $this->trashViaPrivilegedMove($path);
        if (! ($shellTrash['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $shellTrash['message'] ?? 'Güvenli silme başarısız.',
                'data' => ['path' => $path, 'assessment' => $assessment],
            ];
        }

        return [
            'ok' => true,
            'message' => 'Dosya güvenli çöpe taşındı: '.basename($path),
            'data' => [
                'path' => $path,
                'method' => 'ai_trash',
                'trash_path' => $shellTrash['trash_path'] ?? null,
                'assessment' => $assessment,
                'ui' => 'delete_result',
            ],
        ];
    }

    private function pathExistsForUser(string $path, User $user): bool
    {
        if (is_file($path) || is_link($path)) {
            return true;
        }
        if (! $user->isAdmin()) {
            return false;
        }
        $proc = $this->makeCommandProcess('test -e '.escapeshellarg($path).' && echo YES', null, [
            'PATH' => '/usr/bin:/bin',
            'TERM' => 'dumb',
        ], true);
        if ($proc === null) {
            return false;
        }
        $proc->setTimeout(15);
        try {
            $proc->run();

            return str_contains($proc->getOutput(), 'YES');
        } catch (\Throwable) {
            return false;
        }
    }

    private function findDomainForAbsolutePath(User $user, string $absPath): ?Domain
    {
        $abs = rtrim(str_replace('\\', '/', $absPath), '/');
        $query = Domain::query()->select(['id', 'name', 'document_root', 'user_id']);
        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        $webRoot = rtrim(str_replace('\\', '/', (string) config('panelze.hosting_web_root', '')), '/');

        foreach ($query->get() as $domain) {
            $doc = rtrim(str_replace('\\', '/', (string) $domain->document_root), '/');
            if ($doc !== '' && (str_starts_with($abs, $doc.'/') || $abs === $doc)) {
                return $domain;
            }
            if ($webRoot !== '') {
                $site = $webRoot.'/'.$domain->name;
                if (str_starts_with($abs, $site.'/') || $abs === $site) {
                    return $domain;
                }
            }
        }

        return null;
    }

    /**
     * @return array{ok: bool, id?: string, message?: string}
     */
    private function trashViaEngine(Domain $domain, string $absPath): array
    {
        $abs = str_replace('\\', '/', $absPath);
        $doc = rtrim(str_replace('\\', '/', (string) $domain->document_root), '/');
        $webRoot = rtrim(str_replace('\\', '/', (string) config('panelze.hosting_web_root', '')), '/');
        $siteRoot = $webRoot !== '' ? $webRoot.'/'.$domain->name : '';

        // Engine kökü = hosting/domain. File Manager public_html göreli path kullanır.
        if ($siteRoot === '' || ! str_starts_with($abs, $siteRoot.'/')) {
            return ['ok' => false, 'message' => 'Site göreli yolu çıkarılamadı.'];
        }
        $engineFrom = ltrim(substr($abs, strlen($siteRoot)), '/');
        if ($engineFrom === '' || str_contains($engineFrom, '..')) {
            return ['ok' => false, 'message' => 'Site göreli yolu çıkarılamadı.'];
        }

        // Dosya Yöneticisi çöp geri yükleme: public_html altı path (önek yok)
        $panelOrig = $engineFrom;
        if (str_starts_with($engineFrom, 'public_html/')) {
            $panelOrig = substr($engineFrom, strlen('public_html/'));
        } elseif ($doc !== '' && str_starts_with($abs, $doc.'/')) {
            $panelOrig = ltrim(substr($abs, strlen($doc)), '/');
        }

        $id = now()->format('YmdHis').'-'.Str::lower(Str::random(10));
        $trashDir = '.panelze-trash';
        $item = $trashDir.'/items/'.$id;
        $meta = $trashDir.'/meta/'.$id.'.json';

        try {
            $this->engine->mkdirFile($domain->name, $trashDir);
            $this->engine->mkdirFile($domain->name, $trashDir.'/items');
            $this->engine->mkdirFile($domain->name, $trashDir.'/meta');
            $mv = $this->engine->moveFile($domain->name, $engineFrom, $item);
            if (! empty($mv['error'])) {
                return ['ok' => false, 'message' => (string) $mv['error']];
            }
            $size = is_file($abs) ? (int) (@filesize($abs) ?: 0) : 0;
            // move sonrası abs yok; item boyutunu site trash'ten oku
            $webItem = ($siteRoot !== '' ? $siteRoot : '').'/.panelze-trash/items/'.$id;
            if (is_file($webItem)) {
                $size = (int) (@filesize($webItem) ?: 0);
            }
            $payload = json_encode([
                'id' => $id,
                'original_path' => $panelOrig,
                'engine_path' => $engineFrom,
                'absolute_path' => $abs,
                'deleted_at' => now()->toIso8601String(),
                'name' => basename($abs),
                'size' => $size,
                'source' => 'panelzeka_safe_delete',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $this->engine->writeFile($domain->name, $meta, (string) $payload);

            return ['ok' => true, 'id' => $id];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, message?: string, trash_path?: string}
     */
    private function trashViaPrivilegedMove(string $absPath): array
    {
        $abs = str_replace('\\', '/', $absPath);
        $trashRoot = dirname(base_path()).'/data/.panelze-ai-trash';
        if (! is_dir(dirname($trashRoot))) {
            $trashRoot = '/var/tmp/panelze-ai-trash';
        }
        $stamp = now()->format('YmdHis');
        $destDir = $trashRoot.'/'.$stamp;
        $dest = $destDir.'/'.basename($abs);
        $meta = $destDir.'/meta.json';

        $metaJson = json_encode([
            'original_path' => $abs,
            'deleted_at' => now()->toIso8601String(),
            'trash_path' => $dest,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        // Tek satır: heredoc panelze-ai-exec -c içinde kırılıyor
        $script = sprintf(
            'mkdir -p %s && mv -f %s %s && echo %s | base64 -d > %s && echo TRASH_OK',
            escapeshellarg($destDir),
            escapeshellarg($abs),
            escapeshellarg($dest),
            escapeshellarg(base64_encode($metaJson)),
            escapeshellarg($meta)
        );

        $proc = $this->makeCommandProcess($script, null, [
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
            'TERM' => 'dumb',
        ], true);
        if ($proc === null) {
            return ['ok' => false, 'message' => 'Yetkili çalıştırıcı yok.'];
        }
        $proc->setTimeout(120);
        try {
            $proc->mustRun();
            if (! str_contains($proc->getOutput(), 'TRASH_OK')) {
                return ['ok' => false, 'message' => 'Çöpe taşıma doğrulanamadı.'];
            }

            return ['ok' => true, 'trash_path' => $dest];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Çöpe taşıma başarısız: '.$this->exceptionMessage($e)];
        }
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
            'file_write', 'safe_delete_file' => 'files:write',
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
