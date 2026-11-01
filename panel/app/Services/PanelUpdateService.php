<?php

namespace App\Services;

use App\Models\PanelUpdateRun;
use App\Models\SystemAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class PanelUpdateService
{
    public function __construct(
        private PanelUpdateHubService $hub,
    ) {}

    public function isUpdating(): bool
    {
        if (File::exists($this->maintenanceFlagPath())) {
            return true;
        }

        $this->reconcileActiveRun();

        return PanelUpdateRun::query()
            ->whereIn('status', ['queued', 'running'])
            ->exists();
    }

    /**
     * Güncelleme betiği başarılı bitse bile job süreci (kuyruk işçisi yeniden
     * başlama, log izin hatası vb.) düşerse kayıt "running" kalıp paneli
     * kalıcı olarak "güncelleniyor" durumuna kilitliyordu. Burada kurulu
     * sürüm ile hedef sürümü karşılaştırıp kaydı kapatıyoruz.
     */
    public function reconcileActiveRun(): void
    {
        $run = PanelUpdateRun::query()
            ->whereIn('status', ['queued', 'running'])
            ->latest('id')
            ->first();

        if ($run === null) {
            return;
        }

        if (File::exists($this->maintenanceFlagPath()) || File::exists(self::LOCK_FILE)) {
            return;
        }

        $installed = $this->installedVersion();
        $target = (string) $run->to_version;

        if ($run->status === 'queued') {
            if ($run->created_at !== null && $run->created_at->lt(now()->subMinutes(10))) {
                $this->closeRun($run, 'failed', 'Güncelleme başlatılamadı (kuyruk işçisi yanıt vermedi).');
            }

            return;
        }

        $startedAt = $run->started_at ?? $run->created_at;
        $age = $startedAt?->diffInSeconds(now()) ?? 0;

        if ($age >= 90) {
            if ($target !== '' && version_compare($installed, $target, '>=')) {
                $this->closeRun($run, 'success', 'Panel '.$target.' sürümüne güncellendi. Sayfayı yenileyin.', 100);
            } else {
                $this->closeRun($run, 'failed', 'Güncelleme yarıda kesildi (kurulu sürüm: '.$installed.').');
            }
        }
    }

    private function closeRun(PanelUpdateRun $run, string $status, string $message, int $progress = 100): void
    {
        $run->status = $status;
        $run->progress = $progress;
        $run->message = $message;
        $run->finished_at = now();
        $run->save();
    }

    /**
     * `.env` içindeki kurulu sürüm (config cache eski olabilir).
     */
    public function installedVersion(): string
    {
        $envFile = base_path('.env');
        if (File::exists($envFile)) {
            $content = (string) File::get($envFile);
            if (preg_match('/^PANELZE_PANEL_VERSION=(.+)$/m', $content, $m)) {
                return trim($m[1], " \t\"'");
            }
        }

        return (string) config('panelze.version', '0.0.0');
    }

    /**
     * @return array<string, mixed>
     */
    public function statusPayload(): array
    {
        $this->reconcileActiveRun();

        $current = $this->installedVersion();
        $hub = $this->hub->checkForUpdate($current);
        $latest = $this->hub->latestRelease($hub);
        $activeRun = PanelUpdateRun::query()
            ->whereIn('status', ['queued', 'running'])
            ->latest('id')
            ->first();

        return [
            'current_version' => $current,
            'update_available' => $this->hub->updateAvailable($hub),
            'latest' => $latest,
            'updating' => $this->isUpdating(),
            'active_run_id' => $activeRun?->id,
            'dismissed_version' => Cache::get($this->dismissCacheKey()),
            'hub_error' => $this->hub->hubAuthError($hub),
            'hub_configured' => rtrim((string) config('panelze.updates.hub_url', config('panelze.license_server', '')), '/') !== '',
        ];
    }

    public function notifyIfNewRelease(?array $hubPayload = null): void
    {
        $hub = $hubPayload ?? $this->hub->checkForUpdate();
        if (! $this->hub->updateAvailable($hub)) {
            return;
        }

        $latest = $this->hub->latestRelease($hub);
        if ($latest === null) {
            return;
        }

        $version = (string) ($latest['version'] ?? '');
        if ($version === '') {
            return;
        }

        $dedupe = 'panel-update-'.$version;
        if (SystemAlert::query()->where('dedupe_key', $dedupe)->exists()) {
            return;
        }

        $title = (string) ($latest['title'] ?? 'Yeni panel sürümü');
        SystemAlert::create([
            'level' => 'info',
            'title' => $title.' (v'.$version.')',
            'message' => 'Güncelleme mevcut. Panel özetinden veya Sistem sayfasından inceleyip uygulayabilirsiniz.',
            'path' => '/system?update='.$version,
            'dedupe_key' => $dedupe,
        ]);
    }

    public function dismissVersion(string $version): void
    {
        Cache::forever($this->dismissCacheKey(), $version);
    }

    public function createRun(int $userId, string $fromVersion, array $release): PanelUpdateRun
    {
        if ($this->isUpdating()) {
            throw new \RuntimeException('Bir panel güncellemesi zaten sürüyor.');
        }

        $to = (string) ($release['version'] ?? '');
        if ($to === '') {
            throw new \InvalidArgumentException('Geçersiz sürüm bilgisi.');
        }

        return PanelUpdateRun::query()->create([
            'user_id' => $userId,
            'from_version' => $fromVersion,
            'to_version' => $to,
            'status' => 'queued',
            'progress' => 0,
            'message' => 'Güncelleme kuyruğa alındı',
            'release_payload' => $release,
        ]);
    }

    public function maintenanceFlagPath(): string
    {
        return storage_path('framework/panelze-updating');
    }

    /** panelze-panel-update betiğinin kilit dosyası */
    public const LOCK_FILE = '/var/run/panelze-panel-update.lock';

    public function lockFilePath(): string
    {
        return self::LOCK_FILE;
    }

    private function dismissCacheKey(): string
    {
        return 'panelze:panel-update:dismissed';
    }
}
