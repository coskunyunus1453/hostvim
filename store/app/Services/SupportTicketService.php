<?php

namespace App\Services;

use App\Mail\SupportTicketReplyMail;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SupportTicketService
{
    public function __construct(private SettingsService $settings) {}

    public function open(
        User $user,
        string $subject,
        string $body,
        string $department = 'general',
        string $priority = 'medium',
        ?int $orderId = null,
    ): SupportTicket {
        return DB::transaction(function () use ($user, $subject, $body, $department, $priority, $orderId): SupportTicket {
            $ticket = SupportTicket::create([
                'number' => 'TEMP',
                'user_id' => $user->id,
                'order_id' => $orderId,
                'department' => $department,
                'subject' => $subject,
                'status' => SupportTicket::STATUS_OPEN,
                'priority' => $priority,
                'last_reply_at' => now(),
                'last_reply_by' => 'customer',
            ]);

            $prefix = (string) $this->settings->get('ticket_prefix', 'TKT-');
            $ticket->update([
                'number' => $prefix.date('Y').'-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT),
            ]);

            $ticket->messages()->create([
                'user_id' => $user->id,
                'is_staff' => false,
                'body' => $body,
            ]);

            $this->notifyStaff($ticket);

            return $ticket->fresh('messages');
        });
    }

    public function reply(SupportTicket $ticket, User $author, string $body, bool $isStaff): SupportTicketMessage
    {
        return DB::transaction(function () use ($ticket, $author, $body, $isStaff): SupportTicketMessage {
            $message = $ticket->messages()->create([
                'user_id' => $author->id,
                'is_staff' => $isStaff,
                'body' => $body,
            ]);

            $ticket->update([
                'status' => $isStaff ? SupportTicket::STATUS_ANSWERED : SupportTicket::STATUS_CUSTOMER_REPLY,
                'last_reply_at' => now(),
                'last_reply_by' => $isStaff ? 'staff' : 'customer',
            ]);

            if ($isStaff) {
                $this->notifyCustomer($ticket, $message);
            } else {
                $this->notifyStaff($ticket);
            }

            return $message;
        });
    }

    public function setStatus(SupportTicket $ticket, string $status): void
    {
        $ticket->update(['status' => $status]);
    }

    private function notifyCustomer(SupportTicket $ticket, SupportTicketMessage $message): void
    {
        try {
            Mail::to($ticket->user->email)->queue(new SupportTicketReplyMail($ticket->fresh(), $message, forStaff: false));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function notifyStaff(SupportTicket $ticket): void
    {
        $to = (string) $this->settings->get('contact_email', '');
        if ($to === '') {
            return;
        }

        try {
            $latest = $ticket->messages()->latest()->first();
            Mail::to($to)->queue(new SupportTicketReplyMail($ticket->fresh('messages'), $latest, forStaff: true));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
