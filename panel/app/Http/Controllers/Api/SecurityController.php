<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EngineApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SecurityController extends Controller
{
    public function __construct(
        private EngineApiService $engine,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $overview = $this->cachedSecurityOverview();

        if (! empty($overview['error'])) {
            return $this->securityErrorResponse((string) $overview['error'], $overview);
        }

        return response()->json(['overview' => $overview]);
    }

    public function bootstrapDefaults(Request $request): JsonResponse
    {
        $result = $this->engine->bootstrapSecurityDefaults();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        $this->forgetSecurityOverviewCache();

        return response()->json([
            'message' => __('security.bootstrap_done'),
            'result' => $result['result'] ?? $result,
        ], 200);
    }

    public function advisor(): JsonResponse
    {
        $overview = $this->cachedSecurityOverview();
        if (! empty($overview['error'])) {
            return $this->securityErrorResponse((string) $overview['error'], $overview);
        }

        $advisor = is_array($overview['advisor'] ?? null) ? $overview['advisor'] : [];

        return response()->json([
            'score' => (int) ($advisor['score'] ?? 0),
            'items' => $advisor['items'] ?? [],
        ], 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function cachedSecurityOverview(): array
    {
        return Cache::remember('panelze:security:overview', 60, function () {
            return $this->engine->securityOverview();
        });
    }

    public function firewall(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:allow,deny',
            'protocol' => 'required|string|in:tcp,udp,icmp,any',
            'port' => ['nullable', 'string', 'max:32', 'regex:/^$|^(\d{1,5}(-\d{1,5})?)(,\d{1,5}(-\d{1,5})?)*$/'],
            'source' => ['nullable', 'string', 'max:64', 'regex:/^$|^[\d.:a-fA-F\/]+$/'],
        ]);

        $result = $this->engine->applyFirewallRule($validated);
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function toggleFail2ban(Request $request): JsonResponse
    {
        $validated = $request->validate(['enabled' => 'required|boolean']);
        $result = $this->engine->toggleFail2ban((bool) $validated['enabled']);
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function installFail2ban(): JsonResponse
    {
        $result = $this->engine->installFail2ban();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function toggleModSecurity(Request $request): JsonResponse
    {
        $validated = $request->validate(['enabled' => 'required|boolean']);
        $result = $this->engine->toggleModSecurity((bool) $validated['enabled']);
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function installModSecurity(): JsonResponse
    {
        $result = $this->engine->installModSecurity();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function toggleClamav(Request $request): JsonResponse
    {
        $validated = $request->validate(['enabled' => 'required|boolean']);
        $result = $this->engine->toggleClamav((bool) $validated['enabled']);
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function installClamav(): JsonResponse
    {
        $result = $this->engine->installClamav();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function scanClamav(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target' => 'nullable|string|max:255',
            'domain' => 'nullable|string|max:255',
        ]);
        $result = $this->engine->runClamavScan(
            $validated['target'] ?? null,
            $validated['domain'] ?? null
        );
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function quarantineClamav(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'paths' => 'required|array|min:1|max:50',
            'paths.*' => 'required|string|max:4096',
        ]);
        /** @var list<string> $paths */
        $paths = $validated['paths'];
        $result = $this->engine->quarantineClamavFiles($paths);
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return response()->json(['result' => $result], 200);
    }

    public function scanMaldet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target' => 'nullable|string|max:255',
            'domain' => 'nullable|string|max:255',
        ]);
        $result = $this->engine->runMaldetScan(
            $validated['target'] ?? null,
            $validated['domain'] ?? null
        );
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function updateFail2banJail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bantime' => 'required|integer|min:60|max:604800',
            'findtime' => 'required|integer|min:60|max:604800',
            'maxretry' => 'required|integer|min:1|max:20',
        ]);

        $result = $this->engine->updateFail2banJail(
            (int) $validated['bantime'],
            (int) $validated['findtime'],
            (int) $validated['maxretry']
        );
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function reconcileMailState(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dry_run' => 'sometimes|boolean',
            'confirm' => 'nullable|string|max:128',
        ]);
        $dryRun = array_key_exists('dry_run', $validated) ? (bool) $validated['dry_run'] : true;
        $confirm = isset($validated['confirm']) ? (string) $validated['confirm'] : null;
        $result = $this->engine->reconcileMailState($dryRun, $confirm);
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return response()->json(['result' => $result], 200);
    }

    public function fimStatus(Request $request): JsonResponse
    {
        $result = $this->engine->getFimStatus();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return response()->json(['result' => $result], 200);
    }

    public function createFimBaseline(): JsonResponse
    {
        $result = $this->engine->createFimBaseline();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function runFimScan(): JsonResponse
    {
        $result = $this->engine->runFimScan();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function alerts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:500',
        ]);
        $limit = (int) ($validated['limit'] ?? 50);
        $result = $this->engine->listSecurityAlerts($limit);
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return response()->json(['result' => $result], 200);
    }

    public function intelPolicy(): JsonResponse
    {
        $policy = $this->engine->getSecurityIntelPolicy();
        if (! empty($policy['error'])) {
            return $this->securityErrorResponse($policy['error'], $policy);
        }

        return response()->json($policy, 200);
    }

    public function updateIntelPolicy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => 'required|string|in:observe,enforce',
            'countries_allow' => 'sometimes|array|max:100',
            'countries_allow.*' => 'string|size:2',
            'countries_deny' => 'sometimes|array|max:100',
            'countries_deny.*' => 'string|size:2',
            'asn_allow' => 'sometimes|array|max:200',
            'asn_allow.*' => 'integer|min:1|max:429496729',
            'asn_deny' => 'sometimes|array|max:200',
            'asn_deny.*' => 'integer|min:1|max:429496729',
            'min_risk_score' => 'sometimes|integer|min:0|max:100',
            'panel_allowlist' => 'sometimes|array|max:200',
            'panel_allowlist.*' => 'string|max:64',
        ]);

        $result = $this->engine->updateSecurityIntelPolicy($validated);
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function intelStatus(): JsonResponse
    {
        $status = $this->engine->getSecurityIntelStatus();
        if (! empty($status['error'])) {
            return $this->securityErrorResponse($status['error'], $status);
        }

        return response()->json($status, 200);
    }

    public function getRateLimitProfile(): JsonResponse
    {
        $result = $this->engine->getNginxRateLimitProfile();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return response()->json(['result' => $result], 200);
    }

    public function setRateLimitProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile' => 'required|string|in:wordpress,laravel,api',
        ]);

        $result = $this->engine->setNginxRateLimitProfile((string) $validated['profile']);
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function getModSecuritySiteRules(): JsonResponse
    {
        $result = $this->engine->getModSecuritySiteRules();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return response()->json(['result' => $result], 200);
    }

    public function addModSecuritySiteRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'mode' => 'required|string|in:allow,deny,exception',
            'target' => 'nullable|string|max:255',
        ]);

        $result = $this->engine->addModSecuritySiteRule(
            (string) $validated['domain'],
            (string) $validated['mode'],
            isset($validated['target']) ? (string) $validated['target'] : null
        );
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function removeModSecuritySiteRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|max:32',
        ]);

        $result = $this->engine->removeModSecuritySiteRule((string) $validated['id']);
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    public function sshHardening(): JsonResponse
    {
        $result = $this->engine->getSshHardeningStatus();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return response()->json(['result' => $result], 200);
    }

    public function applySshHardening(): JsonResponse
    {
        $result = $this->engine->applySshHardening();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }
        return $this->securityMutationResponse($result);
    }

    public function ddosSysctl(): JsonResponse
    {
        $result = $this->engine->getDdosSysctlStatus();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return response()->json(['result' => $result], 200);
    }

    public function applyDdosHardening(): JsonResponse
    {
        $result = $this->engine->applyDdosHardening();
        if (! empty($result['error'])) {
            return $this->securityErrorResponse($result['error'], $result);
        }

        return $this->securityMutationResponse($result);
    }

    private function securityMutationResponse(array $result): JsonResponse
    {
        $this->forgetSecurityOverviewCache();

        return response()->json(['result' => $result], 200);
    }

    private function forgetSecurityOverviewCache(): void
    {
        Cache::forget('panelze:security:overview');
        Cache::forget('panelze:security:advisor');
    }

    private function securityErrorResponse(mixed $rawError, array $result): JsonResponse
    {
        $err = is_string($rawError) ? trim($rawError) : __('security.operation_failed');
        $hint = null;
        $code = 502;
        $lower = strtolower($err);

        if (str_contains($lower, 'sudo') || str_contains($lower, 'panelze-security') || str_contains($lower, 'panelsar-security')) {
            $code = 422;
            $hint = __('security.hint_sudo');
        } elseif (str_contains($lower, 'fim baseline not found')) {
            $code = 422;
            $hint = __('security.hint_fim_baseline');
        } elseif (str_contains($lower, 'missing /etc/modsecurity') || str_contains($lower, 'modsecurity')) {
            $code = 422;
            $hint = __('security.hint_modsecurity');
        } elseif (str_contains($lower, 'systemctl') || str_contains($lower, 'unit') || str_contains($lower, 'not found')) {
            $code = 422;
            $hint = __('security.hint_service');
        } elseif (str_contains($lower, 'scan path not allowed')) {
            $code = 422;
            $hint = __('security.hint_clamav_path');
        } elseif (str_contains($lower, 'maldet not installed')) {
            $code = 422;
            $hint = __('security.hint_maldet');
        } elseif (str_contains($lower, 'iptables') || str_contains($lower, 'firewall-rule')) {
            $code = 422;
            $hint = __('security.hint_firewall');
        } elseif (str_contains($lower, 'invalid profile') || str_contains($lower, 'invalid mode') || str_contains($lower, 'invalid domain') || str_contains($lower, 'invalid target')) {
            $code = 422;
            $hint = __('security.hint_invalid_input');
        }

        return response()->json([
            'message' => $err,
            'hint' => $hint,
            'result' => $result,
        ], $code);
    }
}
