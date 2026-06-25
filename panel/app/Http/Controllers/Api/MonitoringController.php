<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\CronJobRun;
use App\Models\DeploymentRun;
use App\Models\Domain;
use App\Models\InstallerRun;
use App\Models\SslCertificate;
use App\Models\User;
use App\Services\EngineApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitoringController extends Controller
{
    public function __construct(
        private EngineApiService $engine,
    ) {}

    public function userSummary(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'domains' => $user->domains()->count(),
            'databases' => $user->databases()->count(),
            'email_accounts' => $user->emailAccounts()->count(),
            'disk_estimate_mb' => $user->databases()->sum('size_mb'),
        ]);
    }

    public function server(Request $request): JsonResponse
    {
        if (! $this->canViewServerMetrics($request->user())) {
            abort(403);
        }

        return response()->json($this->cachedServerPayload());
    }

    public function health(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'domain_id' => ['nullable', 'integer', 'exists:domains,id'],
        ]);

        $selectedDomain = $this->resolveHealthDomain($user, $validated['domain_id'] ?? null);
        $cacheKey = $this->healthCacheKey($user, $selectedDomain);

        $payload = Cache::get($cacheKey);
        if (is_array($payload)) {
            return response()->json($payload);
        }

        $stale = Cache::get($cacheKey.':stale');
        if (is_array($stale)) {
            $userId = (int) $user->id;
            $domainId = $selectedDomain ? (int) $selectedDomain->id : null;
            dispatch(function () use ($cacheKey, $userId, $domainId): void {
                try {
                    $u = User::query()->find($userId);
                    if (! $u) {
                        return;
                    }
                    $domain = $domainId ? Domain::query()->find($domainId) : null;
                    app(self::class)->storeHealthPayload($u, $domain, $cacheKey);
                } catch (\Throwable $e) {
                    Log::warning('monitoring health refresh failed', ['error' => $e->getMessage()]);
                }
            })->afterResponse();

            return response()->json($stale);
        }

        return response()->json($this->storeHealthPayload($user, $selectedDomain, $cacheKey));
    }

    /**
     * @return array{stats: array<string, mixed>, services: list<array<string, mixed>>}
     */
    private function cachedServerPayload(): array
    {
        return Cache::remember('monitoring:server:stats', 20, function (): array {
            $t0 = microtime(true);

            return [
                'stats' => $this->engine->getSystemStats(),
                'services' => $this->engine->getServices(),
                'fetched_ms' => (int) round((microtime(true) - $t0) * 1000),
            ];
        });
    }

    private function resolveHealthDomain(User $user, mixed $domainId): ?Domain
    {
        if (empty($domainId)) {
            return null;
        }
        $domainQ = Domain::query()->where('id', (int) $domainId);
        if (! $user->isAdmin()) {
            $domainQ->where('user_id', (int) $user->id);
        }
        $selectedDomain = $domainQ->first();
        if (! $selectedDomain) {
            abort(403);
        }

        return $selectedDomain;
    }

    private function healthCacheKey(User $user, ?Domain $selectedDomain): string
    {
        $scope = $user->isAdmin() ? 'admin' : 'u'.$user->id;
        $scope .= $selectedDomain ? ':d'.$selectedDomain->id : ':all';

        return 'monitoring:health:'.$scope;
    }

    /**
     * @return array<string, mixed>
     */
    public function storeHealthPayload(User $user, ?Domain $selectedDomain, string $cacheKey): array
    {
        $payload = $this->buildHealthPayload($user, $selectedDomain);
        Cache::put($cacheKey, $payload, 45);
        Cache::put($cacheKey.':stale', $payload, 600);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHealthPayload(User $user, ?Domain $selectedDomain): array
    {
        $canServer = $this->canViewServerMetrics($user);
        $server = $canServer ? $this->cachedServerPayload() : ['stats' => [], 'services' => [], 'fetched_ms' => 0];
        $stats = $server['stats'];
        $services = $server['services'];
        $responseMs = (int) ($server['fetched_ms'] ?? 0);
        $siteResponseMs = $selectedDomain ? $this->probeDomainResponseMs((string) $selectedDomain->name) : null;

        $cpu = $canServer ? (float) ($stats['cpu_usage'] ?? 0) : null;
        $ram = $canServer ? (float) ($stats['memory_percent'] ?? 0) : null;
        $disk = $canServer ? (float) ($stats['disk_percent'] ?? 0) : null;

        $score = 100.0;
        $reasons = [];
        $knownMetrics = 0;
        $totalMetrics = $canServer ? 6 : 3;

        if ($canServer) {
            $cpuPenalty = max(0.0, min(20.0, (($cpu ?? 0) - 60.0) * 0.5));
            $score -= $cpuPenalty;
            $reasons[] = [
                'key' => 'cpu',
                'ok' => $cpuPenalty < 4,
                'unknown' => false,
                'label' => $cpuPenalty < 4 ? __('monitoring.health_cpu_ok') : __('monitoring.health_cpu_high'),
                'detail' => __('monitoring.health_cpu_detail', ['value' => number_format($cpu ?? 0, 1)]),
            ];
            $knownMetrics++;

            $ramPenalty = max(0.0, min(20.0, (($ram ?? 0) - 65.0) * 0.57));
            $score -= $ramPenalty;
            $reasons[] = [
                'key' => 'ram',
                'ok' => $ramPenalty < 4,
                'unknown' => false,
                'label' => $ramPenalty < 4 ? __('monitoring.health_ram_ok') : __('monitoring.health_ram_high'),
                'detail' => __('monitoring.health_ram_detail', ['value' => number_format($ram ?? 0, 1)]),
            ];
            $knownMetrics++;

            $diskPenalty = max(0.0, min(15.0, (($disk ?? 0) - 70.0) * 0.5));
            $score -= $diskPenalty;
            $reasons[] = [
                'key' => 'disk',
                'ok' => ($disk ?? 0) < 70.0,
                'unknown' => false,
                'label' => $diskPenalty < 3 ? __('monitoring.health_disk_ok') : __('monitoring.health_disk_high'),
                'detail' => __('monitoring.health_disk_detail', ['value' => number_format($disk ?? 0, 1)]),
            ];
            $knownMetrics++;
        }

        $rtBase = $siteResponseMs ?? ($canServer ? $responseMs : null);
        if ($rtBase !== null) {
            $rtPenalty = max(0.0, min(15.0, ($rtBase - 250.0) / 35.0));
            $score -= $rtPenalty;
            $reasons[] = [
                'key' => 'rt',
                'ok' => $rtPenalty < 3,
                'unknown' => false,
                'label' => $rtPenalty < 3 ? __('monitoring.health_rt_ok') : __('monitoring.health_rt_slow'),
                'detail' => $selectedDomain
                    ? __('monitoring.health_rt_site', ['name' => $selectedDomain->name, 'ms' => $siteResponseMs ?? 0])
                    : __('monitoring.health_rt_engine', ['ms' => $responseMs]),
            ];
            $knownMetrics++;
        }

        if ($selectedDomain) {
            $inst = InstallerRun::query()->where('domain_id', (int) $selectedDomain->id)->latest('id')->limit(20)->get(['status']);
            $dep = DeploymentRun::query()->where('domain_id', (int) $selectedDomain->id)->latest('id')->limit(20)->get(['status']);
            $bak = Backup::query()->where('domain_id', (int) $selectedDomain->id)->latest('id')->limit(20)->get(['status']);
            $cron = collect();
        } elseif ($user->isAdmin()) {
            $inst = InstallerRun::query()->latest('id')->limit(20)->get(['status']);
            $dep = DeploymentRun::query()->latest('id')->limit(20)->get(['status']);
            $bak = Backup::query()->latest('id')->limit(20)->get(['status']);
            $cron = CronJobRun::query()->latest('id')->limit(20)->get(['status']);
        } else {
            $uid = (int) $user->id;
            $inst = InstallerRun::query()->where('user_id', $uid)->latest('id')->limit(20)->get(['status']);
            $dep = DeploymentRun::query()->where('user_id', $uid)->latest('id')->limit(20)->get(['status']);
            $bak = Backup::query()->where('user_id', $uid)->latest('id')->limit(20)->get(['status']);
            $cron = CronJobRun::query()->where('user_id', $uid)->latest('id')->limit(20)->get(['status']);
        }
        $statuses = $inst->pluck('status')->concat($dep->pluck('status'))->concat($bak->pluck('status'))->concat($cron->pluck('status'));
        $totalRuns = $statuses->count();
        $failed = $statuses->filter(fn ($s) => in_array(strtolower((string) $s), ['failed', 'error'], true))->count();
        $errRate = 0.0;
        if ($totalRuns > 0) {
            $errRate = ($failed / $totalRuns) * 100.0;
            $errPenalty = min(20.0, $errRate * 0.5);
            $score -= $errPenalty;
            $reasons[] = [
                'key' => 'errors',
                'ok' => $errPenalty < 4,
                'unknown' => false,
                'label' => $errPenalty < 4 ? __('monitoring.health_errors_ok') : __('monitoring.health_errors_high'),
                'detail' => __('monitoring.health_errors_detail', [
                    'failed' => $failed,
                    'total' => $totalRuns,
                    'rate' => number_format($errRate, 1),
                ]),
            ];
            $knownMetrics++;
        } else {
            $reasons[] = [
                'key' => 'errors',
                'ok' => false,
                'unknown' => true,
                'label' => __('monitoring.health_errors_unknown'),
                'detail' => __('monitoring.health_errors_no_runs'),
            ];
        }

        if ($selectedDomain) {
            $sslTotal = SslCertificate::query()->where('domain_id', (int) $selectedDomain->id)->count();
            $sslBad = SslCertificate::query()->where('domain_id', (int) $selectedDomain->id)
                ->where(function ($q) {
                    $q->where('status', '!=', 'active')
                        ->orWhereNotNull('expires_at')->where('expires_at', '<', now()->addDays(7));
                })->count();
        } elseif ($user->isAdmin()) {
            $sslTotal = SslCertificate::query()->count();
            $sslBad = SslCertificate::query()
                ->where(function ($q) {
                    $q->where('status', '!=', 'active')
                        ->orWhereNotNull('expires_at')->where('expires_at', '<', now()->addDays(7));
                })->count();
        } else {
            $sslTotal = SslCertificate::query()->whereHas('domain', fn ($q) => $q->where('user_id', (int) $user->id))->count();
            $sslBad = SslCertificate::query()->whereHas('domain', fn ($q) => $q->where('user_id', (int) $user->id))
                ->where(function ($q) {
                    $q->where('status', '!=', 'active')
                        ->orWhereNotNull('expires_at')->where('expires_at', '<', now()->addDays(7));
                })->count();
        }
        if ($sslTotal > 0) {
            $sslPenalty = min(10.0, ($sslBad / max(1, $sslTotal)) * 10.0);
            $score -= $sslPenalty;
            $reasons[] = [
                'key' => 'ssl',
                'ok' => $sslPenalty < 2,
                'unknown' => false,
                'label' => $sslPenalty < 2 ? __('monitoring.health_ssl_ok') : __('monitoring.health_ssl_risk'),
                'detail' => __('monitoring.health_ssl_detail', ['bad' => $sslBad, 'total' => $sslTotal]),
            ];
            $knownMetrics++;
        } else {
            $reasons[] = [
                'key' => 'ssl',
                'ok' => false,
                'unknown' => true,
                'label' => __('monitoring.health_ssl_unknown'),
                'detail' => __('monitoring.health_ssl_none'),
            ];
        }

        if ($canServer) {
            $serviceBy = collect($services)->keyBy(fn ($s) => strtolower((string) ($s['name'] ?? '')));
            foreach (['nginx', 'apache2'] as $svcName) {
                if ($serviceBy->has($svcName) && strtolower((string) ($serviceBy[$svcName]['status'] ?? '')) !== 'running') {
                    $score -= 8.0;
                    $reasons[] = [
                        'key' => 'svc_'.$svcName,
                        'ok' => false,
                        'label' => __('monitoring.health_svc_down', ['service' => strtoupper($svcName)]),
                        'detail' => __('monitoring.health_svc_not_running'),
                    ];
                }
            }
        }

        if ($selectedDomain && strtolower((string) $selectedDomain->status) !== 'active') {
            $score -= 12.0;
            $reasons[] = [
                'key' => 'domain_status',
                'ok' => false,
                'unknown' => false,
                'label' => __('monitoring.site_domain_inactive'),
                'detail' => (string) $selectedDomain->name,
            ];
            $knownMetrics++;
        }

        $score = max(0, min(100, (int) round($score)));
        $grade = $score >= 90 ? 'excellent' : ($score >= 75 ? 'good' : ($score >= 60 ? 'warning' : 'critical'));

        return [
            'score' => $score,
            'grade' => $grade,
            'response_ms' => $responseMs,
            'site_response_ms' => $siteResponseMs,
            'scope' => $selectedDomain ? 'domain' : 'global',
            'domain' => $selectedDomain ? [
                'id' => (int) $selectedDomain->id,
                'name' => (string) $selectedDomain->name,
                'status' => (string) ($selectedDomain->status ?? 'unknown'),
            ] : null,
            'snapshot' => [
                'cpu' => $cpu !== null ? round($cpu, 1) : null,
                'ram' => $ram !== null ? round($ram, 1) : null,
                'disk' => $disk !== null ? round($disk, 1) : null,
                'error_rate' => round($errRate, 1),
            ],
            'metrics_total' => $totalMetrics,
            'metrics_known' => $knownMetrics,
            'coverage_percent' => (int) round(($knownMetrics / max(1, $totalMetrics)) * 100),
            'reasons' => array_slice($reasons, 0, 8),
            'server_metrics_visible' => $canServer,
        ];
    }

    public function healthSites(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $limit = (int) ($validated['limit'] ?? 20);

        $scope = $user->isAdmin() ? 'admin' : 'u'.$user->id;
        $cacheKey = 'monitoring:health_sites:'.$scope.':'.$limit;

        $payload = Cache::remember($cacheKey, 30, function () use ($user, $limit): array {
            $baseQ = Domain::query()->select(['id', 'name', 'status', 'user_id'])->orderBy('id', 'desc');
            if (! $user->isAdmin()) {
                $baseQ->where('user_id', (int) $user->id);
            }
            $domains = $baseQ->limit($limit)->get();
            $domainIds = $domains->pluck('id')->map(fn ($id) => (int) $id)->all();

            $sslByDomain = [];
            $installerByDomain = [];
            $deployByDomain = [];
            $backupByDomain = [];

            if ($domainIds !== []) {
                foreach (SslCertificate::query()
                    ->whereIn('domain_id', $domainIds)
                    ->orderByDesc('id')
                    ->get(['domain_id', 'status', 'expires_at']) as $ssl) {
                    $did = (int) $ssl->domain_id;
                    if (! isset($sslByDomain[$did])) {
                        $sslByDomain[$did] = $ssl;
                    }
                }

                $installerByDomain = $this->latestRunStatusesByDomain(InstallerRun::class, $domainIds, 8);
                $deployByDomain = $this->latestRunStatusesByDomain(DeploymentRun::class, $domainIds, 8);
                $backupByDomain = $this->latestRunStatusesByDomain(Backup::class, $domainIds, 8);
            }

            $items = $domains->map(function (Domain $d) use ($sslByDomain, $installerByDomain, $deployByDomain, $backupByDomain) {
                $score = 100.0;
                $reasons = [];
                $did = (int) $d->id;

                if (strtolower((string) $d->status) !== 'active') {
                    $score -= 30;
                    $reasons[] = __('monitoring.site_domain_inactive');
                }

                $ssl = $sslByDomain[$did] ?? null;
                if (! $ssl) {
                    $score -= 10;
                    $reasons[] = __('monitoring.site_ssl_missing');
                } elseif ((string) $ssl->status !== 'active') {
                    $score -= 20;
                    $reasons[] = __('monitoring.site_ssl_inactive');
                } elseif ($ssl->expires_at !== null && $ssl->expires_at->lt(now()->addDays(7))) {
                    $score -= 15;
                    $reasons[] = __('monitoring.site_ssl_expiring');
                }

                $runs = collect($installerByDomain[$did] ?? [])
                    ->concat($deployByDomain[$did] ?? [])
                    ->concat($backupByDomain[$did] ?? []);

                $total = $runs->count();
                $failed = $runs->filter(fn ($s) => in_array(strtolower((string) $s), ['failed', 'error'], true))->count();
                if ($total > 0) {
                    $errRate = ($failed / $total) * 100.0;
                    $pen = min(30.0, $errRate * 0.5);
                    $score -= $pen;
                    if ($pen >= 6) {
                        $reasons[] = __('monitoring.site_job_errors', ['failed' => $failed, 'total' => $total]);
                    }
                }

                $score = max(0, min(100, (int) round($score)));
                $grade = $score >= 90 ? 'excellent' : ($score >= 75 ? 'good' : ($score >= 60 ? 'warning' : 'critical'));

                return [
                    'domain_id' => $did,
                    'name' => (string) $d->name,
                    'score' => $score,
                    'grade' => $grade,
                    'reasons' => array_slice($reasons, 0, 3),
                ];
            })->values();

            return [
                'items' => $items,
                'limit' => $limit,
            ];
        });

        return response()->json($payload);
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<int, int>  $domainIds
     * @return array<int, list<string>>
     */
    private function latestRunStatusesByDomain(string $modelClass, array $domainIds, int $perDomain): array
    {
        $grouped = [];
        $rows = $modelClass::query()
            ->whereIn('domain_id', $domainIds)
            ->orderByDesc('id')
            ->limit(max(100, count($domainIds) * $perDomain * 3))
            ->get(['domain_id', 'status']);

        foreach ($rows as $row) {
            $did = (int) $row->domain_id;
            if (! isset($grouped[$did])) {
                $grouped[$did] = [];
            }
            if (count($grouped[$did]) < $perDomain) {
                $grouped[$did][] = (string) $row->status;
            }
        }

        return $grouped;
    }

    private function probeDomainResponseMs(string $domain): ?int
    {
        $domain = trim($domain);
        if ($domain === '') {
            return null;
        }
        foreach ([443, 80] as $port) {
            $errno = 0;
            $errstr = '';
            $t0 = microtime(true);
            $sock = @fsockopen($domain, $port, $errno, $errstr, 0.8);
            $ms = (int) round((microtime(true) - $t0) * 1000);
            if (is_resource($sock)) {
                fclose($sock);
                return $ms;
            }
        }

        return null;
    }

    private function canViewServerMetrics(User $user): bool
    {
        return $user->isAdmin() || $user->can('monitoring:server');
    }
}
