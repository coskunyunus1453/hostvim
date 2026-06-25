<?php

namespace App\Jobs;

use App\Services\BindDnsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBindDnsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public int $tries = 3;

    public function handle(BindDnsService $bind): void
    {
        $result = $bind->syncReliable();
        if (! ($result['ok'] ?? false)) {
            Log::error('panelze.bind_sync_job_failed', [
                'message' => $result['message'] ?? 'unknown',
            ]);
        }
    }
}
