<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\SiteStackAdvisor;
use Illuminate\Console\Command;

class RepairSitesWebCommand extends Command
{
    protected $signature = 'hostvim:repair-sites-web
                            {--domain= : Yalnızca bu alan adı}
                            {--all : Tüm aktif domainler}';

    protected $description = 'Stack taraması + belge kökü/nginx vhost düzeltmesi (Laravel public kök, storage link)';

    public function handle(SiteStackAdvisor $advisor): int
    {
        $query = Domain::query()->where('status', 'active');
        if ($one = trim((string) $this->option('domain'))) {
            $query->where('name', strtolower($one));
        } elseif (! $this->option('all')) {
            $this->error('--domain=ornek.com veya --all gerekli.');

            return self::FAILURE;
        }

        $domains = $query->orderBy('name')->get();
        if ($domains->isEmpty()) {
            $this->warn('Domain bulunamadı.');

            return self::SUCCESS;
        }

        foreach ($domains as $domain) {
            $this->line("==> {$domain->name}");
            $result = $advisor->applyFixes($domain, []);
            if (! empty($result['error'])) {
                $this->error('  Hata: '.$result['error']);

                continue;
            }
            $applied = implode(', ', $result['applied'] ?? []);
            $this->info('  Uygulanan: '.($applied !== '' ? $applied : '—'));
            foreach ($result['errors'] ?? [] as $fix => $msg) {
                $this->warn("  {$fix}: {$msg}");
            }
            $after = $result['after']['scan'] ?? [];
            $this->line('  Belge kökü: '.($after['current_doc_root'] ?? '—'));
        }

        return self::SUCCESS;
    }
}
