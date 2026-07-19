<?php

namespace App\Services;

use App\Jobs\SyncBindDnsJob;
use App\Models\Domain;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BindDnsService
{
    public function __construct(
        private BindZoneWriter $writer,
        private PanelDnsSettingsService $dnsSettings,
    ) {}

    /**
     * @return array{ok: bool, zones?: int, message?: string, skipped?: bool}
     */
    /**
     * BIND senkronunu kuyruğa alır. Aktif transaction içindeyse commit sonrasına ertelenir;
     * aksi halde harici artisan süreci henüz commit edilmemiş DNS kayıtlarını göremez.
     */
    public function scheduleSync(int $delaySeconds = 2): void
    {
        if (! $this->dnsSettings->bindEnabled()) {
            return;
        }

        $job = SyncBindDnsJob::dispatch()->delay(now()->addSeconds($delaySeconds));

        if (DB::transactionLevel() > 0) {
            $job->afterCommit();
        }
    }

    /**
     * Anında senkron dener; başarısız olursa kuyruğa yedekler.
     *
     * @return array{ok: bool, skipped?: bool, message?: string, queued?: bool}
     */
    public function syncNowOrQueue(int $queueDelaySeconds = 3): array
    {
        if (! $this->dnsSettings->bindEnabled()) {
            return ['ok' => true, 'skipped' => true, 'message' => 'BIND sync kapalı'];
        }

        $result = $this->syncReliable();
        if ($result['ok'] ?? false) {
            return $result;
        }

        $this->scheduleSync($queueDelaySeconds);

        return array_merge($result, ['queued' => true]);
    }

    public function syncViaSudo(): array
    {
        if (! $this->dnsSettings->bindEnabled()) {
            return ['ok' => true, 'skipped' => true, 'message' => 'BIND sync kapalı'];
        }

        $script = trim((string) config('panelze.dns.bind_sync_script', '/usr/local/sbin/panelze-bind-sync'));
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
     * DNS ayarları kaydı sonrası — yeniden dene ve zone NS satırlarını doğrula.
     *
     * @return array{ok: bool, skipped?: bool, message?: string, zones?: int, verified?: bool}
     */
    public function syncReliable(): array
    {
        if (! $this->dnsSettings->bindEnabled()) {
            return ['ok' => true, 'skipped' => true, 'message' => 'BIND sync kapalı'];
        }

        $attempts = 2;
        $last = ['ok' => false, 'message' => 'BIND sync başarısız'];

        for ($i = 0; $i < $attempts; $i++) {
            if ($i > 0) {
                usleep(400_000);
            }

            $last = $this->syncViaSudo();
            if (! ($last['ok'] ?? false)) {
                continue;
            }

            if ($this->verifyPublishedNameServers()) {
                return array_merge($last, ['verified' => true]);
            }

            $last = [
                'ok' => false,
                'message' => 'BIND zone dosyaları güncel NS ile eşleşmiyor',
            ];
        }

        return $last;
    }

    /**
     * En az bir aktif zone dosyasında panel NS ayarları yayınlanıyor mu?
     */
    public function verifyPublishedNameServers(): bool
    {
        [$ns1, $ns2] = $this->nameServers();
        $expected = array_values(array_filter([
            strtolower(rtrim($ns1, '.')),
            strtolower(rtrim($ns2, '.')),
        ]));
        if ($expected === []) {
            return true;
        }

        $zonesDir = rtrim((string) config('panelze.dns.zones_dir', '/var/lib/bind/panelze/zones'), '/');
        $sample = Domain::query()
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('name')
            ->value('name');

        if (! is_string($sample) || $sample === '') {
            return true;
        }

        $path = $zonesDir.'/'.strtolower($sample).'.zone';
        if (! is_readable($path)) {
            return false;
        }

        $content = strtolower((string) file_get_contents($path));
        foreach ($expected as $host) {
            if (! str_contains($content, 'in ns '.$host.'.') && ! str_contains($content, 'in ns '.$host)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Panel kayıtlarından zone dosyalarını yazar (root ile çalıştırılmalı).
     *
     * @return array{ok: bool, zones: int, message?: string}
     */
    public function writeZonesAndReload(): array
    {
        if (! $this->dnsSettings->bindEnabled()) {
            return ['ok' => true, 'zones' => 0, 'message' => 'BIND sync kapalı'];
        }

        $zonesDir = rtrim((string) config('panelze.dns.zones_dir', '/var/lib/bind/panelze/zones'), '/');
        $confPath = $this->confPath();
        $serial = (int) date('YmdH');

        $confDir = dirname($confPath);
        if (! is_dir($confDir) && ! @mkdir($confDir, 0775, true) && ! is_dir($confDir)) {
            return ['ok' => false, 'zones' => 0, 'message' => 'BIND conf dizini oluşturulamadı: '.$confDir];
        }

        if (! is_dir($zonesDir) && ! @mkdir($zonesDir, 0775, true) && ! is_dir($zonesDir)) {
            return ['ok' => false, 'zones' => 0, 'message' => 'Zone dizini oluşturulamadı: '.$zonesDir];
        }
        @chmod($zonesDir, 0775);
        @chown($zonesDir, 'bind');
        @chgrp($zonesDir, 'bind');

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

        $confBody = "// Panelze auto-generated\n";
        if ($zoneBlocks !== []) {
            $confBody .= implode("\n", $zoneBlocks)."\n";
        }
        File::put($confPath, $confBody);
        @chmod($confPath, 0644);
        if (function_exists('posix_getpwnam')) {
            $bind = posix_getpwnam('bind');
            if ($bind !== false) {
                @chown($confPath, 'bind');
                @chgrp($confPath, 'bind');
            }
        }

        $reload = new Process(['rndc', 'reconfig']);
        $reload->run();
        if ($reload->isSuccessful()) {
            $zoneReload = new Process(['rndc', 'reload']);
            $zoneReload->run();
        }
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
        return $this->dnsSettings->nameServers();
    }

    public function serverIp(): string
    {
        return $this->dnsSettings->serverIp();
    }

    public function zoneFilePath(string $domainName): string
    {
        $zonesDir = rtrim((string) config('panelze.dns.zones_dir', '/var/lib/bind/panelze/zones'), '/');

        return $zonesDir.'/'.strtolower(rtrim(trim($domainName), '.')).'.zone';
    }

    public function zoneFileExists(string $domainName): bool
    {
        $path = $this->zoneFilePath($domainName);

        return is_readable($path) && filesize($path) > 0;
    }

    /**
     * Panelde DNS kaydı olan ancak BIND zone dosyası eksik domainler.
     *
     * @return Collection<int, Domain>
     */
    public function domainsWithMissingZoneFiles(): Collection
    {
        if (! $this->dnsSettings->bindEnabled()) {
            return collect();
        }

        return Domain::query()
            ->whereIn('status', ['active', 'pending'])
            ->whereHas('dnsRecords')
            ->orderBy('name')
            ->get()
            ->filter(fn (Domain $domain) => ! $this->zoneFileExists($domain->name))
            ->values();
    }

  /**
   * Üretilen zone listesi dosyası — /etc/bind salt-okunur olabilir; /var/lib/bind/panelze kullanılır.
   */
    public function confPath(): string
    {
        $zonesDir = rtrim((string) config('panelze.dns.zones_dir', '/var/lib/bind/panelze/zones'), '/');
        $fallback = dirname($zonesDir).'/named.conf.panelze-zones';
        $configured = trim((string) config('panelze.dns.conf_path', $fallback));

        if ($configured === '' || $configured === $fallback) {
            return $fallback;
        }

        $parent = dirname($configured);
        if (str_starts_with($configured, '/etc/bind') && is_dir($parent) && ! is_writable($parent)) {
            return $fallback;
        }

        return $configured;
    }

    private function isValidZoneName(string $zone): bool
    {
        return (bool) preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/', $zone);
    }
}
