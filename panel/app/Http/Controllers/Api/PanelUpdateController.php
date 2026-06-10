<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunPanelUpdateJob;
use App\Models\PanelUpdateRun;
use App\Services\PanelUpdateHubService;
use App\Services\PanelUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanelUpdateController extends Controller
{
    public function __construct(
        private PanelUpdateService $updates,
        private PanelUpdateHubService $hub,
    ) {}

    public function status(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json($this->updates->statusPayload());
    }

    public function check(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $payload = $this->hub->checkForUpdate();
        $this->updates->notifyIfNewRelease();

        return response()->json($this->updates->statusPayload());
    }

    public function dismiss(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'version' => ['required', 'string', 'max:32'],
        ]);
        $this->updates->dismissVersion($validated['version']);

        return response()->json(['message' => 'Bildirim kapatıldı', 'dismissed_version' => $validated['version']]);
    }

    public function apply(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ($this->updates->isUpdating()) {
            return response()->json(['message' => 'Güncelleme zaten sürüyor.'], 409);
        }

        $latest = $this->hub->latestRelease();
        if ($latest === null) {
            return response()->json(['message' => 'Uygulanacak yeni sürüm yok.'], 422);
        }

        $current = (string) config('panelze.version', '0.0.0');
        $run = $this->updates->createRun((int) $request->user()->id, $current, $latest);

        $isSync = (string) config('queue.default', 'sync') === 'sync';
        if ($isSync) {
            (new RunPanelUpdateJob($run->id))->handle($this->updates);
            $run->refresh();

            return response()->json([
                'message' => $run->status === 'success' ? 'Güncelleme tamamlandı' : 'Güncelleme başarısız',
                'run' => $run,
                'background' => false,
            ], $run->status === 'success' ? 200 : 500);
        }

        RunPanelUpdateJob::dispatch($run->id)->afterResponse();

        return response()->json([
            'message' => 'Güncelleme arka planda başlatıldı',
            'run_id' => $run->id,
            'background' => true,
        ], 202);
    }

    public function runs(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $rows = PanelUpdateRun::query()
            ->latest('id')
            ->limit(15)
            ->get(['id', 'from_version', 'to_version', 'status', 'progress', 'message', 'created_at', 'started_at', 'finished_at']);

        return response()->json(['runs' => $rows]);
    }

    public function showRun(Request $request, PanelUpdateRun $panelUpdateRun): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json(['run' => $panelUpdateRun]);
    }

    private function authorizeAdmin(Request $request): void
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }
    }
}
