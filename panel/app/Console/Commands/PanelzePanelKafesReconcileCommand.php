<?php

namespace App\Console\Commands;

use App\Services\PanelKafesApplyService;
use Illuminate\Console\Command;

class PanelzePanelKafesReconcileCommand extends Command
{
    protected $signature = 'panelze:panelkafes-reconcile';

    protected $description = 'PanelKafes tutarlılık onarımı (FPM servisleri + eksik izolasyonları paket limitleriyle tamamlar)';

    public function handle(PanelKafesApplyService $apply): int
    {
        $result = $apply->reconcile();

        $this->line('Helper: '.($result['helper_ok'] ? 'OK' : 'UYARI'));
        if (! empty($result['helper_output'])) {
            $this->line((string) $result['helper_output']);
        }
        $this->info(sprintf(
            'Eksik cage onarımı: %d başarılı, %d hatalı',
            (int) ($result['repaired'] ?? 0),
            (int) ($result['failed'] ?? 0)
        ));

        return ((int) ($result['failed'] ?? 0)) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
