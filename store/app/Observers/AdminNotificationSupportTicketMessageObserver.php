<?php

namespace App\Observers;

use App\Models\SupportTicketMessage;
use App\Services\AdminNotificationService;

class AdminNotificationSupportTicketMessageObserver
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function created(SupportTicketMessage $message): void
    {
        if ($message->is_staff) {
            return;
        }

        $ticket = $message->ticket;
        if ($ticket === null) {
            return;
        }

        $this->notifications->fromSupportTicketReply($ticket, $message);
    }
}
