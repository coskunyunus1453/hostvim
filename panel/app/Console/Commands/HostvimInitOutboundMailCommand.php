<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * @deprecated Eski kurulumlar ve betikler için — panelze:init-outbound-mail ile aynı işi yapar.
 */
class PanelzeInitOutboundMailCommand extends Command
{
    protected $signature = 'panelze:init-outbound-mail';

    protected $description = '(Eski ad) panelze:init-outbound-mail ile aynı — giden posta varsayılanlarını yazar.';

    public function handle(): int
    {
        return $this->call('panelze:init-outbound-mail');
    }
}
