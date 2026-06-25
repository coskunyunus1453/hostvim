<?php

namespace App\Services;

use App\Models\CuriousSpeedResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ServerSpeedtestService
{
    /**
     * @return array{ok: bool, cached?: bool, ping_ms?: float, download_mbps?: float, upload_mbps?: float, label?: string, error?: string}
     */
    public function measureOrCached(string $clientIp, int $userId): array
    {
        if (! config('panelze.curious.ookla_enabled', true)) {
            return ['ok' => false, 'error' => __('curious.speed_ookla_disabled')];
        }

        $cacheMinutes = max(5, (int) config('panelze.curious.ookla_cache_minutes', 30));
        $recent = CuriousSpeedResult::query()
            ->where('user_id', $userId)
            ->where('client_ip', $clientIp)
            ->whereNotNull('server_download_mbps')
            ->where('created_at', '>=', now()->subMinutes($cacheMinutes))
            ->latest('id')
            ->first();

        if ($recent !== null) {
            return [
                'ok' => true,
                'cached' => true,
                'ping_ms' => (float) ($recent->server_ping_ms ?? 0),
                'download_mbps' => (float) $recent->server_download_mbps,
                'upload_mbps' => (float) ($recent->server_upload_mbps ?? 0),
                'label' => $recent->server_label,
            ];
        }

        $lockKey = 'curious.ookla.lock.'.md5($clientIp);
        $lock = Cache::lock($lockKey, 150);
        if (! $lock->get()) {
            return ['ok' => false, 'error' => __('curious.speed_ookla_busy')];
        }

        try {
            return $this->runCli();
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{ok: bool, cached?: bool, ping_ms?: float, download_mbps?: float, upload_mbps?: float, label?: string, error?: string}
     */
    private function runCli(): array
    {
        $binary = $this->resolveBinary();
        if ($binary === null) {
            return ['ok' => false, 'error' => __('curious.speed_ookla_missing')];
        }

        $timeout = max(60, (int) config('panelze.curious.ookla_timeout', 120));
        $isOokla = $this->isOoklaCli($binary);

        $cmd = $isOokla
            ? [$binary, '-f', 'json', '--accept-license', '--accept-gdpr']
            : [$binary, '--json'];

        $process = new Process($cmd, null, null, null, $timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            $err = trim($process->getErrorOutput()."\n".$process->getOutput());
            $err = $err !== '' ? Str::limit($err, 200) : __('curious.speed_ookla_failed');

            return ['ok' => false, 'error' => $err];
        }

        $raw = trim($process->getOutput());
        $parsed = $this->parseOutput($raw, $isOokla);
        if (! ($parsed['ok'] ?? false)) {
            return $parsed;
        }

        return array_merge(['ok' => true, 'cached' => false], $parsed);
    }

    private function resolveBinary(): ?string
    {
        foreach ([
            (string) config('panelze.curious.ookla_binary', 'speedtest'),
            (string) config('panelze.curious.ookla_fallback_binary', 'speedtest-cli'),
        ] as $name) {
            if ($name === '') {
                continue;
            }
            $p = new Process(['which', $name]);
            $p->run();
            if ($p->isSuccessful()) {
                $path = trim($p->getOutput());
                if ($path !== '' && is_executable($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    private function isOoklaCli(string $binary): bool
    {
        $base = basename($binary);
        if (str_contains($base, 'speedtest-cli') || str_contains($base, 'speedtest_cli')) {
            return false;
        }

        $real = realpath($binary) ?: $binary;
        if (is_readable($real)) {
            $head = @file_get_contents($real, false, null, 0, 120);
            if (is_string($head) && (str_contains($head, 'python') || str_contains($head, 'speedtest_cli'))) {
                return false;
            }
        }

        $probe = new Process([$binary, '--version'], null, null, null, 5);
        $probe->run();
        $versionOut = $probe->getOutput().$probe->getErrorOutput();
        if (stripos($versionOut, 'speedtest-cli') !== false) {
            return false;
        }
        if (stripos($versionOut, 'Ookla') !== false) {
            return true;
        }

        return str_contains($base, 'speedtest');
    }

    /**
     * @return array{ok: bool, ping_ms?: float, download_mbps?: float, upload_mbps?: float, label?: string, error?: string}
     */
    private function parseOutput(string $raw, bool $isOokla): array
    {
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return ['ok' => false, 'error' => __('curious.speed_ookla_parse_failed')];
        }

        if ($isOokla) {
            $ping = (float) ($data['ping']['latency'] ?? $data['ping']['jitter'] ?? 0);
            $dlBw = (float) ($data['download']['bandwidth'] ?? 0);
            $ulBw = (float) ($data['upload']['bandwidth'] ?? 0);
            $serverName = (string) ($data['server']['name'] ?? '');
            $serverLoc = (string) ($data['server']['location'] ?? '');
            $label = trim($serverName.($serverLoc !== '' ? ' — '.$serverLoc : ''));

            return [
                'ok' => true,
                'ping_ms' => round($ping, 1),
                'download_mbps' => round($dlBw * 8 / 1_000_000, 2),
                'upload_mbps' => round($ulBw * 8 / 1_000_000, 2),
                'label' => $label !== '' ? $label : null,
            ];
        }

        $ping = (float) ($data['ping'] ?? 0);
        $dl = (float) ($data['download'] ?? 0);
        $ul = (float) ($data['upload'] ?? 0);
        $server = (string) ($data['server']['name'] ?? $data['server']['host'] ?? '');

        return [
            'ok' => true,
            'ping_ms' => round($ping, 1),
            'download_mbps' => round($dl, 2),
            'upload_mbps' => round($ul, 2),
            'label' => $server !== '' ? $server : null,
        ];
    }

    /**
     * @param  array{ok: bool, ping_ms?: float, download_mbps?: float, upload_mbps?: float, label?: string, cached?: bool, error?: string}  $server
     * @param  array{ping_ms: float, download_mbps: float, upload_mbps: float}  $panel
     */
    public function storeResult(
        int $userId,
        string $clientIp,
        array $panel,
        array $server,
    ): CuriousSpeedResult {
        $serverOk = (bool) ($server['ok'] ?? false);

        $panelPing = isset($panel['ping_ms']) ? (int) round($panel['ping_ms']) : null;
        $panelDl = isset($panel['download_mbps']) ? round((float) $panel['download_mbps'], 2) : null;
        $panelUl = isset($panel['upload_mbps']) ? round((float) $panel['upload_mbps'], 2) : null;

        $srvPing = $serverOk && isset($server['ping_ms']) ? (int) round((float) $server['ping_ms']) : null;
        $srvDl = $serverOk && isset($server['download_mbps']) ? round((float) $server['download_mbps'], 2) : null;
        $srvUl = $serverOk && isset($server['upload_mbps']) ? round((float) $server['upload_mbps'], 2) : null;

        $deltaPing = ($panelPing !== null && $srvPing !== null) ? round($srvPing - $panelPing, 1) : null;
        $deltaDl = ($panelDl !== null && $srvDl !== null) ? round($srvDl - $panelDl, 2) : null;
        $deltaUl = ($panelUl !== null && $srvUl !== null) ? round($srvUl - $panelUl, 2) : null;

        $row = CuriousSpeedResult::create([
            'user_id' => $userId,
            'client_ip' => $clientIp,
            'panel_ping_ms' => $panelPing,
            'panel_download_mbps' => $panelDl,
            'panel_upload_mbps' => $panelUl,
            'server_ping_ms' => $srvPing,
            'server_download_mbps' => $srvDl,
            'server_upload_mbps' => $srvUl,
            'delta_ping_ms' => $deltaPing,
            'delta_download_mbps' => $deltaDl,
            'delta_upload_mbps' => $deltaUl,
            'server_label' => $serverOk ? ($server['label'] ?? null) : null,
            'server_from_cache' => (bool) ($server['cached'] ?? false),
            'server_error' => $serverOk ? null : Str::limit((string) ($server['error'] ?? __('curious.speed_ookla_failed')), 200),
        ]);

        $this->pruneHistory($userId, $clientIp);

        return $row;
    }

    private function pruneHistory(int $userId, string $clientIp): void
    {
        $retentionDays = max(7, (int) config('panelze.curious.ookla_history_retention_days', 90));
        CuriousSpeedResult::query()
            ->where('user_id', $userId)
            ->where('client_ip', $clientIp)
            ->where('created_at', '<', now()->subDays($retentionDays))
            ->delete();

        $maxRows = max(50, (int) config('panelze.curious.ookla_history_max_rows', 200));
        $keepIds = CuriousSpeedResult::query()
            ->where('user_id', $userId)
            ->where('client_ip', $clientIp)
            ->orderByDesc('id')
            ->limit($maxRows)
            ->pluck('id');

        if ($keepIds->isEmpty()) {
            return;
        }

        CuriousSpeedResult::query()
            ->where('user_id', $userId)
            ->where('client_ip', $clientIp)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function historyFor(int $userId, string $clientIp): array
    {
        $limit = max(5, min(50, (int) config('panelze.curious.ookla_history_limit', 30)));

        return CuriousSpeedResult::query()
            ->where('user_id', $userId)
            ->where('client_ip', $clientIp)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (CuriousSpeedResult $r) => $r->toApiArray())
            ->all();
    }
}
