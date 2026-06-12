<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\DomainDnsBootstrapService;
use Illuminate\Console\Command;

class BootstrapDomainDnsCommand extends Command
{
    protected $signature = 'panelze:dns-bootstrap {--domain= : Tek alan adı} {--all : Tüm aktif domainler}';

    protected $description = 'Domainlere varsayılan DNS kayıtlarını ekler (A, MX, SPF, DMARC, DKIM, NS glue)';

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

        $totalCreated = 0;
        foreach ($domains as $domain) {
            $result = $bootstrap->repairAndProvision($domain);
            if (! empty($result['error'])) {
                $this->error("{$domain->name}: {$result['error']}");

                continue;
            }
            $created = (int) ($result['created'] ?? 0);
            $skipped = (int) ($result['skipped'] ?? 0);
            $totalCreated += $created;
            $this->line("{$domain->name}: +{$created} kayıt, {$skipped} zaten vardı");
        }

        $this->info("Toplam {$totalCreated} yeni kayıt eklendi");

        return self::SUCCESS;
    }
}
