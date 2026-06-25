<?php

namespace App\Observers;

use App\Models\CloudServer;
use App\Services\AdminNotificationService;

class AdminNotificationCloudServerObserver
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function updated(CloudServer $server): void
    {
        if ($server->wasChanged('status') && $server->status === CloudServer::STATUS_FAILED) {
            $this->notifications->fromCloudServerFailed($server);
        }
    }
}
