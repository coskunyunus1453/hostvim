<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Collection;

class BindZoneWriter
{
    /**
     * @param  Collection<int, \App\Models\DnsRecord>  $records
     */
    public function zoneText(Domain $domain, Collection $records, string $ns1, ?string $ns2, string $serverIp, int $serial): string
    {
        $zone = strtolower(trim($domain->name));
        $ns1Fqdn = $this->fqdn($ns1, $zone);
        $ns2Fqdn = $ns2 ? $this->fqdn($ns2, $zone) : null;
        $admin = 'hostmaster.'.$zone.'.';

        $lines = [
            '; Panelze BIND zone — '.$zone,
            '; Otomatik üretildi; panelde düzenleyin.',
            '$ORIGIN '.$zone.'.',
            '$TTL 3600',
            '',
            '@ IN SOA '.$ns1Fqdn.' '.$admin.' (',
            '    '.$serial.' ; serial',
            '    3600       ; refresh',
            '    900        ; retry',
            '    604800     ; expire',
            '    86400      ; minimum',
            '    )',
            '@ IN NS '.$ns1Fqdn,
        ];
        if ($ns2Fqdn) {
            $lines[] = '@ IN NS '.$ns2Fqdn;
        }
        $lines[] = '';

        $hasRootA = $records->contains(fn ($r) => strtoupper((string) $r->type) === 'A' && in_array(strtolower(trim((string) $r->name)), ['', '@'], true));
        if (! $hasRootA && $serverIp !== '') {
            $lines[] = '@ 3600 IN A '.$serverIp;
        }

        foreach ($records as $r) {
            $type = strtoupper(trim((string) $r->type));
            $name = strtolower(trim((string) $r->name));
            // Apex NS zone başlığında zaten yazılıyor.
            if ($type === 'NS' && ($name === '' || $name === '@')) {
                continue;
            }
            $lines[] = $this->recordLine($zone, $r);
        }

        return implode("\n", $lines)."\n";
    }

    private function recordLine(string $zone, $r): string
    {
        $fqdn = $this->dnsNameToFqdn($zone, (string) $r->name);
        $ttl = max(60, (int) ($r->ttl ?: 3600));
        $type = strtoupper(trim((string) $r->type));
        $val = trim((string) $r->value);

        if ($type === 'TXT' && $val !== '') {
            $val = $this->formatTxtRdata($val);
        }

        if ($type === 'MX') {
            $pri = (int) ($r->priority ?? 10);
            $target = $this->mxTargetFqdn($val);

            return sprintf('%s %d IN MX %d %s', $fqdn, $ttl, $pri, $target);
        }

        if ($type === 'CNAME' || $type === 'NS') {
            $val = $this->mxTargetFqdn($val);
        }

        if ($type === 'A' || $type === 'AAAA') {
            return sprintf('%s %d IN %s %s', $fqdn, $ttl, $type, $val);
        }

        return sprintf('%s %d IN %s %s', $fqdn, $ttl, $type, $val);
    }

    private function formatTxtRdata(string $value): string
    {
        $value = trim($value, "\" \t\n\r");
        if ($value === '') {
            return '""';
        }
        if (strlen($value) <= 250) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }
        $chunks = str_split($value, 250);
        $quoted = array_map(
            static fn (string $chunk) => '"'.str_replace('"', '\\"', $chunk).'"',
            $chunks
        );

        return '( '.implode(' ', $quoted).' )';
    }

    private function fqdn(string $host, string $zone): string
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return 'ns1.'.$zone.'.';
        }
        if (str_ends_with($host, '.')) {
            return $host;
        }
        if (str_contains($host, '.')) {
            return $host.'.';
        }

        return $host.'.'.$zone.'.';
    }

    private function dnsNameToFqdn(string $zone, string $name): string
    {
        $name = strtolower(trim($name));
        if ($name === '' || $name === '@') {
            return '@';
        }
        if (str_ends_with($name, '.')) {
            return rtrim($name, '.');
        }

        return $name;
    }

    private function mxTargetFqdn(string $target): string
    {
        $target = strtolower(trim($target));
        if ($target === '') {
            return '.';
        }
        if (str_ends_with($target, '.')) {
            return $target;
        }

        return $target.'.';
    }
}
