<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorFeature;
use App\Services\VendorAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeatureController extends Controller
{
    public function __construct(
        private VendorAuditService $audit,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'items' => VendorFeature::query()->orderBy('key')->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9][a-z0-9\.\-_]*$/', Rule::unique('vendor_features', 'key')],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'kind' => ['nullable', Rule::in(['boolean', 'quota'])],
        ]);
        $feature = VendorFeature::query()->create($validated);
        $this->audit->record('vendor.feature.created', 'info', null, null, (int) $request->user()->id, [
            'feature' => $feature->only(['id', 'key', 'kind']),
        ], $request);

        return response()->json(['item' => $feature], 201);
    }
}

