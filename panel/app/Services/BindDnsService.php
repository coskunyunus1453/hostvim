<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BindDnsService
{
    public function __construct(
        private BindZoneWriter $writer,
    ) {}

    /**
     * @return array{ok: bool, zones?: int, message?: string, skipped?: bool}
     */
    public function syncViaSudo(): array
    {
        if (! (bool) config('hostvim.dns.bind_enabled', true)) {
            return ['ok' => true, 'skipped' => true, 'message' => 'BIND sync kapalı'];
        }

        $script = trim((string) config('hostvim.dns.bind_sync_script', '/usr/local/sbin/hostvim-bind-sync'));
        if (! is_executable($script)) {
            return ['ok' => false, 'message' => 'BIND sync betiği yok: '.$script];
        }

        $proc = new Process(['sudo', '-n', $script]);
        $proc->setTimeout(120);
        $proc->run();

        if (! $proc->isSuccessful()) {
            $err = trim($proc->getErrorOutput()."\n".$proc->getOutput());

            return ['ok' => false, 'message' => $err !== '' ? $err : 'BIND sync başarısız'];
        }

        return ['ok' => true, 'message' => trim($proc->getOutput())];
    }

    /**
     * Panel kayıtlarından zone dosyalarını yazar (root ile çalıştırılmalı).
     *
     * @return array{ok: bool, zones: int, message?: string}
     */
    public function writeZonesAndReload(): array
    {
        if (! (bool) config('hostvim.dns.bind_enabled', true)) {
            return ['ok' => true, 'zones' => 0, 'message' => 'BIND sync kapalı'];
        }

        $zonesDir = rtrim((string) config('hostvim.dns.zones_dir', '/var/lib/hostvim/bind/zones'), '/');
        $confPath = (string) config('hostvim.dns.conf_path', '/etc/bind/named.conf.hostvim-zones');
        $serial = (int) date('YmdH');

        if (! is_dir($zonesDir) && ! @mkdir($zonesDir, 0755, true) && ! is_dir($zonesDir)) {
            return ['ok' => false, 'zones' => 0, 'message' => 'Zone dizini oluşturulamadı: '.$zonesDir];
        }

        [$ns1, $ns2] = $this->nameServers();
        $serverIp = $this->serverIp();

        $domains = Domain::query()
            ->whereIn('status', ['active', 'pending'])
            ->with(['dnsRecords'])
            ->orderBy('name')
            ->get();

        $zoneBlocks = [];
        $written = 0;

        foreach ($domains as $domain) {
            $zone = strtolower(trim($domain->name));
            if ($zone === '' || ! $this->isValidZoneName($zone)) {
                continue;
            }

            $records = $domain->dnsRecords;
            $body = $this->writer->zoneText($domain, $records, $ns1, $ns2, $serverIp, $serial);
            $path = $zonesDir.'/'.$zone.'.zone';
            File::put($path, $body);
            @chmod($path, 0644);
            @chown($path, 'bind');
            @chgrp($path, 'bind');

            $check = new Process(['named-checkzone', $zone, $path]);
            $check->run();
            if (! $check->isSuccessful()) {
                Log::warning('BIND zone check failed', ['zone' => $zone, 'output' => $check->getOutput()]);

                continue;
            }

            $zoneBlocks[] = 'zone "'.$zone.'" { type master; file "'.$path.'"; };';
            $written++;
        }

        $confBody = "// Hostvim auto-generated\n";
        if ($zoneBlocks !== []) {
            $confBody .= implode("\n", $zoneBlocks)."\n";
        }
        File::put($confPath, $confBody);
        @chmod($confPath, 0644);

        $reload = new Process(['rndc', 'reload']);
        $reload->run();
        if (! $reload->isSuccessful()) {
            foreach (['named', 'bind9'] as $unit) {
                $reload = new Process(['systemctl', 'reload', $unit]);
                $reload->run();
                if ($reload->isSuccessful()) {
                    break;
                }
            }
        }

        if (! $reload->isSuccessful()) {
            return [
                'ok' => false,
                'zones' => $written,
                'message' => trim($reload->getErrorOutput()."\n".$reload->getOutput()) ?: 'BIND reload başarısız',
            ];
        }

        return ['ok' => true, 'zones' => $written, 'message' => "OK {$written} zone"];
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    public function nameServers(): array
    {
        $ns1 = trim((string) config('hostvim.dns.ns1', ''));
        $ns2 = trim((string) config('hostvim.dns.ns2', ''));
        if ($ns1 === '') {
            $ns1 = trim((string) @shell_exec('hostname -f 2>/dev/null')) ?: 'ns1';
        }
        if ($ns2 === '') {
            $parts = explode('.', $ns1, 2);
            $ns2 = count($parts) === 2 ? 'ns2.'.$parts[1] : 'ns2';
        }

        return [$ns1, $ns2];
    }

    public function serverIp(): string
    {
        $configured = trim((string) config('hostvim.dns.server_ip', ''));
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_IP)) {
            return $configured;
        }

        $ips = trim((string) @shell_exec('hostname -I 2>/dev/null') ?: '');
        $first = explode(' ', $ips)[0] ?? '';

        return filter_var($first, FILTER_VALIDATE_IP) ? $first : '';
    }

    private function isValidZoneName(string $zone): bool
    {
        return (bool) preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/', $zone);
    }
}
