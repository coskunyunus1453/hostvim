<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\DomainDnsBootstrapService;
use Illuminate\Console\Command;

class RepairDomainDnsCommand extends Command
{
    protected $signature = 'panelze:dns-repair {--domain= : Tek alan adı} {--all : Tüm aktif domainler}';

    protected $description = 'Hatalı DNS kayıtlarını düzeltir, eksik varsayılanları ekler, BIND senkronlar';

    public function handle(DomainDnsBootstrapService $bootstrap): int
    {
        $name = trim((string) $this->option('domain'));
        $all = (bool) $this->option('all');

        if ($name === '' && ! $all) {
            $this->error('--domain=ornek.com veya --all kullanın');

            return self::FAILURE;
        }

        $query = Domain::query()->where('status', 'active');
        if ($name !== '') {
            $query->where('name', strtolower($name));
        }

        $domains = $query->get();
        if ($domains->isEmpty()) {
            $this->warn('Eşleşen domain yok');

            return self::SUCCESS;
        }

        $totals = ['repaired' => 0, 'created' => 0, 'removed' => 0];
        foreach ($domains as $domain) {
            $result = $bootstrap->repairAndProvision($domain);
            if (! empty($result['error'])) {
                $this->error("{$domain->name}: {$result['error']}");

                continue;
            }
            $totals['repaired'] += (int) ($result['repaired'] ?? 0);
            $totals['created'] += (int) ($result['created'] ?? 0);
            $totals['removed'] += (int) ($result['removed'] ?? 0);
            $this->line(sprintf(
                '%s: %d düzeltildi, %d silindi, +%d kayıt',
                $domain->name,
                (int) ($result['repaired'] ?? 0),
                (int) ($result['removed'] ?? 0),
                (int) ($result['created'] ?? 0),
            ));
        }

        $this->info(sprintf(
            'Toplam: %d düzeltme, %d silme, %d yeni kayıt',
            $totals['repaired'],
            $totals['removed'],
            $totals['created'],
        ));

        return self::SUCCESS;
    }
}
