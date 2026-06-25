<?php

namespace App\Observers;

use App\Models\SupportTicket;
use App\Services\AdminNotificationService;

class AdminNotificationSupportTicketObserver
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function created(SupportTicket $ticket): void
    {
        $this->notifications->fromSupportTicket($ticket);
    }
}
