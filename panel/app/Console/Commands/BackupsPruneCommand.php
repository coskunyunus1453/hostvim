<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\BackupController;
use App\Models\Backup;
use App\Models\BackupSchedule;
use Illuminate\Console\Command;

class BackupsPruneCommand extends Command
{
    protected $signature = 'backups:prune {--dry-run : Sadece raporla, silme}';

    protected $description = 'Süresi dolan yedekleri ve retention sınırını aşan eski zincirleri temizler';

    public function handle(BackupController $ctrl): int
    {
        $dry = (bool) $this->option('dry-run');
        $deleted = 0;

        // 1) Plan bazlı retention: her domain+hedef için en yeni N tam-yedek zinciri kalsın.
        $schedules = BackupSchedule::query()->whereNotNull('retention_count')->get();
        foreach ($schedules as $s) {
            $keep = max(1, (int) $s->retention_count);
            $bases = Backup::query()
                ->where('user_id', $s->user_id)
                ->where('domain_id', $s->domain_id)
                ->where('level', 0)
                ->where('status', 'completed')
                ->when($s->destination_id !== null, fn ($q) => $q->where('destination_id', $s->destination_id))
                ->when($s->destination_id === null, fn ($q) => $q->whereNull('destination_id'))
                ->orderByDesc('id')
                ->get();

            foreach ($bases->slice($keep) as $base) {
                $deleted += $this->deleteChain($ctrl, (int) $base->id, $dry);
            }
        }

        // 2) Süresi dolmuş yedekler (expires_at geçmiş). Zincir güvenliği: bağımlısı olanı atla.
        $expired = Backup::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->orderByDesc('level')
            ->orderByDesc('id')
            ->get();
        foreach ($expired as $b) {
            $hasDeps = Backup::query()
                ->where('id', '!=', $b->id)
                ->where(function ($q) use ($b) {
                    $q->where('parent_backup_id', $b->id)->orWhere('base_backup_id', $b->id);
                })
                ->exists();
            if ($hasDeps) {
                continue;
            }
            $deleted += $this->deleteOne($ctrl, $b, $dry);
        }

        $this->info(($dry ? '[dry-run] ' : '').$deleted.' yedek temizlendi.');

        return self::SUCCESS;
    }

    /** Bir zinciri (base + tüm arttırımlılar) yaprak-önce siler. */
    private function deleteChain(BackupController $ctrl, int $baseId, bool $dry): int
    {
        $chain = Backup::query()
            ->where('id', $baseId)
            ->orWhere('base_backup_id', $baseId)
            ->orderByDesc('level')
            ->orderByDesc('id')
            ->get();
        $n = 0;
        foreach ($chain as $b) {
            $n += $this->deleteOne($ctrl, $b, $dry);
        }

        return $n;
    }

    private function deleteOne(BackupController $ctrl, Backup $b, bool $dry): int
    {
        if ($dry) {
            $this->line(sprintf('  - #%d domain=%s level=%d status=%s', $b->id, $b->domain_id, $b->level, $b->status));

            return 1;
        }
        $ctrl->purgeBackupArtifacts($b);
        $b->delete();

        return 1;
    }
}
