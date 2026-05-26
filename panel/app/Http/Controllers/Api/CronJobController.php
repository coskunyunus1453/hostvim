<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\CronJob;
use App\Models\CronJobRun;
use App\Services\Cron\CronCommandParser;
use App\Services\Cron\CronJobExecutor;
use App\Services\Cron\CronScheduleHelper;
use App\Services\EngineApiService;
use App\Services\HostingQuotaService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
class CronJobController extends Controller
{
    public function __construct(
        private EngineApiService $engine,
        private HostingQuotaService $quota,
        private CronCommandParser $commandParser,
        private CronJobExecutor $executor,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $jobs = CronJob::query()
            ->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                    ->orWhere('is_system', true);
            })
            ->latest()
            ->paginate(30);

        return response()->json($jobs);
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'quota' => $this->quota->cronQuotaSummary($request->user()),
            'timezone_hint' => CronScheduleHelper::timezone(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'schedule' => ['required', 'string', 'max:80', $this->cronScheduleRule()],
                'command' => 'required|string|max:2000',
                'description' => 'nullable|string|max:255',
                'domain_id' => 'nullable|integer|exists:domains,id',
            ]);
            $domain = $this->resolveDomainForPathCheck($request, $validated['domain_id'] ?? null);
            $this->commandParser->assertValid($validated['command'], $request->user(), $domain);

            $this->quota->ensureCanCreateCronJob($request->user());

            $job = CronJob::create([
                'user_id' => $request->user()->id,
                'schedule' => $validated['schedule'],
                'command' => $validated['command'],
                'description' => $validated['description'] ?? null,
                'status' => 'active',
                'next_run_at' => CronScheduleHelper::nextRunAt($validated['schedule']),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'cron_jobs')) {
                return response()->json([
                    'message' => __('cron.database_not_ready'),
                ], 503);
            }

            throw $e;
        }

        $engine = $this->engine->engineCronCreate([
            'schedule' => $job->schedule,
            'command' => $job->command,
            'user_id' => $job->user_id,
            'panel_job_id' => $job->id,
        ]);

        if (empty($engine['error']) && isset($engine['id']) && $engine['id'] !== '') {
            $job->update(['engine_job_id' => (string) $engine['id']]);
        }

        return response()->json([
            'message' => __('cron.created'),
            'job' => $job->fresh(),
            'engine' => $engine,
        ], 201);
    }

    public function update(Request $request, CronJob $cronJob): JsonResponse
    {
        $this->assertCanAccess($request, $cronJob);
        if ($cronJob->is_system) {
            return response()->json(['message' => 'Sistem cron görevi düzenlenemez.'], 403);
        }

        $validated = $request->validate([
            'schedule' => ['required', 'string', 'max:80', $this->cronScheduleRule()],
            'command' => 'required|string|max:2000',
            'description' => 'nullable|string|max:255',
            'domain_id' => 'nullable|integer|exists:domains,id',
        ]);
        $domain = $this->resolveDomainForPathCheck($request, $validated['domain_id'] ?? null);
        $this->commandParser->assertValid($validated['command'], $request->user(), $domain);

        $eid = $cronJob->engine_job_id;
        if ($eid === null || $eid === '') {
            $eid = (string) $cronJob->id;
        }

        $engine = $this->engine->engineCronUpdate($eid, [
            'schedule' => $validated['schedule'],
            'command' => $validated['command'],
            'description' => $validated['description'] ?? '',
        ]);

        if (! empty($engine['error'])) {
            return response()->json([
                'message' => $engine['error'],
                'engine' => $engine,
            ], 502);
        }

        $cronJob->update([
            'schedule' => $validated['schedule'],
            'command' => $validated['command'],
            'description' => $validated['description'] ?? null,
            'next_run_at' => CronScheduleHelper::nextRunAt($validated['schedule']),
        ]);

        return response()->json([
            'message' => __('cron.updated'),
            'job' => $cronJob->fresh(),
        ]);
    }

    public function destroy(Request $request, CronJob $cronJob): JsonResponse
    {
        $this->assertCanAccess($request, $cronJob);
        if ($cronJob->is_system) {
            return response()->json(['message' => 'Sistem cron görevi silinemez.'], 403);
        }
        $eid = $cronJob->engine_job_id;
        if ($eid === null || $eid === '') {
            $eid = (string) $cronJob->id;
        }
        $cronJob->delete();

        return response()->json([
            'message' => __('cron.deleted'),
            'engine' => $this->engine->engineCronDelete($eid),
        ]);
    }

    public function runNow(Request $request, CronJob $cronJob): JsonResponse
    {
        $this->assertCanAccess($request, $cronJob);
        if ($cronJob->is_system && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Sistem cron görevi yalnızca yönetici tarafından çalıştırılabilir.'], 403);
        }

        try {
            $run = $this->executor->execute($cronJob, $request->user()->id);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => __('cron.run_failed'),
                'error' => $e->getMessage(),
            ], 422);
        }

        if ($run->status === 'timeout') {
            return response()->json([
                'message' => __('cron.run_timeout'),
                'run' => $run,
            ], 408);
        }

        if ($run->status !== 'success') {
            $detail = trim((string) ($run->output ?? ''));
            $message = $detail !== ''
                ? __('cron.run_failed').': '.$detail
                : __('cron.run_failed');

            return response()->json([
                'message' => $message,
                'run' => $run,
            ], 422);
        }

        return response()->json([
            'message' => __('cron.run_success'),
            'run' => $run,
        ]);
    }

    public function runs(Request $request, CronJob $cronJob): JsonResponse
    {
        $this->assertCanAccess($request, $cronJob);
        $runs = $cronJob->runs()->latest()->limit(20)->get();

        return response()->json([
            'data' => $runs,
        ]);
    }

    /**
     * @return \Closure(string, mixed, \Closure): void
     */
    private function cronScheduleRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || ! CronScheduleHelper::isValidSchedule($value)) {
                $fail(__('cron.invalid_schedule'));
            }
        };
    }

    private function assertCanAccess(Request $request, CronJob $cronJob): void
    {
        if ($cronJob->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }
    }

    private function resolveDomainForPathCheck(Request $request, mixed $domainId): ?Domain
    {
        if ($domainId === null || $domainId === '') {
            return null;
        }

        $domain = Domain::query()->find((int) $domainId);
        if ($domain === null) {
            return null;
        }

        $user = $request->user();
        if ($user->isAdmin() || $domain->user_id === $user->id) {
            return $domain;
        }

        abort(403);
    }
}
