<?php

namespace App\Console\Commands;

use App\Mail\DiskQuotaRestoredMail;
use App\Mail\DiskQuotaSuspendedMail;
use App\Mail\DiskQuotaWarningMail;
use App\Mail\InodeQuotaRestoredMail;
use App\Mail\InodeQuotaSuspendedMail;
use App\Mail\InodeQuotaWarningMail;
use App\Models\SystemAlert;
use App\Models\User;
use App\Services\DomainService;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Günlük disk kotası denetimi.
 *
 * İşletim sistemi düzeyinde (ext4 root) gerçek quota açık olmadığından, paket disk
 * limitini bu günlük tarama zorlar: her müşterinin tüm sitelerinin toplam disk
 * kullanımını engine'den okur, paket limitiyle karşılaştırır ve
 *   - >= warn_percent  → uyarı (admin uyarısı; askı yok)
 *   - > %100 ve grace_days boyunca aşımda → siteleri otomatik askıya alır
 *   - tekrar limit altına inince → bu sistemce askıya alınmış siteleri geri açar
 *
 * Grace/askı durumu cache'te tutulur (DB şeması değiştirmeye gerek yok).
 */
class PanelzeDiskQuotaEnforceCommand extends Command
{
    protected $signature = 'panelze:disk-quota-enforce {--dry-run : Sadece raporla, askıya alma/açma yapma}';

    protected $description = 'Paket disk kotasını günlük tarar; aşan müşterileri uyarır ve grace sonrası askıya alır';

    private const OVER_SINCE_PREFIX = 'panelze:diskquota:over_since:';

    private const SUSPENDED_PREFIX = 'panelze:diskquota:suspended:';

    private const INODE_OVER_SINCE_PREFIX = 'panelze:inodequota:over_since:';

    private const INODE_SUSPENDED_PREFIX = 'panelze:inodequota:suspended:';

    private const STATE_TTL_DAYS = 30;

    public function handle(HostingQuotaService $quota, EngineApiService $engine, DomainService $domains): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $warnPercent = (int) config('panelze.limits.disk_quota_warn_percent', 90);
        $graceDays = (int) config('panelze.limits.disk_quota_grace_days', 3);
        $autoSuspend = (bool) config('panelze.limits.disk_quota_auto_suspend', true);

        $users = User::query()
            ->whereNotNull('hosting_package_id')
            ->with('hostingPackage')
            ->get();

        $warned = 0;
        $suspended = 0;
        $reactivated = 0;
        $checked = 0;

        foreach ($users as $user) {
            if ($user->isAdmin()) {
                continue;
            }
            $pkg = $user->hostingPackage;
            $limit = $quota->diskQuotaBytes($pkg);
            if ($limit === null || $limit <= 0) {
                continue; // sınırsız paket
            }

            $checked++;
            $used = $this->freshDiskUsage($user, $engine);
            $percent = $limit > 0 ? ($used / $limit) * 100 : 0;

            if ($used > $limit) {
                $overSince = $this->markOverSince($user->id);
                $daysOver = (int) floor((now()->timestamp - $overSince) / 86400);

                $this->warnAdmin($user, $used, $limit, $percent, $daysOver, 'over');

                if ($autoSuspend && $daysOver >= $graceDays) {
                    $count = $dryRun ? $this->activeDomainCount($user) : $this->suspendUserSites($user, $domains, $used, $limit);
                    if ($count > 0) {
                        $suspended += $count;
                        $this->line(sprintf('SUSPEND %s — %d site (%.1f%%, %d gün aşımda)', $user->email, $count, $percent, $daysOver));
                    }
                } else {
                    $warned++;
                    $remainingGrace = max(0, $graceDays - $daysOver);
                    if (! $dryRun) {
                        $this->notifyCustomerWarning($user, $used, $limit, $percent, true, $remainingGrace);
                    }
                    $this->line(sprintf('WARN    %s — %.1f%% (%d/%d gün grace)', $user->email, $percent, $daysOver, $graceDays));
                }

                continue;
            }

            // Limit altında: grace sayacını sıfırla, bu sistemce askıya alınmışsa geri aç.
            $this->clearOverSince($user->id);

            if ($percent >= $warnPercent) {
                $warned++;
                $this->warnAdmin($user, $used, $limit, $percent, 0, 'near');
                // "Yaklaştı" uyarısı müşteriye en fazla 3 günde bir (spam olmasın).
                if (! $dryRun && $this->throttle('near-mail-'.$user->id, 3 * 86400)) {
                    $this->notifyCustomerWarning($user, $used, $limit, $percent, false, 0);
                }
                $this->line(sprintf('NEAR    %s — %.1f%%', $user->email, $percent));
            }

            if (! $dryRun) {
                $reactivated += $this->reactivateIfQuotaSuspended($user, $domains);
            }
        }

