<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesUserDomain;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\SiteStackAlert;
use App\Services\SiteStackAdvisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteStackController extends Controller
{
    use AuthorizesUserDomain;

    public function __construct(
        private SiteStackAdvisor $advisor,
    ) {}

    public function scan(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $report = $this->advisor->scan($domain);
        if (! empty($report['error'])) {
            return response()->json([
                'message' => (string) $report['error'],
                'needs_reprovision' => (bool) ($report['needs_reprovision'] ?? false),
                'engine_error' => $report['engine_error'] ?? null,
            ], 422);
        }

        return response()->json($report);
    }

    public function fix(Request $request, Domain $domain): JsonResponse
    {
        if (! $this->userOwnsDomain($request, $domain)) {
            abort(403);
        }

        $validated = $request->validate([
            'fix_ids' => ['nullable', 'array'],
            'fix_ids.*' => ['string', 'max:64'],
        ]);

        $fixIds = array_values(array_filter(array_map('strval', $validated['fix_ids'] ?? [])));
        $result = $this->advisor->applyFixes($domain, $fixIds);

        if (! empty($result['error']) && empty($result['applied'])) {
            return response()->json(['message' => (string) $result['error']], 422);
        }

        return response()->json($result);
    }

    public function alerts(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $rows = SiteStackAlert::query()
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->latest('id')
            ->limit(30)
            ->get();

        $items = [];
        foreach ($rows as $alert) {
            $domain = Domain::query()->find($alert->domain_id);
            if (! $domain) {
                $alert->update(['status' => 'dismissed', 'dismissed_at' => now()]);

                continue;
            }
            $report = $this->advisor->scan($domain);
            $items[] = [
                'id' => $alert->id,
                'domain_id' => $alert->domain_id,
                'domain_name' => $alert->domain_name,
                'profile' => $alert->profile,
                'severity' => $alert->severity,
                'issue_count' => $alert->issue_count,
                'created_at' => optional($alert->created_at)->toIso8601String(),
                'scan' => $report['scan'] ?? null,
                'issues' => $report['issues'] ?? [],
                'scan_error' => $report['error'] ?? null,
            ];
        }

        return response()->json(['items' => $items]);
    }

    public function dismissAlert(Request $request, SiteStackAlert $alert): JsonResponse
    {
        if ((int) $alert->user_id !== (int) $request->user()->id) {
            abort(403);
        }
        $alert->update(['status' => 'dismissed', 'dismissed_at' => now()]);

        return response()->json(['message' => __('domains.stack_alert_dismissed')]);
    }
}
