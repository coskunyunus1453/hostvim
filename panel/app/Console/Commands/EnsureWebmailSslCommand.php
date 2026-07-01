<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\EmailAccount;
use App\Services\MailStackService;
use App\Services\WebmailSslService;
use Illuminate\Console\Command;

class EnsureWebmailSslCommand extends Command
{
    protected $signature = 'panelze:ensure-webmail-ssl {--domain= : Tek alan adı} {--all : E-posta hesabı olan tüm domainler}';

    protected $description = 'webmail.* hostları için Let\'s Encrypt TLS sertifikası ve nginx 443 vhost oluşturur';

    public function handle(MailStackService $mailStack, WebmailSslService $webmailSsl): int
    {
        if (! $mailStack->isWebmailStackInstalled()) {
            $this->error('mail-stack-webmail kurulu değil.');

            return self::FAILURE;
        }

        $domainName = trim((string) $this->option('domain'));
        $all = (bool) $this->option('all');

        if ($domainName === '' && ! $all) {
            $this->error('--domain= veya --all gerekli');

            return self::FAILURE;
        }

        if ($domainName !== '') {
            $domains = Domain::query()->where('name', $domainName)->get();
        } else {
            $ids = EmailAccount::query()->distinct()->pluck('domain_id');
            $domains = Domain::query()->whereIn('id', $ids)->orderBy('name')->get();
        }

        if ($domains->isEmpty()) {
            $this->warn('Domain bulunamadı.');

            return self::FAILURE;
        }

        $failed = 0;
        foreach ($domains as $domain) {
            $result = $webmailSsl->ensureForDomain($domain);
            if ($result['ok']) {
                $note = ! empty($result['skipped']) ? ' (zaten geçerli)' : '';
                $this->info($domain->name.': OK'.$note);
            } else {
                $failed++;
                $this->warn($domain->name.': '.($result['error'] ?? 'hata'));
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