        $this->info(sprintf(
            'Disk kotası denetimi: %d müşteri kontrol edildi, %d uyarı, %d site askıya alındı, %d site geri açıldı.%s',
            $checked,
            $warned,
            $suspended,
            $reactivated,
            $dryRun ? ' [DRY-RUN]' : ''
        ));

        // inode (dosya/dizin sayısı) kotası denetimi — disk ile aynı akış, ayrı sayaç/cache.
        [$iChecked, $iWarned, $iSuspended, $iReactivated] = $this->enforceInodeQuota($users, $quota, $engine, $domains, $dryRun);
        $this->info(sprintf(
            'inode kotası denetimi: %d müşteri kontrol edildi, %d uyarı, %d site askıya alındı, %d site geri açıldı.%s',
            $iChecked,
            $iWarned,
            $iSuspended,
            $iReactivated,
            $dryRun ? ' [DRY-RUN]' : ''
        ));

        return self::SUCCESS;
    }

    /**
     * inode kotasını disk kotasıyla birebir aynı mantıkla uygular (uyarı → grace → askı → geri açma).
     *
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @return array{0:int,1:int,2:int,3:int} [checked, warned, suspended, reactivated]
     */
    private function enforceInodeQuota($users, HostingQuotaService $quota, EngineApiService $engine, DomainService $domains, bool $dryRun): array
    {
        $warnPercent = (int) config('panelze.limits.inode_quota_warn_percent', 90);
        $graceDays = (int) config('panelze.limits.inode_quota_grace_days', 3);
        $autoSuspend = (bool) config('panelze.limits.inode_quota_auto_suspend', true);

        $checked = 0;
        $warned = 0;
        $suspended = 0;
        $reactivated = 0;

        foreach ($users as $user) {
            if ($user->isAdmin()) {
                continue;
            }
            $limit = $quota->inodeQuota($user->hostingPackage);
            if ($limit === null || $limit <= 0) {
                continue; // sınırsız inode
            }

            $checked++;
            $used = $this->freshInodeUsage($user, $engine);
            $percent = $limit > 0 ? ($used / $limit) * 100 : 0;

            if ($used > $limit) {
                $overSince = $this->markOverSince($user->id, self::INODE_OVER_SINCE_PREFIX);
                $daysOver = (int) floor((now()->timestamp - $overSince) / 86400);

                $this->warnAdminInode($user, $used, $limit, $percent, $daysOver, 'over');

                if ($autoSuspend && $daysOver >= $graceDays) {
                    $count = $dryRun ? $this->activeDomainCount($user) : $this->suspendUserSitesInode($user, $domains, $used, $limit);
                    if ($count > 0) {
                        $suspended += $count;
                        $this->line(sprintf('SUSPEND(inode) %s — %d site (%.1f%%, %d gün aşımda)', $user->email, $count, $percent, $daysOver));
                    }
                } else {
                    $warned++;
                    $remainingGrace = max(0, $graceDays - $daysOver);
                    if (! $dryRun) {
                        $this->notifyCustomerInodeWarning($user, $used, $limit, $percent, true, $remainingGrace);
                    }
                    $this->line(sprintf('WARN(inode)    %s — %.1f%% (%d/%d gün grace)', $user->email, $percent, $daysOver, $graceDays));
                }

                continue;
            }

            $this->clearOverSince($user->id, self::INODE_OVER_SINCE_PREFIX);

            if ($percent >= $warnPercent) {
                $warned++;
                $this->warnAdminInode($user, $used, $limit, $percent, 0, 'near');
                if (! $dryRun && $this->throttle('inode-near-mail-'.$user->id, 3 * 86400)) {
                    $this->notifyCustomerInodeWarning($user, $used, $limit, $percent, false, 0);
                }
                $this->line(sprintf('NEAR(inode)    %s — %.1f%%', $user->email, $percent));
            }

            if (! $dryRun) {
                $reactivated += $this->reactivateIfInodeSuspended($user, $domains);
            }
        }

        return [$checked, $warned, $suspended, $reactivated];
    }

    /** Cache'i bypass ederek güncel toplam inode (dosya/dizin) sayısını hesaplar. */
    private function freshInodeUsage(User $user, EngineApiService $engine): int
    {
        $total = 0;
        foreach ($user->domains()->cursor() as $domain) {
            try {
                $row = $engine->getSiteInodeUsage((string) $domain->name);
                if (empty($row['error'])) {
                    $total += (int) ($row['inodes'] ?? 0);
                }
            } catch (\Throwable $e) {
                Log::warning('inodequota.usage_read_failed', ['domain' => $domain->name, 'error' => $e->getMessage()]);
            }
        }

        return $total;
    }

    /** @return int askıya alınan site sayısı */
    private function suspendUserSitesInode(User $user, DomainService $domains, int $used, int $limit): int
    {
        $suspendedNames = [];
        $count = 0;
        foreach ($user->domains()->where('status', 'active')->get() as $domain) {
            try {
                $domains->setPanelStatus($domain, 'suspended');
                $suspendedNames[] = $domain->name;
                $count++;
            } catch (\Throwable $e) {
                Log::warning('inodequota.suspend_failed', ['domain' => $domain->name, 'error' => $e->getMessage()]);
            }
        }
        if ($suspendedNames !== []) {
            Cache::put(self::INODE_SUSPENDED_PREFIX.$user->id, $suspendedNames, now()->addDays(self::STATE_TTL_DAYS));
            $this->pushAlert('error', 'inode kotası: siteler askıya alındı', sprintf(
                '%s müşterisinin %d sitesi dosya (inode) sayısı aşımı nedeniyle askıya alındı: %s',
                $user->email,
                $count,
                implode(', ', $suspendedNames)
            ), 'inodequota-suspend-'.$user->id.'-'.date('Ymd'));

            $this->safeMail(fn () => Mail::to($user->email)->queue(new InodeQuotaSuspendedMail(
                (string) ($user->name ?: $user->email),
                $used,
                $limit,
                $suspendedNames,
            )));
        }

        return $count;
    }

    /** @return int geri açılan site sayısı */
    private function reactivateIfInodeSuspended(User $user, DomainService $domains): int
    {
        $key = self::INODE_SUSPENDED_PREFIX.$user->id;
        $names = Cache::get($key);
        if (! is_array($names) || $names === []) {
            return 0;
        }
        $count = 0;
        foreach ($names as $name) {
            $domain = $user->domains()->where('name', $name)->where('status', 'suspended')->first();
            if (! $domain) {
                continue;
            }
            try {
                $domains->setPanelStatus($domain, 'active');
                $count++;
            } catch (\Throwable $e) {
                Log::warning('inodequota.reactivate_failed', ['domain' => $name, 'error' => $e->getMessage()]);
            }
        }
        Cache::forget($key);
        if ($count > 0) {
            $this->pushAlert('info', 'inode kotası: siteler geri açıldı', sprintf(
                '%s müşterisi dosya sayısı sınırının altına indi; %d site otomatik geri açıldı.',
                $user->email,
                $count
            ), 'inodequota-reactivate-'.$user->id.'-'.date('Ymd'));

            $restored = array_values(array_filter($names, static fn ($n) => is_string($n)));
            $this->safeMail(fn () => Mail::to($user->email)->queue(new InodeQuotaRestoredMail(
                (string) ($user->name ?: $user->email),
                $restored,
            )));
        }

        return $count;
    }

    private function notifyCustomerInodeWarning(User $user, int $used, int $limit, float $percent, bool $over, int $remainingGraceDays): void
    {
        $this->safeMail(fn () => Mail::to($user->email)->queue(new InodeQuotaWarningMail(
            (string) ($user->name ?: $user->email),
            $used,
            $limit,
            round($percent, 1),
            $over,
            $remainingGraceDays,
        )));
    }

    private function warnAdminInode(User $user, int $used, int $limit, float $percent, int $daysOver, string $kind): void
    {
        $title = $kind === 'over' ? 'inode kotası aşıldı' : 'inode kotasına yaklaşıldı';
        $msg = sprintf(
            '%s — %.1f%% (%s / %s dosya)%s',
            $user->email,
            $percent,
            number_format($used, 0, ',', '.'),
            number_format($limit, 0, ',', '.'),
            $kind === 'over' && $daysOver > 0 ? sprintf(', %d gündür aşımda', $daysOver) : ''
        );
        $this->pushAlert($kind === 'over' ? 'error' : 'info', $title, $msg, 'inodequota-'.$kind.'-'.$user->id.'-'.date('Ymd'));
    }

    /** Cache'i bypass ederek güncel toplam disk kullanımını (bayt) hesaplar. */
    private function freshDiskUsage(User $user, EngineApiService $engine): int
    {
        $total = 0;
        foreach ($user->domains()->cursor() as $domain) {
            try {
                $row = $engine->getSiteDiskUsage((string) $domain->name);
                if (empty($row['error'])) {
                    $total += (int) ($row['bytes'] ?? 0);
                }
            } catch (\Throwable $e) {
                Log::warning('diskquota.usage_read_failed', ['domain' => $domain->name, 'error' => $e->getMessage()]);
            }
        }

        return $total;
    }

    private function markOverSince(int $userId, string $prefix = self::OVER_SINCE_PREFIX): int
    {
        $key = $prefix.$userId;
        $existing = Cache::get($key);
        if (is_numeric($existing)) {
            return (int) $existing;
        }
        $now = now()->timestamp;
        Cache::put($key, $now, now()->addDays(self::STATE_TTL_DAYS));

        return $now;
    }

    private function clearOverSince(int $userId, string $prefix = self::OVER_SINCE_PREFIX): void
    {
        Cache::forget($prefix.$userId);
    }

    private function activeDomainCount(User $user): int
    {
        return $user->domains()->where('status', 'active')->count();
    }

    /** @return int askıya alınan site sayısı */
    private function suspendUserSites(User $user, DomainService $domains, int $used, int $limit): int
    {
        $suspendedNames = [];
        $count = 0;
        foreach ($user->domains()->where('status', 'active')->get() as $domain) {
            try {
                $domains->setPanelStatus($domain, 'suspended');
                $suspendedNames[] = $domain->name;
                $count++;
            } catch (\Throwable $e) {
                Log::warning('diskquota.suspend_failed', ['domain' => $domain->name, 'error' => $e->getMessage()]);
            }
        }
        if ($suspendedNames !== []) {
            // Yalnızca kota nedeniyle askıya alınanları işaretle (geri açarken sadece bunları aç).
            Cache::put(self::SUSPENDED_PREFIX.$user->id, $suspendedNames, now()->addDays(self::STATE_TTL_DAYS));
            $this->pushAlert('error', 'Disk kotası: siteler askıya alındı', sprintf(
                '%s müşterisinin %d sitesi disk kotası aşımı nedeniyle askıya alındı: %s',
                $user->email,
                $count,
                implode(', ', $suspendedNames)
            ), 'diskquota-suspend-'.$user->id.'-'.date('Ymd'));

            $this->safeMail(fn () => Mail::to($user->email)->queue(new DiskQuotaSuspendedMail(
                (string) ($user->name ?: $user->email),
                round($used / 1048576, 1),
                round($limit / 1048576, 1),
                $suspendedNames,
            )));
        }

        return $count;
    }

    /** @return int geri açılan site sayısı */
    private function reactivateIfQuotaSuspended(User $user, DomainService $domains): int
    {
        $key = self::SUSPENDED_PREFIX.$user->id;
        $names = Cache::get($key);
        if (! is_array($names) || $names === []) {
            return 0;
        }
        $count = 0;
        foreach ($names as $name) {
            $domain = $user->domains()->where('name', $name)->where('status', 'suspended')->first();
            if (! $domain) {
                continue;
            }
            try {
                $domains->setPanelStatus($domain, 'active');
                $count++;
            } catch (\Throwable $e) {
                Log::warning('diskquota.reactivate_failed', ['domain' => $name, 'error' => $e->getMessage()]);
            }
        }
        Cache::forget($key);
        if ($count > 0) {
            $this->pushAlert('info', 'Disk kotası: siteler geri açıldı', sprintf(
                '%s müşterisi limit altına indi; %d site otomatik geri açıldı.',
                $user->email,
                $count
            ), 'diskquota-reactivate-'.$user->id.'-'.date('Ymd'));

            $restored = array_values(array_filter($names, static fn ($n) => is_string($n)));
            $this->safeMail(fn () => Mail::to($user->email)->queue(new DiskQuotaRestoredMail(
                (string) ($user->name ?: $user->email),
                $restored,
            )));
        }

        return $count;
    }

    private function notifyCustomerWarning(User $user, int $used, int $limit, float $percent, bool $over, int $remainingGraceDays): void
    {
        $this->safeMail(fn () => Mail::to($user->email)->queue(new DiskQuotaWarningMail(
            (string) ($user->name ?: $user->email),
            round($used / 1048576, 1),
            round($limit / 1048576, 1),
            round($percent, 1),
            $over,
            $remainingGraceDays,
        )));
    }

    /** @return bool true = işlem yapılabilir (pencere içinde ilk kez), false = yakın zamanda yapıldı */
    private function throttle(string $key, int $seconds): bool
    {
        $cacheKey = 'panelze:diskquota:throttle:'.$key;
        if (Cache::has($cacheKey)) {
            return false;
        }
        Cache::put($cacheKey, now()->timestamp, now()->addSeconds($seconds));

        return true;
    }

    private function safeMail(callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning('diskquota.mail_failed', ['error' => $e->getMessage()]);
        }
    }

    private function warnAdmin(User $user, int $used, int $limit, float $percent, int $daysOver, string $kind): void
    {
        $usedMb = round($used / 1048576, 1);
        $limitMb = round($limit / 1048576, 1);
        $title = $kind === 'over'
            ? 'Disk kotası aşıldı'
            : 'Disk kotasına yaklaşıldı';
        $msg = sprintf(
            '%s — %.1f%% (%s MB / %s MB)%s',
            $user->email,
            $percent,
            $usedMb,
            $limitMb,
            $kind === 'over' && $daysOver > 0 ? sprintf(', %d gündür aşımda', $daysOver) : ''
        );
        $this->pushAlert($kind === 'over' ? 'error' : 'info', $title, $msg, 'diskquota-'.$kind.'-'.$user->id.'-'.date('Ymd'));
    }

    private function pushAlert(string $level, string $title, string $message, string $dedupe): void
    {
        if (! Schema::hasTable('system_alerts')) {
            return;
        }
        if (SystemAlert::query()->where('dedupe_key', $dedupe)->exists()) {
            return;
        }
        SystemAlert::query()->create([
            'level' => $level,
            'title' => $title,
            'message' => $message,
            'path' => '/system',
            'dedupe_key' => $dedupe,
        ]);
    }
}
