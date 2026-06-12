<?php

namespace App\Services;

class DomainNsDelegationService
{
    public function __construct(
        private PanelDnsSettingsService $dnsSettings,
    ) {}

    /**
     * Alan adının yetkili NS kayıtları panel sunucusunu gösteriyor mu?
     */
    public function isDelegatedToPanel(string $domain): bool
    {
        $domain = strtolower(rtrim(trim($domain), '.'));
        if ($domain === '') {
            return false;
        }

        $panelNs = $this->normalizeNsSet($this->panelNameServers());
        if ($panelNs === []) {
            return false;
        }

        $publicNs = $this->normalizeNsSet($this->publicNameServers($domain));

        return $publicNs !== [] && $panelNs === $publicNs;
    }

    /**
     * @return list<string>
     */
    public function panelNameServers(): array
    {
        [$ns1, $ns2] = $this->dnsSettings->nameServers();

        return array_values(array_filter([$ns1, $ns2], fn (string $ns): bool => $ns !== ''));
    }

    /**
     * @return list<string>
     */
    public function publicNameServers(string $domain): array
    {
        $domain = strtolower(rtrim(trim($domain), '.'));
        if ($domain === '') {
            return [];
        }

        return $this->parseNsLines($this->digLines('NS', $domain));
    }

    /**
     * @return list<string>
     */
    private function digLines(string $type, string $name): array
    {
        foreach (['8.8.8.8', '1.1.1.1', ''] as $resolver) {
            $cmd = $resolver === ''
                ? sprintf('dig +short %s %s 2>/dev/null', escapeshellarg($type), escapeshellarg($name))
                : sprintf('dig +short %s %s @%s 2>/dev/null', escapeshellarg($type), escapeshellarg($name), escapeshellarg($resolver));
            $out = trim((string) @shell_exec($cmd) ?: '');
            if ($out !== '') {
                return preg_split('/\s+/', $out) ?: [];
            }
        }

        return [];
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function parseNsLines(array $lines): array
    {
        $names = [];
        foreach ($lines as $line) {
            $line = strtolower(rtrim(trim($line), '.'));
            if ($line !== '') {
                $names[] = $line;
            }
        }
        sort($names);

        return array_values(array_unique($names));
    }

    /**
     * @param  list<string>  $servers
     * @return list<string>
     */
    private function normalizeNsSet(array $servers): array
    {
        $normalized = [];
        foreach ($servers as $server) {
            $server = strtolower(rtrim(trim($server), '.'));
            if ($server !== '') {
                $normalized[] = $server;
            }
        }
        sort($normalized);

        return array_values(array_unique($normalized));
    }
}
