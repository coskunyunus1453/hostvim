<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorTenant;
use App\Services\VendorAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function __construct(
        private VendorAuditService $audit,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'items' => VendorTenant::query()->with('panelUser:id,name,email,status')->latest('id')->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9][a-z0-9\-]*$/', Rule::unique('vendor_tenants', 'slug')],
            'panel_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['active', 'suspended', 'archived'])],
            'contact_email' => ['nullable', 'email', 'max:191'],
            'country' => ['nullable', 'string', 'max:8'],
            'meta' => ['nullable', 'array'],
        ]);

        $tenant = VendorTenant::query()->create($validated);
        $this->audit->record('vendor.tenant.created', 'info', (int) $tenant->id, null, (int) $request->user()->id, [
            'tenant' => $tenant->only(['id', 'name', 'slug', 'status']),
        ], $request);

        return response()->json(['item' => $tenant], 201);
    }
}

