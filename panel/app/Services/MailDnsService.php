<?php

namespace App\Services;

use App\Models\Domain;

class MailDnsService
{
    public function __construct(
        private DomainDnsBootstrapService $dnsBootstrap,
    ) {}

    /**
     * mail + webmail (ve eksikse @/www) A kayıtlarını panel DNS'ine ekler.
     *
     * @return array{created: int, skipped: int, error?: string}
     */
    public function ensureMailDns(Domain $domain): array
    {
        $result = $this->dnsBootstrap->ensureDefaults($domain);

        return [
            'created' => (int) ($result['created'] ?? 0),
            'skipped' => (int) ($result['skipped'] ?? 0),
            'error' => $result['error'] ?? null,
        ];
    }
}
