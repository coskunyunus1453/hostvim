<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorLicense;
use App\Models\VendorNode;
use App\Services\VendorAuditService;
use App\Services\VendorLicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NodeController extends Controller
{
    public function __construct(
        private VendorLicenseService $licenseService,
        private VendorAuditService $audit,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'items' => VendorNode::query()->with('license:id,license_key,status')->latest('id')->paginate(30),
        ]);
    }

    /**
     * Node -> Vendor: ilk aktivasyon.
     */
    public function activate(Request $request): JsonResponse
    {
        if ($replay = $this->guardReplay($request, 'activate')) {
            return $replay;
        }
        $validated = $request->validate([
            'license_key' => ['required', 'string', 'max:96'],
            'instance_id' => ['required', 'string', 'max:96'],
            'fingerprint' => ['nullable', 'string', 'max:191'],
            'hostname' => ['nullable', 'string', 'max:191'],
            'public_ip' => ['nullable', 'ip'],
            'agent_version' => ['nullable', 'string', 'max:48'],
            'capabilities' => ['nullable', 'array'],
        ]);

        $license = VendorLicense::query()
            ->with(['plan.planFeatures.feature', 'tenant'])
            ->where('license_key', $validated['license_key'])
            ->first();
        if (! $license) {
            return response()->json(['message' => 'License not found'], 404);
        }
        if (! $this->licenseService->isLicenseUsable($license)) {
            return response()->json(['message' => 'License not active or expired'], 422);
        }

        $node = VendorNode::query()->updateOrCreate(
            [
                'license_id' => (int) $license->id,
                'instance_id' => (string) $validated['instance_id'],
            ],
            [
                'fingerprint' => $validated['fingerprint'] ?? null,
                'hostname' => $validated['hostname'] ?? null,
                'public_ip' => $validated['public_ip'] ?? null,
                'agent_version' => $validated['agent_version'] ?? null,
                'capabilities' => $validated['capabilities'] ?? null,
                'status' => 'online',
                'last_seen_at' => now(),
            ]
        );

        $nodeToken = 'node_'.Str::random(48);
        $meta = $node->meta ?? [];
        $meta['auth'] = [
            'token_hash' => hash('sha256', $nodeToken),
            'issued_at' => now()->toIso8601String(),
        ];
        $node->meta = $meta;
        $node->save();

        $payload = $this->licenseService->buildLicensePayload($license, $node);
        $payload['usable'] = true;
        $signature = $this->licenseService->signPayload($payload);

        $this->audit->record('vendor.node.activated', 'info', (int) $license->tenant_id, (int) $license->id, null, [
            'node_id' => (int) $node->id,
            'instance_id' => (string) $node->instance_id,
        ], $request);

        return response()->json([
            'node_id' => (int) $node->id,
            'node_token' => $nodeToken,
            'payload' => $payload,
            'signature' => $signature,
        ], 201);
    }

    /**
     * Node -> Vendor: periyodik heartbeat.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        if ($replay = $this->guardReplay($request, 'heartbeat')) {
            return $replay;
        }
        $validated = $request->validate([
            'license_key' => ['required', 'string', 'max:96'],
            'instance_id' => ['required', 'string', 'max:96'],
            'node_token' => ['required', 'string', 'min:24', 'max:128'],
            'status' => ['nullable', Rule::in(['online', 'offline', 'degraded'])],
            'agent_version' => ['nullable', 'string', 'max:48'],
            'public_ip' => ['nullable', 'ip'],
            'capabilities' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ]);

        $node = VendorNode::query()
            ->where('instance_id', $validated['instance_id'])
            ->whereHas('license', fn ($q) => $q->where('license_key', $validated['license_key']))
            ->with('license.plan.planFeatures.feature', 'license.tenant')
            ->first();
        if (! $node) {
            return response()->json(['message' => 'Node not found'], 404);
        }

        $tokenHash = (string) data_get($node->meta, 'auth.token_hash', '');
        if ($tokenHash === '' || ! hash_equals($tokenHash, hash('sha256', $validated['node_token']))) {
            return response()->json(['message' => 'Invalid node token'], 401);
        }

        $node->status = (string) ($validated['status'] ?? 'online');
        $node->last_seen_at = now();
        if (isset($validated['agent_version'])) {
            $node->agent_version = $validated['agent_version'];
        }
        if (isset($validated['public_ip'])) {
            $node->public_ip = $validated['public_ip'];
        }
        if (isset($validated['capabilities'])) {
            $node->capabilities = $validated['capabilities'];
        }
        if (isset($validated['meta'])) {
            $currentMeta = is_array($node->meta) ? $node->meta : [];
            $currentMeta['heartbeat'] = $validated['meta'];
            $node->meta = $currentMeta;
        }
        $node->save();

        $license = $node->license;
        $payload = $this->licenseService->buildLicensePayload($license, $node);
        $payload['usable'] = $this->licenseService->isLicenseUsable($license);
        $signature = $this->licenseService->signPayload($payload);

        return response()->json([
            'payload' => $payload,
            'signature' => $signature,
        ]);
    }

    private function guardReplay(Request $request, string $scope): ?JsonResponse
    {
        $timestamp = (int) $request->header('X-Vendor-Timestamp', '0');
        $nonce = (string) $request->header('X-Vendor-Nonce', '');
        $ttl = max(30, (int) config('panelze.vendor_request_replay_ttl_seconds', 300));
        if ($timestamp <= 0 || abs(time() - $timestamp) > $ttl) {
            return response()->json(['message' => 'Invalid request timestamp'], 401);
        }
        if ($nonce === '' || strlen($nonce) < 12 || strlen($nonce) > 128) {
            return response()->json(['message' => 'Invalid request nonce'], 401);
        }
        $nonceKey = 'vendor:node:'.$scope.':nonce:'.$nonce;
        if (! Cache::add($nonceKey, now()->toIso8601String(), $ttl)) {
            return response()->json(['message' => 'Replay detected'], 409);
        }

        return null;
    }
}

