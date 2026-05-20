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

        return PanelUpdateRun::query()
            ->whereIn('status', ['queued', 'running'])
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function statusPayload(): array
    {
        $current = (string) config('hostvim.version', '0.0.0');
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
            'hub_configured' => rtrim((string) config('hostvim.updates.hub_url', config('hostvim.license_server', '')), '/') !== '',
        ];
    }

    public function notifyIfNewRelease(): void
    {
        $hub = $this->hub->checkForUpdate();
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
        return storage_path('framework/hostvim-updating');
    }

    private function dismissCacheKey(): string
    {
        return 'hostvim:panel-update:dismissed';
    }
}
