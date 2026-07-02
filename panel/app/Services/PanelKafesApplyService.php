<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * PanelKafes izolasyonunu paket bazlı CPU/RAM limitleriyle uygular ve tutarlılığı onarır.
 */
class PanelKafesApplyService
{
    public function __construct(
        private EngineApiService $engine,
    ) {}

    /**
     * @return array{0:int,1:int} [cpuPercent, memoryMB] — 0 = engine global varsayılanı
     */
    public function limitsForUser(?User $user): array
    {
        if (! $user) {
            return [0, 0];
        }
        $pkg = $user->hostingPackage()->first();
        if (! $pkg) {
            return [0, 0];
        }

        return [
            max(0, (int) ($pkg->cpu_limit ?? 0)),
            max(0, (int) ($pkg->memory_limit_mb ?? 0)),
        ];
    }

    /**
     * @return array{0:int,1:int}
     */
    public function limitsForDomain(Domain $domain): array
    {
        return $this->limitsForUser($domain->user()->first());
    }

    /**
     * @return array{message?: string, cage_user?: string, status?: array<string, mixed>, error?: string}
     */
    public function applySite(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $row = Domain::query()->where('name', $domain)->first();
        [$cpu, $mem] = $row ? $this->limitsForDomain($row) : [0, 0];

        return $this->engine->applyPanelKafesSite($domain, $cpu, $mem);
    }

    /**
     * Tüm aktif sitelere sahip paketin CPU/RAM limitlerini uygular.
     *
     * @return array{ok: int, failed: int, results: list<array<string, mixed>>}
     */
    public function applyAllActive(): array
    {
        $domains = Domain::query()->where('status', 'active')->get();
        $ok = 0;
        $failed = 0;
        $results = [];

        foreach ($domains as $domain) {
            [$cpu, $mem] = $this->limitsForDomain($domain);
            try {
                $result = $this->engine->applyPanelKafesSite($domain->name, $cpu, $mem);
                if (! empty($result['error'])) {
                    throw new \RuntimeException((string) $result['error']);
                }
                $ok++;
                $results[] = [
                    'domain' => $domain->name,
                    'ok' => true,
                    'cpu' => $cpu,
                    'memory_mb' => $mem,
                ];
            } catch (\Throwable $e) {
                $failed++;
                $results[] = [
                    'domain' => $domain->name,
                    'ok' => false,
                    'error' => $e->getMessage(),
                ];
                Log::warning('panelkafes.apply_all.site_failed', [
                    'domain' => $domain->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['ok' => $ok, 'failed' => $failed, 'results' => $results];
    }

    /**
     * systemd/FPM tutarlılık onarımı + eksik cage servislerini paket limitleriyle tamamlar.
     */
    public function reconcile(): array
    {
        $helper = '/usr/local/sbin/panelze-site-cage';
        $output = '';
        $exitCode = 1;

        try {
            $proc = Process::fromShellCommandline('sudo -n '.escapeshellarg($helper).' reconcile 2>&1');
            $proc->setTimeout(120);
            $proc->run();
            $output = trim($proc->getOutput()."\n".$proc->getErrorOutput());
            $exitCode = $proc->getExitCode() ?? 1;
        } catch (\Throwable $e) {
            Log::warning('panelkafes.reconcile.helper_failed', ['error' => $e->getMessage()]);

            return [
                'helper_ok' => false,
                'helper_output' => $e->getMessage(),
                'repaired' => 0,
                'failed' => 0,
            ];
        }

        $repaired = 0;
        $failed = 0;

        foreach (Domain::query()->where('status', 'active')->get() as $domain) {
            if ($this->hasCageService($domain->name)) {
                continue;
            }
            try {
                $res = $this->applySite($domain->name);
                if (! empty($res['error'])) {
                    throw new \RuntimeException((string) $res['error']);
                }
                $repaired++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('panelkafes.reconcile.apply_missing', [
                    'domain' => $domain->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'helper_ok' => $exitCode === 0,
            'helper_output' => $output,
            'repaired' => $repaired,
            'failed' => $failed,
        ];
    }

    private function hasCageService(string $domain): bool
    {
        $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', $domain) ?? '');
        $slug = substr($slug, 0, 48);

        return is_file("/etc/systemd/system/panelze-fpm-{$slug}.service");
    }
}
