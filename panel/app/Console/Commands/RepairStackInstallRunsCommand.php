<?php

namespace App\Console\Commands;

use App\Models\StackInstallRun;
use Illuminate\Console\Command;

class RepairStackInstallRunsCommand extends Command
{
    protected $signature = 'panelze:repair-stack-installs {--minutes=30 : running durumunda bu süreden uzun kayıtları failed yapar}';

    protected $description = 'Takılı kalmış stack kurulum kayıtlarını failed olarak işaretler';

    public function handle(): int
    {
        $minutes = max(5, (int) $this->option('minutes'));
        $cutoff = now()->subMinutes($minutes);

        $stale = StackInstallRun::query()
            ->where('status', 'running')
            ->where(function ($q) use ($cutoff) {
                $q->where('started_at', '<', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNull('started_at')->where('created_at', '<', $cutoff);
                    });
            })
            ->get();

        foreach ($stale as $run) {
            $run->update([
                'status' => 'failed',
                'progress' => 100,
                'message' => 'Kurulum yarım kaldı (zaman aşımı). Sunucu paketleri sayfasında «Yeniden kur» ile tekrar deneyin.',
                'finished_at' => now(),
            ]);
            $this->line("Run #{$run->id} ({$run->bundle_id}) → failed");
        }

        $this->info("Onarılan kayıt: {$stale->count()}");

        return self::SUCCESS;
    }
}
