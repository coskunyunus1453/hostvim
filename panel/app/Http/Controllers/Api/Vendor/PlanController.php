<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorFeature;
use App\Models\VendorPlan;
use App\Models\VendorPlanFeature;
use App\Services\VendorAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function __construct(
        private VendorAuditService $audit,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'items' => VendorPlan::query()->latest('id')->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9\.\-_]*$/', Rule::unique('vendor_plans', 'code')],
            'name' => ['required', 'string', 'max:120'],
            'billing_cycle' => ['nullable', Rule::in(['monthly', 'yearly'])],
            'price_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'is_public' => ['nullable', 'boolean'],
            'limits' => ['nullable', 'array'],
        ]);
        $plan = VendorPlan::query()->create($validated);
        $this->audit->record('vendor.plan.created', 'info', null, null, (int) $request->user()->id, [
            'plan' => $plan->only(['id', 'code', 'name']),
        ], $request);

        return response()->json(['item' => $plan], 201);
    }

    public function setFeature(Request $request, VendorPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'feature_id' => ['required', 'integer', 'exists:vendor_features,id'],
            'enabled' => ['required', 'boolean'],
            'quota' => ['nullable', 'integer', 'min:0'],
        ]);
        $feature = VendorFeature::query()->findOrFail((int) $validated['feature_id']);
        $pf = VendorPlanFeature::query()->updateOrCreate(
            [
                'plan_id' => (int) $plan->id,
                'feature_id' => (int) $feature->id,
            ],
            [
                'enabled' => (bool) $validated['enabled'],
                'quota' => $validated['quota'] ?? null,
            ]
        );
        $this->audit->record('vendor.plan.feature.updated', 'info', null, null, (int) $request->user()->id, [
            'plan_id' => (int) $plan->id,
            'feature_id' => (int) $feature->id,
            'enabled' => (bool) $pf->enabled,
            'quota' => $pf->quota,
        ], $request);

        return response()->json(['item' => $pf]);
    }
}

