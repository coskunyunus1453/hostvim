<?php

namespace App\Services;

use App\Models\Domain;

class WebmailService
{
    public function __construct(
        private MailStackService $mailStack,
        private MailDnsService $mailDns,
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
        }

        $dnsIps = @gethostbynamel($host);
        $dnsOk = is_array($dnsIps) && count($dnsIps) > 0 && $dnsIps[0] !== $host;
        $scheme = $this->detectScheme($host, $dnsOk);
        $url = ($dnsOk && $scheme) ? sprintf('%s://%s', $scheme, $host) : null;

        $hint = null;
        if (! $mailStackReady) {
            $hint = 'Posta sunucusu (Roundcube) henüz kurulmadı.';
        } elseif (! $dnsOk) {
            $hint = 'webmail alt alan adı için DNS kaydı yok. «DNS kayıtlarını ekle» ile mail ve webmail A kayıtlarını oluşturun.';
        } elseif ($scheme === null) {
            $hint = 'DNS var ancak 80/443 portlarında webmail yanıt vermiyor.';
        }

        return [
            'host' => $host,
            'url' => $url,
            'dns_ok' => $dnsOk,
            'scheme' => $scheme,
            'ips' => $dnsOk ? array_map('strval', $dnsIps) : [],
            'hint' => $hint,
            'mail_stack_ready' => $mailStackReady,
        ];
    }

    private function detectScheme(string $host, bool $dnsOk): ?string
    {
        if (! $dnsOk) {
            return null;
        }

        $errno = 0;
        $errstr = '';
        $s443 = @fsockopen($host, 443, $errno, $errstr, 2.0);
        if (is_resource($s443)) {
            fclose($s443);

            return 'https';
        }

        $s80 = @fsockopen($host, 80, $errno, $errstr, 2.0);
        if (is_resource($s80)) {
            fclose($s80);

            return 'http';
        }

        return null;
    }
}
