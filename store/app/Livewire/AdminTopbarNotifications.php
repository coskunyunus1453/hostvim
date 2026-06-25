<?php

namespace App\Livewire;

use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class AdminTopbarNotifications extends Component
{
    public bool $open = false;

    public function mount(AdminNotificationService $notifications): void
    {
        if (! Cache::has('admin_notifications:last_sync')) {
            $notifications->syncPendingItems();
            Cache::put('admin_notifications:last_sync', true, now()->addMinutes(5));
        }
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function markAllAsRead(AdminNotificationService $notifications): void
    {
        $notifications->markAllAsRead();
    }

    public function openNotification(int $id, AdminNotificationService $notifications)
    {
        $notification = $notifications->markAsRead($id);
        $this->open = false;

        if ($notification === null || $notification->action_url === '') {
            return;
        }

        return $this->redirect($notification->action_url, navigate: true);
    }

    public function render(AdminNotificationService $notifications)
    {
        return view('livewire.admin-topbar-notifications', [
            'notifications' => $notifications->recent(25),
            'unreadCount' => $notifications->unreadCount(),
        ]);
    }
}
