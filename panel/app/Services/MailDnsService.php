<?php

namespace App\Services;

use App\Models\Domain;

class MailDnsService
{
    public function __construct(
        private EngineApiService $engine,
    ) {}

    /**
     * mail + webmail A kayıtlarını panel DNS'ine ekler (yoksa).
     *
     * @return array{created: int, skipped: int}
     */
    public function ensureMailDns(Domain $domain): array
    {
        $domainName = strtolower(trim($domain->name));
        $ip = $this->guessServerIp($domainName);
        if ($ip === null) {
            return ['created' => 0, 'skipped' => 0];
        }

        $created = 0;
        $skipped = 0;
        foreach (['mail', 'webmail'] as $name) {
            $exists = $domain->dnsRecords()
                ->where('type', 'A')
                ->where('name', $name)
                ->exists();
            if ($exists) {
                $skipped++;

                continue;
            }
            $record = $domain->dnsRecords()->create([
                'type' => 'A',
                'name' => $name,
                'value' => $ip,
                'ttl' => 3600,
                'priority' => null,
            ]);
            $this->engine->dnsCreate($domainName, [
                'id' => (string) $record->id,
                'type' => 'A',
                'name' => $name,
                'value' => $ip,
                'ttl' => 3600,
                'priority' => null,
            ]);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function guessServerIp(string $domainName): ?string
    {
        $ips = @gethostbynamel($domainName);
        if (is_array($ips) && count($ips) > 0 && $ips[0] !== $domainName) {
            return (string) $ips[0];
        }

        $serverIps = trim((string) shell_exec('hostname -I 2>/dev/null') ?: '');
        $first = explode(' ', $serverIps)[0] ?? '';

        return filter_var($first, FILTER_VALIDATE_IP) ? $first : null;
    }
}
