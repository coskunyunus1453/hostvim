<?php

namespace App\Console\Commands;

use App\Models\SystemAlert;
use App\Services\EngineApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class RunSelfHealCommand extends Command
{
    protected $signature = 'panelze:self-heal';

    protected $description = 'Run guarded self-heal checks and alerts';

    private const WINDOW_SECONDS = 300; // 5 min

    private const MAX_SAME_ACTION = 3;

    private const COOLDOWN_SECONDS = 120; // 2 min

    public function handle(EngineApiService $engine): int
    {
        $stats = $engine->getSystemStats();
        $services = $engine->getServices();

        $this->handleDiskAlert($stats);
        $this->handleServiceAutoRestart($engine, $services);
        $this->handleNodeAppsWatchdog($engine);

        return self::SUCCESS;
    }

    private function handleNodeAppsWatchdog(EngineApiService $engine): void
    {
        $report = $engine->getNodeAppsWatchdogStatus();
        if (! empty($report['error']) && empty($report['items'])) {
            $err = (string) $report['error'];
            if ($this->isBenignWatchdogTransportError($err)) {
                return;
            }
            $dedupe = 'node-watchdog-error-'.date('YmdH');
            if (! $this->alertExists($dedupe)) {
                $this->createAlert([
                    'level' => 'warning',
                    'title' => 'Node.js watchdog',
                    'message' => $this->friendlyWatchdogError($err),
                    'path' => '/node-apps',
                    'dedupe_key' => $dedupe,
                ]);
            }

            return;
        }

        $failed = (int) ($report['failed'] ?? 0);
        $started = (int) ($report['started'] ?? 0);
        $restarted = (int) ($report['restarted'] ?? 0);

        if ($failed > 0) {
            $lines = [];
            foreach (($report['items'] ?? []) as $item) {
                if (! is_array($item) || ($item['action'] ?? '') !== 'failed') {
                    continue;
                }
                $dom = (string) ($item['domain'] ?? '?');
                $err = $this->friendlyNodeAppError((string) ($item['error'] ?? 'bilinmeyen'));
                $lines[] = "{$dom}: {$err}";
                if (count($lines) >= 5) {
                    break;
                }
            }
            $dedupe = 'node-failed-'.date('YmdHi');
            if (! $this->alertExists($dedupe)) {
                $this->createAlert([
                    'level' => 'error',
                    'title' => "Node.js uygulaması başlatılamadı ({$failed})",
                    'message' => $lines !== [] ? implode("\n", $lines) : 'PM2 süreci ayakta değil veya port dinlemiyor.',
                    'path' => '/node-apps',
                    'dedupe_key' => $dedupe,
                ]);
            }
        } elseif ($started > 0 || $restarted > 0) {
            $dedupe = 'node-recovered-'.date('YmdHi');
            if (! $this->alertExists($dedupe)) {
                $this->createAlert([
                    'level' => 'info',
                    'title' => 'Node.js uygulamaları otomatik yenilendi',
                    'message' => sprintf('Başlatılan: %d, yeniden başlatılan: %d', $started, $restarted),
                    'path' => '/node-apps',
                    'dedupe_key' => $dedupe,
                ]);
            }
        }
    }

    private function handleDiskAlert(array $stats): void
    {
        $disk = (float) ($stats['disk_percent'] ?? 0);
        if ($disk < 80) {
            return;
        }

        $level = $disk >= 90 ? 'error' : 'info';
        $dedupe = $disk >= 90 ? 'disk-high-90' : 'disk-high-80';
        $ttl = $disk >= 90 ? 900 : 1800; // 15m / 30m

        if (Cache::has('selfheal:alert:'.$dedupe)) {
            return;
        }
        Cache::put('selfheal:alert:'.$dedupe, true, now()->addSeconds($ttl));

        $this->createAlert([
            'level' => $level,
            'title' => $disk >= 90 ? 'Disk kullanimi kritik' : 'Disk kullanimi yuksek',
            'message' => sprintf('Disk kullanimi %.1f%%. Temizlik/backup stratejisi kontrol edilmelidir.', $disk),
            'path' => '/system',
            'dedupe_key' => $dedupe,
        ]);
    }

    /**
     * @param  array<int, array<string,mixed>>  $services
     */
    private function handleServiceAutoRestart(EngineApiService $engine, array $services): void
    {
        $byName = [];
        foreach ($services as $svc) {
            $name = (string) ($svc['name'] ?? '');
            if ($name !== '') {
                $byName[$name] = $svc;
            }
        }

        foreach (['nginx', 'apache2'] as $critical) {
            $status = strtolower((string) ($byName[$critical]['status'] ?? 'unknown'));
            if ($status === 'running') {
                continue;
            }
            // Operatör tarafından bilinçli olarak kapatılmış (systemd disabled/masked)
            // servisleri otomatik restart etme. Örn. apache emekliye ayrılıp nginx+PHP-FPM
            // mimarisine geçilmiş bir sunucuda apache2 disabled'dır; aksi halde her dakika
            // başarısız restart denenir ve log "apache2/restart 500" ile dolar.
            if (! $this->isAutoRestartAllowed($critical)) {
                continue;
            }
            $this->tryGuardedRestart($engine, $critical, $status);
        }
    }

    /**
     * systemd'de disabled/masked olan servis için otomatik restart denenmemeli.
     * Tespit edilemezse (boş/unknown) mevcut davranış korunur (true döner).
     */
    private function isAutoRestartAllowed(string $service): bool
    {
        $unit = preg_replace('/[^A-Za-z0-9@._-]/', '', $service);
        if ($unit === '') {
            return true;
        }

        try {
            $p = Process::fromShellCommandline(
                'systemctl is-enabled '.escapeshellarg($unit).' 2>/dev/null',
                null,
                null,
                null,
                10
            );
            $p->run();
            $state = strtolower(trim($p->getOutput()));
        } catch (\Throwable $e) {
            return true;
        }

        foreach (['disabled', 'masked'] as $skip) {
            if (str_starts_with($state, $skip)) {
                return false;
            }
        }

        return true;
    }

    private function tryGuardedRestart(EngineApiService $engine, string $service, string $status): void
    {
        $base = "selfheal:{$service}:restart";
        $lastKey = "{$base}:last";
        $historyKey = "{$base}:history";

        $nowTs = now()->timestamp;
        $last = (int) (Cache::get($lastKey, 0));
        if ($last > 0 && ($nowTs - $last) < self::COOLDOWN_SECONDS) {
            $this->emitGuardrailAlert($service, 'cooldown', 'Auto-restart cooldown aktif, islem ertelendi.');

            return;
        }

        $history = Cache::get($historyKey, []);
        if (! is_array($history)) {
            $history = [];
        }
        $history = array_values(array_filter($history, static fn ($ts) => is_numeric($ts) && ((int) $ts) >= ($nowTs - self::WINDOW_SECONDS)));
        if (count($history) >= self::MAX_SAME_ACTION) {
            $this->emitGuardrailAlert($service, 'limit', 'Auto-restart limitine ulasildi (3/5dk), loop engellendi.');

            return;
        }

        $resp = $engine->controlService($service, 'restart');
        $ok = empty($resp['error']);

        $history[] = $nowTs;
        Cache::put($historyKey, $history, now()->addSeconds(self::WINDOW_SECONDS + 60));
        Cache::put($lastKey, $nowTs, now()->addSeconds(self::COOLDOWN_SECONDS + 60));

        $this->createAlert([
            'level' => $ok ? 'info' : 'error',
            'title' => $ok ? "Auto-restart uygulandi: {$service}" : "Auto-restart basarisiz: {$service}",
            'message' => $ok
                ? 'Servis running degildi, guvenli politika ile restart denendi.'
                : ((string) ($resp['error'] ?? 'bilinmeyen hata')),
            'path' => '/system',
            'dedupe_key' => "restart-{$service}-".date('YmdHi'),
        ]);
    }

    private function emitGuardrailAlert(string $service, string $reason, string $message): void
    {
        $dedupe = "guardrail-{$service}-{$reason}-".date('YmdHi');
        if ($this->alertExists($dedupe)) {
            return;
        }
        $this->createAlert([
            'level' => 'error',
            'title' => "Anti-loop korumasi: {$service}",
            'message' => $message,
            'path' => '/system',
            'dedupe_key' => $dedupe,
        ]);
    }

    private function alertExists(string $dedupeKey): bool
    {
        if (! Schema::hasTable('system_alerts')) {
            return false;
        }

        return SystemAlert::query()->where('dedupe_key', $dedupeKey)->exists();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createAlert(array $payload): void
    {
        if (! Schema::hasTable('system_alerts')) {
            return;
        }
        SystemAlert::query()->create($payload);
    }

    private function isBenignWatchdogTransportError(string $error): bool
    {
        $e = strtolower($error);

        return str_contains($e, 'timed out')
            || str_contains($e, 'curl error 28')
            || str_contains($e, 'connection refused')
            || str_contains($e, 'manage_node_apps devre');
    }

    private function friendlyWatchdogError(string $error): string
    {
        if ($this->isBenignWatchdogTransportError($error)) {
            return 'Node.js uygulama denetimi şu an engine üzerinden okunamadı. Dahili watchdog çalışmaya devam eder; bir süre sonra tekrar kontrol edilecek.';
        }

        return $error;
    }

    private function friendlyNodeAppError(string $error): string
    {
        $e = strtolower($error);
        if (str_contains($e, 'not listening') || str_contains($e, 'port_not_listening')) {
            return 'Uygulama başlatıldı ancak beklenen port dinlenmiyor. Panel portu package.json ile senkronlanıyor; birkaç dakika içinde otomatik düzelecek veya Node uygulaması sayfasından «Onar» kullanın.';
        }

        return $error;
    }
}
