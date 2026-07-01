<?php

namespace App\Services;

use App\Models\Domain;

class MailDnsService
{
    public function __construct(
        private DomainDnsBootstrapService $dnsBootstrap,
        private MailStackService $mailStack,
        private WebmailSslService $webmailSsl,
    ) {}

    /**
     * MX, SPF, DMARC ve (posta kutusu varsa) DKIM kayıtlarını otomatik oluşturur.
     *
     * @return array{created: int, skipped: int, mail_created: int, error?: string}
     */
    public function ensureMailDns(Domain $domain): array
    {
        $result = $this->dnsBootstrap->repairAndProvision($domain);

        if (! empty($result['error'])) {
            return [
                'created' => 0,
                'skipped' => 0,
                'mail_created' => 0,
                'error' => $result['error'],
            ];
        }

        $created = (int) ($result['created'] ?? 0);

        if ($this->mailStack->isWebmailStackInstalled() && $domain->emailAccounts()->exists()) {
            $ssl = $this->webmailSsl->ensureForDomain($domain);
            if (! ($ssl['ok'] ?? false)) {
                $result['webmail_ssl_error'] = $ssl['error'] ?? 'webmail_ssl_failed';
            }
        }

        return [
            'created' => $created,
            'skipped' => (int) ($result['skipped'] ?? 0),
            'mail_created' => $created,
        ];
    }
}
