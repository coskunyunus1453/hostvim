<?php

namespace App\Console\Commands;

use App\Services\AdminNotificationService;
use Illuminate\Console\Command;

class SyncAdminNotificationsCommand extends Command
{
    protected $signature = 'admin:notifications-sync';

    protected $description = 'Bekleyen işlemler için admin bildirimlerini senkronize eder';

    public function handle(AdminNotificationService $notifications): int
    {
        $created = $notifications->syncPendingItems();

        $this->info("Senkron tamam. {$created} yeni bildirim oluşturuldu.");

        return self::SUCCESS;
    }
}
