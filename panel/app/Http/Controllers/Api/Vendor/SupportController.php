<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorSupportMessage;
use App\Models\VendorSupportTicket;
use App\Services\VendorAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportController extends Controller
{
    public function __construct(
        private VendorAuditService $audit,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'items' => VendorSupportTicket::query()->with(['tenant:id,name,slug', 'license:id,license_key'])->latest('last_activity_at')->paginate(20),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:vendor_tenants,id'],
            'license_id' => ['nullable', 'integer', 'exists:vendor_licenses,id'],
            'subject' => ['required', 'string', 'max:191'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'critical'])],
            'message' => ['required', 'string', 'max:20000'],
        ]);

        $ticket = VendorSupportTicket::query()->create([
            'tenant_id' => (int) $validated['tenant_id'],
            'license_id' => isset($validated['license_id']) ? (int) $validated['license_id'] : null,
            'created_by_user_id' => (int) $request->user()->id,
            'subject' => $validated['subject'],
            'priority' => (string) ($validated['priority'] ?? 'normal'),
            'status' => 'open',
            'last_message' => $validated['message'],
            'last_activity_at' => now(),
        ]);
        VendorSupportMessage::query()->create([
            'ticket_id' => (int) $ticket->id,
            'author_user_id' => (int) $request->user()->id,
            'author_type' => 'vendor',
            'message' => $validated['message'],
        ]);

        $this->audit->record('vendor.support.ticket.created', 'info', (int) $ticket->tenant_id, $ticket->license_id ? (int) $ticket->license_id : null, (int) $request->user()->id, [
            'ticket_id' => (int) $ticket->id,
            'priority' => (string) $ticket->priority,
        ], $request);

        return response()->json(['item' => $ticket], 201);
    }

    public function show(VendorSupportTicket $ticket): JsonResponse
    {
        return response()->json([
            'item' => $ticket->load(['tenant:id,name,slug', 'license:id,license_key,status', 'messages']),
        ]);
    }

    public function setStatus(Request $request, VendorSupportTicket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'waiting_customer', 'closed'])],
        ]);
        $ticket->status = $validated['status'];
        $ticket->last_activity_at = now();
        $ticket->save();

        return response()->json(['item' => $ticket]);
    }

    public function addMessage(Request $request, VendorSupportTicket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:20000'],
            'author_type' => ['nullable', Rule::in(['vendor', 'customer', 'system'])],
        ]);
        $msg = VendorSupportMessage::query()->create([
            'ticket_id' => (int) $ticket->id,
            'author_user_id' => (int) $request->user()->id,
            'author_type' => (string) ($validated['author_type'] ?? 'vendor'),
            'message' => $validated['message'],
        ]);
        $ticket->last_message = $validated['message'];
        $ticket->last_activity_at = now();
        $ticket->save();

        return response()->json(['item' => $msg], 201);
    }
}

