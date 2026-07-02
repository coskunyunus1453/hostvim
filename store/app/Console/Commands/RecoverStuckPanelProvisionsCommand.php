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

        // 1) 'processing'te takılı kalanlar: failed yap + yeniden dene.
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

        // 2) 'failed' ama ödemesi alınmış siparişler: panelde gerçekten kurulmuş olabilir
        //    (store→panel fulfill timeout tutarsızlığı). Paneli zorlamadan durum sorgusuyla
        //    senkronize et; panelde varsa "completed" yap. Gerçek hatalar dokunulmadan kalır.
        $recovered = 0;
        $failed = Order::query()
            ->where('payment_status', 'paid')
            ->where('panel_provision_status', 'failed')
            ->get();

        foreach ($failed as $order) {
            try {
                if ($provisioning->reconcileFromPanelStatus($order)) {
                    $recovered++;
                    $this->line("Sipariş {$order->order_number} panelden senkronlandı (completed).");
                }
            } catch (\Throwable $e) {
                $this->warn("Sipariş {$order->order_number} senkron hatası: {$e->getMessage()}");
            }
        }

        $this->info($stuck->count().' takılı sipariş, '.$recovered.' failed sipariş kurtarıldı.');

        return self::SUCCESS;
    }
}
