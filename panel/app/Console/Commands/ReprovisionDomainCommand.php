<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\DomainService;
use Illuminate\Console\Command;

class ReprovisionDomainCommand extends Command
{
    protected $signature = 'panelze:reprovision-domain {domain : Alan adı (ör. gebekado.com)}';

    protected $description = 'Engine site dizini, meta ve vhost dosyalarını panel kaydına göre yeniden oluşturur';

    public function handle(DomainService $domains): int
    {
        $name = strtolower(trim((string) $this->argument('domain')));
        $domain = Domain::query()->where('name', $name)->first();
        if ($domain === null) {
            $this->error("Panelde domain bulunamadı: {$name}");

            return self::FAILURE;
        }

        try {
            $fresh = $domains->reprovision($domain);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("OK: {$fresh->name}");
        $this->line('  document_root: '.($fresh->document_root ?? '—'));
        $this->line('  server_type: '.($fresh->server_type ?? 'nginx'));

        return self::SUCCESS;
    }
}
