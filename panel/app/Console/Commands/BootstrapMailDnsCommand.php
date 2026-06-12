<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\MailDnsService;
use Illuminate\Console\Command;

class BootstrapMailDnsCommand extends Command
{
    protected $signature = 'panelze:mail-dns-bootstrap {--domain= : Tek alan adı} {--all : Tüm aktif domainler}';

    protected $description = 'Posta için MX, SPF, DMARC, DKIM DNS kayıtlarını oluşturur';

    public function handle(MailDnsService $mailDns): int
    {
        $domainName = trim((string) $this->option('domain'));
        $all = (bool) $this->option('all');

        if ($domainName === '' && ! $all) {
            $this->error('--domain= veya --all gerekli');

            return self::FAILURE;
        }

        $domains = $all
            ? Domain::query()->where('status', 'active')->orderBy('name')->get()
            : Domain::query()->where('name', $domainName)->get();

        if ($domains->isEmpty()) {
            $this->warn('Domain bulunamadı.');

            return self::FAILURE;
        }

        foreach ($domains as $domain) {
            $result = $mailDns->ensureMailDns($domain);
            if (! empty($result['error'])) {
                $this->warn("{$domain->name}: {$result['error']}");

                continue;
            }
            $this->info(sprintf(
                '%s: mail_dns created=%d skipped=%d',
                $domain->name,
                (int) ($result['mail_created'] ?? $result['created'] ?? 0),
                (int) ($result['skipped'] ?? 0)
            ));
        }

        return self::SUCCESS;
    }
}
