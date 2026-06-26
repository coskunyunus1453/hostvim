<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = $request->user()
            ->supportTickets()
            ->withCount('messages')
            ->latest('last_reply_at')
            ->paginate(15);

        return view('account.support.index', [
            'tickets' => $tickets,
        ]);
    }

    public function create()
    {
        return view('account.support.create');
    }

    public function store(Request $request, SupportTicketService $support)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
            'department' => ['nullable', 'string', Rule::in(['general', 'technical', 'billing'])],
            'priority' => ['nullable', 'string', Rule::in(['low', 'medium', 'high'])],
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')->where('user_id', $request->user()->id)],
        ]);

        $ticket = $support->open(
            $request->user(),
            $validated['subject'],
            $validated['body'],
            $validated['department'] ?? 'general',
            $validated['priority'] ?? 'medium',
            $validated['order_id'] ?? null,
        );

        return redirect()
            ->route('account.support.show', $ticket)
            ->with('success', 'Destek talebiniz oluşturuldu.');
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        $ticket->load(['messages.user', 'order']);

        return view('account.support.show', [
            'ticket' => $ticket,
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, SupportTicketService $support)
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);
        abort_if($ticket->status === SupportTicket::STATUS_CLOSED, 422);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $support->reply($ticket, $request->user(), $validated['body'], isStaff: false);

        return back()->with('success', 'Yanıtınız gönderildi.');
    }

    public function close(Request $request, SupportTicket $ticket, SupportTicketService $support)
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        $support->setStatus($ticket, SupportTicket::STATUS_CLOSED);

        return back()->with('success', 'Talep kapatıldı.');
    }
}
