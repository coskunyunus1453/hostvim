<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\BackupDestination;
use App\Models\BackupSchedule;
use App\Models\Domain;
use App\Models\PanelSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Merkezi (şirket yönetimli) otomatik yedekleme.
 *
 * Amaç: Tüm shared hosting sitelerini (Domain kayıtları — VPS/VDS hariç) HER GÜN
 * şirketin kendi Google Drive havuz hesaplarına yedeklemek; siteleri hesaplara
 * round-robin dağıtmak; yeni eklenen siteleri otomatik kapsamak.
 *
 * Yapı: Her hosting domaini için "is_managed" bir BackupSchedule oluşturulur/güncellenir.
 * Böylece mevcut motor (backups:run-due, RunBackupJob, artımlı arşiv, retention/prune,
 * reconcile) aynen kullanılır. Bu servis sadece bu zamanlamaları üretir/senkronlar.
 */
class ManagedBackupService
{
    private const PREFIX = 'managed_backup.';

    /** @var array<string, mixed> */
    private const DEFAULTS = [
        'enabled' => false,
        'hour' => 3,
        'retention_count' => 7,
        'full_interval_days' => 7,
        'notify_email' => '',
        'folder_name' => 'HostVim Managed Backups',
    ];

    /**
     * Ayarları döndürür (varsayılanlarla birleştirilmiş).
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $rows = PanelSetting::query()
            ->where('key', 'like', self::PREFIX.'%')
            ->pluck('value', 'key');

        $out = self::DEFAULTS;
        foreach ($out as $k => $default) {
            $full = self::PREFIX.$k;
            if (! isset($rows[$full])) {
                continue;
            }
            $val = $rows[$full];
            $out[$k] = match ($k) {
                'enabled' => (bool) ((int) $val),
                'hour', 'retention_count', 'full_interval_days' => (int) $val,
                default => (string) $val,
            };
        }
        $out['hour'] = max(0, min(23, (int) $out['hour']));
        $out['retention_count'] = max(1, min(100, (int) $out['retention_count']));
        $out['full_interval_days'] = max(1, min(365, (int) $out['full_interval_days']));

        return $out;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateSettings(array $data): array
    {
        foreach (['enabled', 'hour', 'retention_count', 'full_interval_days', 'notify_email', 'folder_name'] as $k) {
            if (! array_key_exists($k, $data)) {
                continue;
            }
            $value = match ($k) {
                'enabled' => ((bool) $data[$k]) ? '1' : '0',
                'hour', 'retention_count', 'full_interval_days' => (string) (int) $data[$k],
                default => (string) $data[$k],
            };
            PanelSetting::query()->updateOrCreate(['key' => self::PREFIX.$k], ['value' => $value]);
        }

        return $this->settings();
    }

    /**
     * Şirket havuzundaki aktif Google Drive hesapları.
     *
     * @return \Illuminate\Support\Collection<int, BackupDestination>
     */
    public function pool()
    {
        return BackupDestination::query()
            ->system()
            ->where('driver', 'google_drive')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * Cron ifadesi (günlük, ayarlanan saatte).
     */
    public function cronExpression(): string
    {
        return sprintf('%d %d * * *', $this->minuteJitter(), $this->settings()['hour']);
    }

    /**
     * Tüm siteler aynı dakikada başlamasın diye sabit küçük bir dakika (0). İleride
     * site başına jitter eklenebilir; şimdilik run-due her dakika baktığı için 0 yeterli.
     */
    private function minuteJitter(): int
    {
        return 0;
    }

    /**
     * Merkezi zamanlamaları oluşturur/senkronlar.
     *
     * @return array<string, mixed>
     */
    public function provision(): array
    {
        $s = $this->settings();

        if (! $s['enabled']) {
            $disabled = BackupSchedule::query()->where('is_managed', true)->where('enabled', true)->update(['enabled' => false]);

            return ['enabled' => false, 'disabled' => $disabled, 'created' => 0, 'updated' => 0];
        }

        $pool = $this->pool();
        if ($pool->isEmpty()) {
            return ['enabled' => true, 'error' => 'no_pool', 'created' => 0, 'updated' => 0];
        }

        $poolIds = $pool->pluck('id')->all();
        $cron = $this->cronExpression();

        $domains = Domain::query()->where('status', 'active')->get(['id', 'user_id', 'name']);
        $domainIds = $domains->pluck('id')->all();

        $existing = BackupSchedule::query()->where('is_managed', true)->get()->keyBy('domain_id');

        // Havuz hesabı başına mevcut yük (round-robin dağıtım için).
        $load = array_fill_keys($poolIds, 0);
        foreach ($existing as $sched) {
            if (isset($load[$sched->destination_id])) {
                $load[$sched->destination_id]++;
            }
        }

        $created = 0;
        $updated = 0;

        foreach ($domains as $domain) {
            $sched = $existing->get($domain->id);

            if ($sched) {
                $update = [
                    'type' => 'incremental',
                    'full_interval_days' => $s['full_interval_days'],
                    'retention_count' => $s['retention_count'],
                    'schedule' => $cron,
                    'enabled' => true,
                ];
                // Hedef havuzdan silinmişse yeniden ata (en az yüklü hesaba).
                if (! in_array($sched->destination_id, $poolIds, true)) {
                    $dest = $this->leastLoaded($load);
                    $update['destination_id'] = $dest;
                    $load[$dest]++;
                }
                $sched->update($update);
                $updated++;

                continue;
            }

            $dest = $this->leastLoaded($load);
            $load[$dest]++;
            BackupSchedule::create([
                'user_id' => $domain->user_id,
                'domain_id' => $domain->id,
                'destination_id' => $dest,
                'type' => 'incremental',
                'full_interval_days' => $s['full_interval_days'],
                'retention_count' => $s['retention_count'],
                'schedule' => $cron,
                'enabled' => true,
                'is_managed' => true,
            ]);
            $created++;
        }

        // Artık aktif olmayan/silinmiş domainlerin merkezi zamanlamalarını pasifleştir.
        $stale = 0;
        if ($domainIds !== []) {
            $stale = BackupSchedule::query()
                ->where('is_managed', true)
                ->whereNotIn('domain_id', $domainIds)
                ->where('enabled', true)
                ->update(['enabled' => false]);
        }

        return [
            'enabled' => true,
            'pool_accounts' => count($poolIds),
            'domains' => count($domainIds),
            'created' => $created,
            'updated' => $updated,
            'disabled_stale' => $stale,
        ];
    }

    /**
     * @param  array<int, int>  $load
     */
    private function leastLoaded(array $load): int
    {
        asort($load);

        return (int) array_key_first($load);
    }

    /**
     * Son 24 saatte başarısız olan (merkezi + müşteri) yedekler.
     *
     * @return \Illuminate\Support\Collection<int, Backup>
     */
    public function recentFailures(int $hours = 24)
    {
        return Backup::query()
            ->where('status', 'failed')
            ->where('updated_at', '>=', now()->subHours($hours))
            ->with('domain:id,name')
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get();
    }

    /**
     * Başarısızlık bildirimi alıcıları: ayarlanan e-posta(lar) yoksa admin kullanıcılar.
     *
     * @return list<string>
     */
    public function notifyRecipients(): array
    {
        $raw = trim((string) $this->settings()['notify_email']);
        if ($raw !== '') {
            $list = array_filter(array_map('trim', preg_split('/[,;\s]+/', $raw) ?: []), static fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL));

            return array_values(array_unique($list));
        }

        try {
            return User::role('admin')->pluck('email')->filter()->values()->all();
        } catch (\Throwable) {
            return array_values(array_filter([(string) config('mail.from.address')]));
        }
    }

