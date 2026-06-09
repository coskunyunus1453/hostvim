<?php

namespace App\Jobs;

use App\Models\StackInstallRun;
use App\Services\EngineApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunStackInstallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** mail-stack-webmail apt kurulumu uzun sürebilir */
    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        private readonly int $runId,
        private readonly string $bundleId
    ) {}

    public function handle(EngineApiService $engine): void
    {
        $run = StackInstallRun::query()->find($this->runId);
        if (! $run) {
            return;
        }
        if ($run->status === 'cancelled' || $run->cancel_requested) {
            $run->status = 'cancelled';
            $run->message = 'Kurulum iptal edildi.';
            $run->progress = 0;
            $run->finished_at = now();
            $run->save();
            return;
        }

        $run->status = 'running';
        $run->progress = 50;
        $run->started_at = now();
        $run->message = 'Kurulum sürüyor...';
        $run->save();

        $res = $engine->installStackBundle($this->bundleId);
        if (! empty($res['error'])) {
            if ($run->cancel_requested) {
                $run->status = 'cancelled';
                $run->message = 'Kurulum iptal edildi.';
                $run->progress = 0;
                $run->finished_at = now();
                $run->save();
                return;
            }
            $run->status = 'failed';
            $run->message = (string) $res['error'];
            $run->output = is_string($res['output'] ?? null) ? $res['output'] : null;
            $run->progress = 100;
            $run->finished_at = now();
            $run->save();
            return;
        }

        $run->status = 'success';
        $run->message = 'Kurulum tamamlandı';
        $run->output = is_string($res['output'] ?? null) ? $res['output'] : json_encode($res, JSON_UNESCAPED_UNICODE);
        $run->progress = 100;
        $run->finished_at = now();
        $run->save();
    }

    public function failed(\Throwable $e): void
    {
        $run = StackInstallRun::query()->find($this->runId);
        if (! $run || in_array($run->status, ['success', 'failed', 'cancelled'], true)) {
            return;
        }
        $run->status = 'failed';
        $run->progress = 100;
        $run->message = 'Kurulum zaman aşımına uğradı veya worker hatası: '.$e->getMessage();
        $run->finished_at = now();
        $run->save();
    }
}
