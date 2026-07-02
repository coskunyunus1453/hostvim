<?php

namespace App\Console\Commands;

use App\Jobs\IssueDomainSslJob;
use App\Models\Domain;
use Illuminate\Console\Command;

/**
 * SSL'i olmayan aktif siteler için Let's Encrypt sertifikasını otomatik dener.
 *
 * Özellikle "kendi alan adım" siparişlerinde DNS satın alma anında sunucuya
 * yönlenmemiş olabilir; müşteri DNS'i sonradan yönlendirince bu komut (job'un
 * kendi DNS kontrolü sayesinde) sertifikayı otomatik olarak verir.
 */
class PanelzeSslAutoIssueCommand extends Command
{
    protected $signature = 'panelze:ssl-auto-issue {--limit=100 : Tek çalıştırmada kuyruğa alınacak azami site}';

    protected $description = 'SSL bulunmayan aktif sitelere otomatik Let\'s Encrypt sertifikası dener';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $domains = Domain::query()
            ->where('status', 'active')
            ->where('ssl_enabled', false)
            ->whereDoesntHave('sslCertificate', fn ($q) => $q->where('status', 'active'))
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'name']);

        foreach ($domains as $domain) {
            IssueDomainSslJob::dispatch($domain->id);
        }

        $this->info($domains->count().' site için otomatik SSL denemesi kuyruğa alındı.');

        return self::SUCCESS;
    }
}