    /**
     * Admin arayüzü için durum özeti.
     *
     * @return array<string, mixed>
     */
    public function statusPayload(): array
    {
        $pool = $this->pool()->map(function (BackupDestination $d) {
            $cfg = (array) ($d->config ?? []);
            $managedCount = BackupSchedule::query()->where('is_managed', true)->where('destination_id', $d->id)->count();

            return [
                'id' => $d->id,
                'name' => $d->name,
                'email' => $cfg['email'] ?? null,
                'folder_name' => $cfg['folder_name'] ?? null,
                'is_active' => $d->is_active,
                'assigned_sites' => $managedCount,
            ];
        })->values();

        $managedTotal = BackupSchedule::query()->where('is_managed', true)->count();
        $managedEnabled = BackupSchedule::query()->where('is_managed', true)->where('enabled', true)->count();
        $lastRun = BackupSchedule::query()->where('is_managed', true)->max('last_run_at');
        $failures24 = $this->recentFailures(24);

        return [
            'settings' => $this->settings(),
            'configured' => app(GoogleDriveService::class)->isConfigured(),
            'pool' => $pool,
            'pool_count' => $pool->count(),
            'managed_schedules' => $managedTotal,
            'managed_enabled' => $managedEnabled,
            'active_domains' => Domain::query()->where('status', 'active')->count(),
            'last_run_at' => $lastRun,
            'failures_24h' => $failures24->take(50)->map(fn (Backup $b) => [
                'id' => $b->id,
                'domain' => $b->domain?->name,
                'type' => $b->type,
                'updated_at' => $b->updated_at,
            ])->values(),
            'failures_24h_count' => $failures24->count(),
        ];
    }
}
