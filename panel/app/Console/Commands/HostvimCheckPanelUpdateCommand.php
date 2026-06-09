<?php

namespace App\Console\Commands;

use App\Services\PanelUpdateService;
use Illuminate\Console\Command;

class PanelzeCheckPanelUpdateCommand extends Command
{
    protected $signature = 'panelze:check-panel-update';

    protected $description = 'Hub üzerinden yeni panel sürümü kontrol eder ve admin bildirimi oluşturur';

    public function handle(PanelUpdateService $updates): int
    {
        $updates->notifyIfNewRelease();
        $payload = $updates->statusPayload();
        if (! empty($payload['update_available'])) {
            $this->info('Yeni sürüm: '.(string) ($payload['latest']['version'] ?? '?'));
        } else {
            $this->info('Güncelleme yok (mevcut: '.(string) ($payload['current_version'] ?? '?').')');
        }

        return self::SUCCESS;
    }
}
