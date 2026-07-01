<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\Log;

class WebmailSslService
{
    /**
     * webmail.{domain} için geçerli Let's Encrypt sertifikası ve nginx 443 vhost sağlar.
     *
     * @return array{ok: bool, hostname: string, error?: string, skipped?: bool}
     */
    public function ensureForDomain(Domain $domain): array
    {
        $apex = strtolower(trim($domain->name));
        $hostname = 'webmail.'.$apex;

        if ($this->hasValidCertificateFiles($hostname)) {
            $this->ensureNginxSslVhost($hostname);

            return ['ok' => true, 'hostname' => $hostname, 'skipped' => true];
        }

        $script = $this->scriptPath();
        if (! is_file($script) || ! is_executable($script)) {
            return [
                'ok' => false,
                'hostname' => $hostname,
                'error' => 'Webmail SSL betiği bulunamadı: '.$script,
            ];
        }

        $email = $this->resolveAcmeEmail();
        $cmd = escapeshellarg($script).' '.escapeshellarg($apex);
        if ($email !== '') {
            $cmd .= ' '.escapeshellarg($email);
        }

        $output = [];
        $exit = 0;
        exec('sudo -n '.$cmd.' 2>&1', $output, $exit);
        $text = trim(implode("\n", $output));

        if ($exit !== 0) {
            Log::warning('webmail_ssl.ensure_failed', [
                'domain' => $apex,
                'hostname' => $hostname,
                'exit' => $exit,
                'output' => $text,
            ]);

            return [
                'ok' => false,
                'hostname' => $hostname,
                'error' => $text !== '' ? $text : 'Webmail SSL kurulumu başarısız',
            ];
        }

        return ['ok' => true, 'hostname' => $hostname];
    }

    public function certificatePath(string $hostname): string
    {
        $sslRoot = rtrim($this->sslRoot(), '/');

        return $sslRoot.'/letsencrypt/config/live/'.$hostname.'/fullchain.pem';
    }

    public function hasValidCertificateFiles(string $hostname): bool
    {
        $chain = $this->certificatePath($hostname);
        $key = dirname($chain).'/privkey.pem';
        if (! is_file($chain) || ! is_file($key)) {
            return false;
        }

        $cmd = sprintf(
            'openssl x509 -in %s -noout -checkend 86400 2>/dev/null',
            escapeshellarg($chain),
        );
        exec($cmd, $out, $exit);

        return $exit === 0;
    }

    public function hasTrustedTls(string $hostname): bool
    {
        if (! $this->hasValidCertificateFiles($hostname)) {
            return false;
        }

        $cmd = sprintf(
            'echo | openssl s_client -servername %s -connect %s:443 2>&1',
            escapeshellarg($hostname),
            escapeshellarg($hostname),
        );
        $out = (string) @shell_exec($cmd);

        return str_contains($out, 'Verify return code: 0');
    }

    private function ensureNginxSslVhost(string $hostname): void
    {
        $conf = '/etc/nginx/sites-available/panelze-roundcube-ssl/'.$hostname.'.conf';
        if (is_file($conf)) {
            return;
        }

        $apex = preg_replace('/^webmail\./', '', $hostname) ?: $hostname;
        $script = $this->scriptPath();
        if (! is_file($script) || ! is_executable($script)) {
            return;
        }

        $email = $this->resolveAcmeEmail();
        $cmd = escapeshellarg($script).' '.escapeshellarg($apex);
        if ($email !== '') {
            $cmd .= ' '.escapeshellarg($email);
        }
        exec('sudo -n '.$cmd.' 2>&1');
    }

    private function resolveAcmeEmail(): string
    {
        $email = trim((string) config('panelze.lets_encrypt_email', ''));
        if ($email !== '' && SslIssueService::isLetsEncryptContactEmail($email)) {
            return $email;
        }

        $engineYaml = '/etc/panelze/engine.yaml';
        if (is_readable($engineYaml)) {
            $raw = (string) file_get_contents($engineYaml);
            if (preg_match('/lets_encrypt_email:\s*["\']?([^"\'\s]+)["\']?/i', $raw, $m)) {
                $candidate = trim($m[1]);
                if (SslIssueService::isLetsEncryptContactEmail($candidate)) {
                    return $candidate;
                }
            }
        }

        return '';
    }

    private function scriptPath(): string
    {
        return (string) config('panelze.webmail_ssl.script_path', '/usr/local/sbin/panelze-configure-roundcube-ssl');
    }

    private function sslRoot(): string
    {
        $configured = trim((string) config('panelze.webmail_ssl.ssl_root', ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $home = trim((string) env('PANELZE_HOME', ''));
        if ($home !== '') {
            return rtrim($home, '/').'/data/ssl';
        }

        return '/var/www/hostvim/data/ssl';
    }
}
