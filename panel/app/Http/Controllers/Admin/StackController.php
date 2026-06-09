<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunStackInstallJob;
use App\Models\StackInstallRun;
use App\Services\EngineApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StackController extends Controller
{
    public function modules(EngineApiService $engine): JsonResponse
    {
        return response()->json(['modules' => $engine->getStackModules()]);
    }

    public function install(Request $request, EngineApiService $engine): JsonResponse
    {
        $validated = $request->validate([
            'bundle_id' => 'required|string|max:120',
        ]);

        return $this->queueBundleInstall($request, $engine, $validated['bundle_id']);
    }

    public function retryRun(Request $request, StackInstallRun $stackInstallRun, EngineApiService $engine): JsonResponse
    {
        if ((int) $stackInstallRun->user_id !== (int) $request->user()->id) {
            abort(403);
        }
        if (! in_array($stackInstallRun->status, ['failed', 'cancelled'], true)) {
            return response()->json(['message' => 'Yalnızca başarısız veya iptal edilmiş kurulumlar yeniden denenebilir.'], 422);
        }

        return $this->queueBundleInstall($request, $engine, (string) $stackInstallRun->bundle_id, true);
    }

    private function queueBundleInstall(Request $request, EngineApiService $engine, string $bundleId, bool $isRetry = false): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $this->markStaleRunningInstalls();

        $active = StackInstallRun::query()
            ->where('user_id', $userId)
            ->where('bundle_id', $bundleId)
            ->whereIn('status', ['queued', 'running'])
            ->exists();
        if ($active) {
            return response()->json(['message' => 'Bu paket için zaten bir kurulum sürüyor. Bitmesini bekleyin veya iptal edin.'], 422);
        }

        $run = StackInstallRun::query()->create([
            'user_id' => $userId,
            'bundle_id' => $bundleId,
            'status' => 'queued',
            'progress' => 0,
            'cancel_requested' => false,
            'message' => $isRetry ? 'Yeniden kurulum kuyruğa alındı' : 'Kurulum kuyruğa alındı',
        ]);

        $isSyncQueue = (string) config('queue.default', 'sync') === 'sync';
        if ($isSyncQueue) {
            app()->call([new RunStackInstallJob($run->id, $bundleId), 'handle']);
            $run->refresh();

            return response()->json([
                'message' => $run->status === 'success' ? 'Kurulum tamamlandı' : ($run->message ?: 'Kurulum bitti'),
                'run_id' => $run->id,
                'background' => false,
            ], $run->status === 'success' ? 200 : 422);
        }

        RunStackInstallJob::dispatch($run->id, $bundleId)->afterResponse();

        return response()->json([
            'message' => $isRetry ? 'Yeniden kurulum arka planda başlatıldı' : 'Kurulum arka planda başlatıldı',
            'run_id' => $run->id,
            'background' => true,
        ], 202);
    }

    public function runs(Request $request): JsonResponse
    {
        $this->markStaleRunningInstalls();

        $rows = StackInstallRun::query()
            ->where('user_id', (int) $request->user()->id)
            ->latest('id')
            ->limit(20)
            ->get(['id', 'bundle_id', 'status', 'progress', 'cancel_requested', 'message', 'created_at', 'started_at', 'finished_at']);
        return response()->json(['runs' => $rows]);
    }

    public function showRun(Request $request, StackInstallRun $stackInstallRun): JsonResponse
    {
        if ((int) $stackInstallRun->user_id !== (int) $request->user()->id) {
            abort(403);
        }
        $this->markStaleRunningInstalls();
        $stackInstallRun->refresh();

        return response()->json(['run' => $stackInstallRun]);
    }

    public function cancelRun(Request $request, StackInstallRun $stackInstallRun): JsonResponse
    {
        if ((int) $stackInstallRun->user_id !== (int) $request->user()->id) {
            abort(403);
        }
        if (in_array($stackInstallRun->status, ['success', 'failed', 'cancelled'], true)) {
            return response()->json(['message' => 'Bu işlem zaten tamamlandı.'], 422);
        }

        if ($stackInstallRun->status === 'queued') {
            $stackInstallRun->status = 'cancelled';
            $stackInstallRun->message = 'Kurulum iptal edildi (kuyrukta).';
            $stackInstallRun->progress = 0;
            $stackInstallRun->finished_at = now();
            $stackInstallRun->cancel_requested = true;
            $stackInstallRun->save();
            return response()->json(['message' => 'Kurulum kuyruğu iptal edildi.']);
        }

        $stackInstallRun->cancel_requested = true;
        $stackInstallRun->message = 'İptal talebi alındı. İşlem mevcut adımı bitirince duracaktır.';
        $stackInstallRun->save();
        return response()->json(['message' => 'İptal talebi alındı.']);
    }

    private function markStaleRunningInstalls(): void
    {
        $minutes = max(5, (int) config('hostvim.stack_install_timeout', 1800) / 60 + 5);
        $cutoff = now()->subMinutes($minutes);

        StackInstallRun::query()
            ->where('status', 'running')
            ->where(function ($q) use ($cutoff) {
                $q->where('started_at', '<', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNull('started_at')->where('created_at', '<', $cutoff);
                    });
            })
            ->update([
                'status' => 'failed',
                'progress' => 100,
                'message' => 'Kurulum zaman aşımına uğradı (worker kesildi). «Yeniden kur» ile tekrar deneyin.',
                'finished_at' => now(),
            ]);
    }
}
