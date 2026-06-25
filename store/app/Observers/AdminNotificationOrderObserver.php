<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\AdminNotificationService;

class AdminNotificationOrderObserver
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function created(Order $order): void
    {
        $this->notifications->fromOrderCreated($order);
    }

    public function updated(Order $order): void
    {
        $this->notifications->fromOrderUpdated($order);
    }
}
