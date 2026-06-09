<?php

namespace App\Console\Commands;

use App\Services\SiteStackMonitor;
use Illuminate\Console\Command;

class SiteStackScanHourlyCommand extends Command
{
    protected $signature = 'panelze:stack-scan-hourly';

    protected $description = 'Scan active sites for stack/config issues and create user alerts';

    public function handle(SiteStackMonitor $monitor): int
    {
        $stats = $monitor->runHourly();
        $this->info(sprintf(
            'Stack scan: scanned=%d alerted=%d cleared=%d errors=%d',
            $stats['scanned'],
            $stats['alerted'],
            $stats['cleared'],
            $stats['errors'],
        ));

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
