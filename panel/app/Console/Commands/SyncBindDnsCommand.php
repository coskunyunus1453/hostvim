<?php

namespace App\Console\Commands;

use App\Services\BindDnsService;
use Illuminate\Console\Command;

class SyncBindDnsCommand extends Command
{
    protected $signature = 'panelze:sync-bind-dns';

    protected $description = 'Panel DNS kayıtlarını BIND9 zone dosyalarına yazar ve named yeniden yükler';

    public function handle(BindDnsService $bind): int
    {
        $result = function_exists('posix_geteuid') && posix_geteuid() === 0
            ? $bind->writeZonesAndReload()
            : $bind->syncViaSudo();
        $msg = (string) ($result['message'] ?? '');
        if ($result['ok'] ?? false) {
            $this->info($msg !== '' ? $msg : 'BIND sync tamam');

            return self::SUCCESS;
        }

        $this->error($msg !== '' ? $msg : 'BIND sync başarısız');

        return self::FAILURE;
    }
}
