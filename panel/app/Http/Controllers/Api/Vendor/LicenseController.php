<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorLicense;
use App\Services\VendorAuditService;
use App\Services\VendorLicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LicenseController extends Controller
{
    public function __construct(
        private VendorLicenseService $licenseService,
        private VendorAuditService $audit,
    ) {}

    public function index(): JsonResponse
    {
        $items = VendorLicense::query()->with(['tenant:id,name,slug', 'plan:id,code,name'])->latest('id')->paginate(20);

        return response()->json(['items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:vendor_tenants,id'],
            'plan_id' => ['required', 'integer', 'exists:vendor_plans,id'],
            'status' => ['nullable', Rule::in(['active', 'expired', 'suspended', 'revoked'])],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'constraints' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ]);

        $license = VendorLicense::query()->create(array_merge($validated, [
            'license_key' => 'psr_'.Str::upper(Str::random(40)),
        ]));

        $this->audit->record('vendor.license.created', 'info', (int) $license->tenant_id, (int) $license->id, (int) $request->user()->id, [
            'license' => $license->only(['id', 'status', 'expires_at']),
        ], $request);

        return response()->json(['item' => $license], 201);
    }

    public function setStatus(Request $request, VendorLicense $license): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'expired', 'suspended', 'revoked'])],
        ]);

        $old = (string) $license->status;
        $license->status = $validated['status'];
        $license->save();

        $this->audit->record('vendor.license.status_changed', 'warning', (int) $license->tenant_id, (int) $license->id, (int) $request->user()->id, [
            'from' => $old,
            'to' => (string) $license->status,
        ], $request);

        return response()->json(['item' => $license]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string', 'max:96'],
            'instance_id' => ['nullable', 'string', 'max:96'],
        ]);

        $license = VendorLicense::query()
            ->with(['plan.planFeatures.feature', 'tenant'])
            ->where('license_key', $validated['license_key'])
            ->first();

        if (! $license) {
            return response()->json(['message' => 'License not found'], 404);
        }

        $payload = $this->licenseService->buildLicensePayload($license, null);
        $payload['usable'] = $this->licenseService->isLicenseUsable($license);
        if (! empty($validated['instance_id'])) {
            $payload['instance_id'] = (string) $validated['instance_id'];
        }

        $signature = $this->licenseService->signPayload($payload);
        $license->last_verified_at = now();
        $license->save();

        $this->audit->record('vendor.license.verified', 'info', (int) $license->tenant_id, (int) $license->id, null, [
            'instance_id' => $validated['instance_id'] ?? null,
        ], $request);

        return response()->json([
            'payload' => $payload,
            'signature' => $signature,
        ]);
    }
}

