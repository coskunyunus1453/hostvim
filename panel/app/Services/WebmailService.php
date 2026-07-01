<?php

namespace App\Services;

use App\Models\Domain;

class WebmailService
{
    public function __construct(
        private MailStackService $mailStack,
        private MailDnsService $mailDns,
        private PanelDnsSettingsService $dnsSettings,
        private DomainNsDelegationService $nsDelegation,
        private WebmailSslService $webmailSsl,
    ) {}

    /**
     * @return array{
     *   host: string,
     *   url: string|null,
     *   dns_ok: bool,
     *   ns_delegated: bool,
     *   public_ns: list<string>,
     *   panel_ns: list<string>,
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
        $nsDelegated = $this->nsDelegation->isDelegatedToPanel($domain->name);
        $panelNs = $this->nsDelegation->panelNameServers();
        $publicNs = $this->nsDelegation->publicNameServers($domain->name);

        if ($mailStackReady && $autoEnsureDns) {
            $this->mailDns->ensureMailDns($domain);
            $domain->refresh();
            $domain->loadMissing('dnsRecords');
        }

        $ips = $this->resolveWebmailIps($host, $domain, $nsDelegated);
        $dnsOk = count($ips) > 0;
        $scheme = $this->detectScheme($host, $dnsOk ? $ips[0] : null);
        $url = ($dnsOk && $scheme) ? sprintf('%s://%s', $scheme, $host) : null;

        $hint = null;
        if (! $mailStackReady) {
            $hint = 'Posta sunucusu (Roundcube) henüz kurulmadı.';
        } elseif (! $dnsOk) {
            $hint = $this->dnsHint($domain, $nsDelegated, $panelNs, $publicNs);
        } elseif ($scheme === null) {
            $hint = 'DNS var ancak 80/443 portlarında webmail yanıt vermiyor.';
        }

        return [
            'host' => $host,
            'url' => $url,
            'dns_ok' => $dnsOk,
            'ns_delegated' => $nsDelegated,
            'public_ns' => $publicNs,
            'panel_ns' => $panelNs,
            'scheme' => $scheme,
            'ips' => $ips,
            'hint' => $hint,
            'mail_stack_ready' => $mailStackReady,
        ];
    }

    private function dnsHint(Domain $domain, bool $nsDelegated, array $panelNs, array $publicNs): string
    {
        $ip = $this->dnsSettings->serverIp();
        $panelNsText = $panelNs !== [] ? implode(', ', $panelNs) : 'panel NS';
        $publicNsText = $publicNs !== [] ? implode(', ', $publicNs) : 'tanımsız';

        if (! $nsDelegated) {
            return sprintf(
                'webmail.%s internet DNS\'inde yok (NXDOMAIN). Alan adı panel NS\'lerine yönlü değil; şu an: %s. Çözüm: kayıt sağlayıcınızda NS\'leri %s yapın VEYA harici DNS\'e şu kayıtları ekleyin: webmail A → %s, mail A → %s.',
                $domain->name,
                $publicNsText,
                $panelNsText,
                $ip,
                $ip,
            );
        }

        return sprintf(
            'webmail.%s için DNS kaydı panelde var ancak henüz yayılmadı. Birkaç dakika bekleyin veya NS kayıtlarınızın %s olduğunu doğrulayın.',
            $domain->name,
            $panelNsText,
        );
    }

    /**
     * @return list<string>
     */
    private function resolveWebmailIps(string $host, Domain $domain, bool $nsDelegated): array
    {
        $expected = $domain->dnsRecords()
            ->where('type', 'A')
            ->where('name', 'webmail')
            ->value('value');
        if (! is_string($expected) || ! filter_var($expected, FILTER_VALIDATE_IP)) {
            $expected = $this->dnsSettings->serverIp();
        }

        $public = $this->digARecords($host);
        if ($public !== []) {
            if ($expected !== '' && filter_var($expected, FILTER_VALIDATE_IP)) {
                $filtered = array_values(array_filter($public, fn (string $ip): bool => $ip === $expected));

                return $filtered !== [] ? $filtered : $public;
            }

            return $public;
        }

        if ($nsDelegated && $this->dnsSettings->bindEnabled()) {
            $local = $this->digARecords($host, '127.0.0.1');
            if ($local !== []) {
                return $local;
            }
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function digARecords(string $host, ?string $resolver = null): array
    {
        $resolvers = $resolver !== null ? [$resolver] : ['8.8.8.8', '1.1.1.1', ''];

        foreach ($resolvers as $res) {
            $cmd = $res === ''
                ? sprintf('dig +short A %s 2>/dev/null', escapeshellarg($host))
                : sprintf('dig +short A %s @%s 2>/dev/null', escapeshellarg($host), escapeshellarg($res));
            $out = trim((string) @shell_exec($cmd) ?: '');
            if ($out === '') {
                continue;
            }

            $ips = [];
            foreach (preg_split('/\s+/', $out) as $line) {
                $line = trim($line);
                if ($line !== '' && filter_var($line, FILTER_VALIDATE_IP)) {
                    $ips[] = $line;
                }
            }

            if ($ips !== []) {
                return array_values(array_unique($ips));
            }
        }

        return [];
    }

    private function detectScheme(string $host, ?string $fallbackIp): ?string
    {
        if ($this->webmailSsl->hasTrustedTls($host)) {
            return 'https';
        }

        if ($this->probeHttp($host, 80, false)) {
            return 'http';
        }

        if ($fallbackIp !== null && filter_var($fallbackIp, FILTER_VALIDATE_IP)) {
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
