<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\Support\SupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function __construct(private SupportService $support) {}

    public function index(Request $request): JsonResponse
    {
        $tickets = $request->user()->supportTickets()
            ->withCount('messages')
            ->latest('last_reply_at')
            ->paginate(20);

        return response()->json($tickets);
    }

    public function show(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        abort_unless($supportTicket->user_id === $request->user()->id, 403);

        return response()->json($supportTicket->load('messages.user:id,name', 'domain:id,name'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
            'department' => ['nullable', 'string', 'in:general,technical,billing'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'domain_id' => ['nullable', 'integer', 'exists:domains,id'],
        ]);

        if (isset($validated['domain_id'])) {
            $owns = $request->user()->domains()->whereKey($validated['domain_id'])->exists();
            abort_unless($owns, 403);
        }

        $ticket = $this->support->open(
            $request->user(),
            $validated['subject'],
            $validated['body'],
            $validated['department'] ?? 'general',
            $validated['priority'] ?? 'medium',
            $validated['domain_id'] ?? null,
        );

        return response()->json($ticket->load('messages.user:id,name'), 201);
    }

    public function reply(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        abort_unless($supportTicket->user_id === $request->user()->id, 403);
        abort_if($supportTicket->status === SupportTicket::STATUS_CLOSED, 422, 'Kapalı talebe yanıt verilemez.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $message = $this->support->reply($supportTicket, $request->user(), $validated['body'], isStaff: false);

        return response()->json($message->load('user:id,name'), 201);
    }

    public function close(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        abort_unless($supportTicket->user_id === $request->user()->id, 403);
        $this->support->setStatus($supportTicket, SupportTicket::STATUS_CLOSED);

        return response()->json(['status' => 'closed']);
    }
}
