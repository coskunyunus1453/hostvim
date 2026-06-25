<?php

namespace App\Observers;

use App\Models\ContactMessage;
use App\Services\AdminNotificationService;

class AdminNotificationContactMessageObserver
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function created(ContactMessage $message): void
    {
        $this->notifications->fromContactMessage($message);
    }

    public function updated(ContactMessage $message): void
    {
        if ($message->wasChanged('is_read') && $message->is_read) {
            $this->notifications->dismissForNotifiable($message);
        }
    }
}
