<?php

namespace App\Services;

use App\Models\Domain;

class WebmailService
{
    public function __construct(
        private MailStackService $mailStack,
        private MailDnsService $mailDns,
        private PanelDnsSettingsService $dnsSettings,
    ) {}

    /**
     * @return array{
     *   host: string,
     *   url: string|null,
     *   dns_ok: bool,
     *   scheme: string|null,
     *   ips: list<string>,
     *   hint: string|null,
     *   mail_stack_ready: bool
     * }
     */
    public function statusForDomain(Domain $domain, bool $autoEnsureDns = false): array
    {
        $host = 'webmail.'.$domain->name;
        $mailStackReady = $this->mailStack->isWebmailStackInstalled();

        if ($mailStackReady && $autoEnsureDns) {
            $this->mailDns->ensureMailDns($domain);
            $domain->refresh();
            $domain->loadMissing('dnsRecords');
        }

        $ips = $this->resolveWebmailIps($host, $domain);
        $dnsOk = count($ips) > 0;
        $scheme = $this->detectScheme($host, $dnsOk ? $ips[0] : null);
        $url = ($dnsOk && $scheme) ? sprintf('%s://%s', $scheme, $host) : null;

        $hint = null;
        if (! $mailStackReady) {
            $hint = 'Posta sunucusu (Roundcube) henüz kurulmadı.';
        } elseif (! $dnsOk) {
            $hint = 'webmail alt alan adı için DNS kaydı yok. Panel DNS kayıtları oluşturuluyor; alan adı NS kayıtlarınız panel sunucusuna yönlü değilse kayıt sağlayıcınızda mail/webmail A kayıtlarını da ekleyin.';
        } elseif ($scheme === null) {
            $hint = 'DNS var ancak 80/443 portlarında webmail yanıt vermiyor.';
        }

        return [
            'host' => $host,
            'url' => $url,
            'dns_ok' => $dnsOk,
            'scheme' => $scheme,
            'ips' => $ips,
            'hint' => $hint,
            'mail_stack_ready' => $mailStackReady,
        ];
    }

    /**
     * @return list<string>
     */
    private function resolveWebmailIps(string $host, Domain $domain): array
    {
        $public = @gethostbynamel($host);
        if (is_array($public) && count($public) > 0 && $public[0] !== $host) {
            return array_values(array_unique(array_map('strval', $public)));
        }

        if ($this->dnsSettings->bindEnabled()) {
            $local = $this->digARecords($host, '127.0.0.1');
            if ($local !== []) {
                return $local;
            }
        }

        $panelIp = $domain->dnsRecords()
            ->where('type', 'A')
            ->where('name', 'webmail')
            ->value('value');
        if (is_string($panelIp) && filter_var($panelIp, FILTER_VALIDATE_IP)) {
            return [$panelIp];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function digARecords(string $host, string $resolver): array
    {
        $cmd = sprintf(
            'dig +short A %s @%s 2>/dev/null',
            escapeshellarg($host),
            escapeshellarg($resolver),
        );
        $out = trim((string) @shell_exec($cmd) ?: '');
        if ($out === '') {
            return [];
        }

        $ips = [];
        foreach (preg_split('/\s+/', $out) as $line) {
            $line = trim($line);
            if ($line !== '' && filter_var($line, FILTER_VALIDATE_IP)) {
                $ips[] = $line;
            }
        }

        return array_values(array_unique($ips));
    }

    private function detectScheme(string $host, ?string $fallbackIp): ?string
    {
        if ($this->probeHttp($host, 443, true)) {
            return 'https';
        }
        if ($this->probeHttp($host, 80, false)) {
            return 'http';
        }

        if ($fallbackIp !== null && filter_var($fallbackIp, FILTER_VALIDATE_IP)) {
            if ($this->probeHttpWithHost($fallbackIp, 443, $host, true)) {
                return 'https';
            }
            if ($this->probeHttpWithHost($fallbackIp, 80, $host, false)) {
                return 'http';
            }
        }

        return null;
    }

    private function probeHttp(string $host, int $port, bool $tls): bool
    {
        $errno = 0;
        $errstr = '';
        $target = ($tls ? 'ssl://' : '').$host.':'.$port;
        $socket = @stream_socket_client($target, $errno, $errstr, 2.0, STREAM_CLIENT_CONNECT);
        if (! is_resource($socket)) {
            return false;
        }
        fclose($socket);

        return true;
    }

    private function probeHttpWithHost(string $ip, int $port, string $host, bool $tls): bool
    {
        $errno = 0;
        $errstr = '';
        $target = ($tls ? 'ssl://' : '').$ip.':'.$port;
        $ctx = $tls
            ? stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'SNI_enabled' => true, 'peer_name' => $host]])
            : null;
        $socket = @stream_socket_client($target, $errno, $errstr, 2.0, STREAM_CLIENT_CONNECT, $ctx);
        if (! is_resource($socket)) {
            return false;
        }

        $scheme = $tls ? 'https' : 'http';
        $request = "HEAD / HTTP/1.1\r\nHost: {$host}\r\nConnection: close\r\n\r\n";
        fwrite($socket, $request);
        $line = fgets($socket) ?: '';
        fclose($socket);

        return str_contains($line, 'HTTP/');
    }
}
