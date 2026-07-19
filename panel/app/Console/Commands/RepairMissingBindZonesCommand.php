<?php

namespace App\Console\Commands;

use App\Jobs\IssueDomainSslJob;
use App\Services\BindDnsService;
use App\Services\DomainDnsBootstrapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RepairMissingBindZonesCommand extends Command
{
    protected $signature = 'panelze:bind-repair-missing';

    protected $description = 'Panelde DNS kaydı olan ancak BIND zone dosyası eksik domainleri onarır';

    public function handle(BindDnsService $bind, DomainDnsBootstrapService $bootstrap): int
    {
        if (! $bind->serverIp()) {
            $this->warn('Sunucu IP ayarlı değil; atlanıyor');

            return self::SUCCESS;
        }

        $missing = $bind->domainsWithMissingZoneFiles();
        if ($missing->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info('Eksik BIND zone: '.$missing->count().' domain');

        foreach ($missing as $domain) {
            $result = $bootstrap->repairAndProvision($domain);
            if (! empty($result['error'])) {
                $this->error("{$domain->name}: {$result['error']}");
                Log::warning('panelze.bind_repair_missing_failed', [
                    'domain' => $domain->name,
                    'error' => $result['error'],
                ]);

                continue;
            }
            $this->line("{$domain->name}: DNS onarıldı, BIND senkron kuyruğa alındı");
            IssueDomainSslJob::dispatch($domain->id)->delay(now()->addMinutes(2));
        }

        $sync = $bind->syncReliable();
        if ($sync['ok'] ?? false) {
            $this->info('BIND senkron tamam: '.($sync['message'] ?? 'OK'));
        } else {
            $bind->scheduleSync(5);
            $this->warn('BIND senkron ertelendi: '.($sync['message'] ?? 'bilinmeyen'));
        }

        return self::SUCCESS;
    }
}
