<?php

namespace App\Services;

class MailStackService
{
    public function __construct(
        private EngineApiService $engine,
        private DomainDnsBootstrapService $dnsBootstrap,
    ) {}

    public function isWebmailStackInstalled(): bool
    {
        foreach ($this->engine->getStackModules() as $module) {
            if (($module['id'] ?? '') === 'mail-stack-webmail' && ($module['installed'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{ok: bool, error?: string, output?: string}
     */
    public function ensureWebmailStack(): array
    {
        if ($this->isWebmailStackInstalled()) {
            return ['ok' => true];
        }

        $result = $this->engine->installStackBundle('mail-stack-webmail');
        if (! empty($result['error'])) {
            return [
                'ok' => false,
                'error' => (string) $result['error'],
                'output' => is_string($result['output'] ?? null) ? $result['output'] : null,
            ];
        }

        $this->dnsBootstrap->repairAllActiveDomains();

        return ['ok' => true, 'output' => is_string($result['output'] ?? null) ? $result['output'] : null];
    }
}
