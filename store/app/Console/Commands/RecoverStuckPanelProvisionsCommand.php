<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Panel\PanelProvisioningService;
use Illuminate\Console\Command;

class RecoverStuckPanelProvisionsCommand extends Command
{
    protected $signature = 'panel:recover-stuck-provisions {--minutes=15 : processing durumunda bu kadar dakikadan eski siparişler}';

    protected $description = 'Takılı kalmış panel provizyonlarını failed yapar veya yeniden dener';

    public function handle(PanelProvisioningService $provisioning): int
    {
        $minutes = max(5, (int) $this->option('minutes'));
        $cutoff = now()->subMinutes($minutes);

        $stuck = Order::query()
            ->where('panel_provision_status', 'processing')
            ->where('updated_at', '<', $cutoff)
            ->get();

        foreach ($stuck as $order) {
            $order->update([
                'panel_provision_status' => 'failed',
                'panel_provision_error' => 'Provizyon zaman aşımına uğradı; otomatik kurtarma tetiklendi.',
            ]);

            $provisioning->retry($order->fresh());
            $this->line("Sipariş {$order->order_number} yeniden kuyruğa alındı.");
        }

        $this->info($stuck->count().' sipariş işlendi.');

        return self::SUCCESS;
    }
}
