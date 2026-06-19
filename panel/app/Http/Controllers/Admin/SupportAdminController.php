<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\Support\SupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportAdminController extends Controller
{
    public function __construct(private SupportService $support) {}

    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::query()
            ->with('user:id,name,email', 'assignee:id,name')
            ->withCount('messages')
            ->latest('last_reply_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($department = $request->string('department')->toString()) {
            $query->where('department', $department);
        }

        return response()->json($query->paginate(25));
    }

    public function show(SupportTicket $supportTicket): JsonResponse
    {
        return response()->json($supportTicket->load('messages.user:id,name', 'user:id,name,email', 'domain:id,name', 'assignee:id,name'));
    }

    public function reply(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $message = $this->support->reply($supportTicket, $request->user(), $validated['body'], isStaff: true);

        return response()->json($message->load('user:id,name'), 201);
    }

    public function update(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:open,answered,customer_reply,on_hold,closed'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $supportTicket->update(array_filter($validated, static fn ($v) => $v !== null));

        return response()->json($supportTicket->fresh('assignee:id,name'));
    }
}
