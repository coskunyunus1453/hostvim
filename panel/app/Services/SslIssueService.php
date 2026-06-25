<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\SiteSubdomain;
use App\Models\SslCertificate;
use App\Models\User;
use App\Support\HostingSiteTarget;
use App\Services\HostingSiteTargetResolver;
use Illuminate\Support\Str;

class SslIssueService
{
    /**
     * Let's Encrypt ACME: iletişim e-postasında alan adında en az bir nokta gerekir
     * (örn. admin@localhost, user@internal kabul edilmez).
     */
    public static function isLetsEncryptContactEmail(string $email): bool
    {
        $email = trim($email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $at = strrpos($email, '@');
        if ($at === false) {
            return false;
        }
        $host = strtolower(substr($email, $at + 1));

        return str_contains($host, '.');
    }

    public function __construct(
        private EngineApiService $engine,
        private HostingQuotaService $quota,
        private HostingSiteTargetResolver $targets,
    ) {}

    /**
     * Let’s Encrypt DV sertifikası — SslController ve SiteController ortak kullanımı.
     *
     * @return array{
     *     ok: bool,
     *     http_status: int,
     *     message?: string,
     *     certificate?: SslCertificate|null,
     *     engine?: array<string, mixed>
     * }
     */
    public function issue(User $user, Domain $domain, ?string $emailFromRequest, ?string $configFallbackEmail, ?int $subdomainId = null): array
    {
        $this->quota->ensureSslAllowed($user);

        $target = $this->targets->forDomain($domain, $subdomainId);

        return $this->issueForTarget($user, $target, $emailFromRequest, $configFallbackEmail);
    }

    /**
     * @return array{ok: bool, http_status: int, message?: string, certificate?: SslCertificate|null, engine?: array<string, mixed>, diagnostics?: list<array<string, mixed>>}
     */
    public function issueForTarget(User $user, HostingSiteTarget $target, ?string $emailFromRequest, ?string $configFallbackEmail): array
    {
        $diagnostics = $this->preflightDiagnostics($target->hostname, $this->resolveAcmeWebroot($target->documentRoot));

        $email = $emailFromRequest;
        if ($email === null || $email === '') {
            $email = $configFallbackEmail ?: null;
        }
        if ($email === null || $email === '') {
            $email = $user->email;
        }
        if ($email === null || $email === '') {
            return [
                'ok' => false,
                'http_status' => 422,
                'message' => (string) __('ssl.email_required'),
            ];
        }

        if (! self::isLetsEncryptContactEmail((string) $email)) {
            return [
                'ok' => false,
                'http_status' => 422,
                'message' => (string) __('ssl.invalid_lets_encrypt_email', ['email' => $email]),
            ];
        }

        $cert = SslCertificate::updateOrCreate(
            [
                'domain_id' => $target->domain->id,
                'site_subdomain_id' => $target->subdomain?->id,
            ],
            [
                'provider' => 'letsencrypt',
                'type' => 'dv',
                'status' => 'pending',
                'auto_renew' => true,
            ]
        );

        if (! $target->isSubdomain()) {
            $activate = $this->engine->activateSite($target->engineSiteName);
            if (! empty($activate['error'])) {
                $cert->update(['status' => 'failed']);

                return [
                    'ok' => false,
                    'http_status' => 503,
                    'message' => (string) $activate['error'],
                    'certificate' => $cert->fresh(),
                    'engine' => $activate,
                    'diagnostics' => $diagnostics,
                ];
            }
        }

        $engine = $this->engine->issueSSL(
            $target->hostname,
            is_string($email) ? $email : null,
            $target->isSubdomain() ? $target->engineSiteName : null,
            $target->subdomain?->path_segment,
        );
        if (! empty($engine['error'])) {
            $cert->update(['status' => 'failed']);
            $this->clearSslFlags($target);

            return [
                'ok' => false,
                'http_status' => 503,
                'message' => SslErrorTranslator::translate((string) $engine['error'], $target->hostname),
                'certificate' => $cert->fresh(),
                'engine' => $engine,
                'diagnostics' => $diagnostics,
            ];
        }

        $cert->update(['status' => 'active', 'issued_at' => now(), 'expires_at' => now()->addDays(90)]);
        $this->applySslFlags($target, $cert);

        return [
            'ok' => true,
            'http_status' => 200,
            'message' => (string) __('ssl.issued'),
            'certificate' => $cert->fresh(),
            'engine' => $engine,
            'hostname' => $target->hostname,
            'diagnostics' => $diagnostics,
        ];
    }

    private function applySslFlags(HostingSiteTarget $target, SslCertificate $cert): void
    {
        if ($target->isSubdomain() && $target->subdomain) {
            $target->subdomain->update([
                'ssl_enabled' => true,
                'ssl_expiry' => $cert->expires_at,
            ]);

            return;
        }

        $target->domain->update([
            'ssl_enabled' => true,
            'ssl_expiry' => $cert->expires_at,
            'force_https' => true,
        ]);
    }

    private function clearSslFlags(HostingSiteTarget $target): void
    {
        if ($target->isSubdomain() && $target->subdomain) {
            $target->subdomain->update(['ssl_enabled' => false, 'ssl_expiry' => null]);

            return;
        }

        $target->domain->update(['ssl_enabled' => false, 'ssl_expiry' => null]);
    }

    /**
     * SSL issue öncesi hızlı teşhis (engine dışı).
     *
     * @return list<array{key: string, ok: bool, message: string}>
     */
    /**
     * @return list<array{key: string, ok: bool, message: string}>
     */
    private function preflightDiagnostics(string $hostname, string $documentRoot): array
    {
        $rows = [];

        $host = trim($hostname);
        $docroot = trim($documentRoot);

        // DNS resolve
        $ip = $host !== '' ? gethostbyname($host) : '';
        $dnsOk = $ip !== '' && $ip !== $host && filter_var($ip, FILTER_VALIDATE_IP);
        $rows[] = [
            'key' => 'dns',
            'ok' => (bool) $dnsOk,
            'message' => $dnsOk
                ? (string) __('ssl.diag_dns_ok', ['ip' => $ip])
                : (string) __('ssl.diag_dns_fail'),
        ];

        // TCP reachability
        foreach ([80, 443] as $port) {
            $ok = false;
            if ($host !== '') {
                $errno = 0;
                $errstr = '';
                $sock = @fsockopen($host, $port, $errno, $errstr, 2.0);
                if (is_resource($sock)) {
                    $ok = true;
                    fclose($sock);
                }
            }
            $rows[] = [
                'key' => 'tcp_'.$port,
                'ok' => $ok,
                'message' => $ok
                    ? (string) __('ssl.diag_port_ok', ['port' => $port])
                    : (string) __('ssl.diag_port_fail', ['port' => $port]),
            ];
        }

        // Docroot + ACME path
        $docOk = $docroot !== '' && is_dir($docroot) && is_writable($docroot);
        $rows[] = [
            'key' => 'docroot',
            'ok' => $docOk,
            'message' => $docOk
                ? (string) __('ssl.diag_docroot_ok')
                : (string) __('ssl.diag_docroot_fail'),
        ];

        if ($docroot !== '' && is_dir($docroot)) {
            $acme = rtrim($docroot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.well-known'.DIRECTORY_SEPARATOR.'acme-challenge';
            $ok = true;
            if (! is_dir($acme)) {
                $ok = @mkdir($acme, 0755, true);
            }
            if ($ok) {
                $probe = $acme.DIRECTORY_SEPARATOR.'panelze_acme_'.Str::random(8).'.txt';
                $ok = @file_put_contents($probe, 'ok') !== false;
                @unlink($probe);
            }
            $rows[] = [
                'key' => 'acme_path',
                'ok' => $ok,
                'message' => $ok
                    ? (string) __('ssl.diag_acme_ok')
                    : (string) __('ssl.diag_acme_fail'),
            ];
            if ($ok && $host !== '') {
                $rows[] = $this->preflightAcmeHttpProbe($host, $docroot);
            }
        } else {
            $rows[] = [
                'key' => 'acme_path',
                'ok' => false,
                'message' => (string) __('ssl.diag_acme_skip'),
            ];
        }

        return $rows;
    }

    private function resolveAcmeWebroot(string $documentRoot): string
    {
        $documentRoot = rtrim(trim($documentRoot), '/\\');
        if ($documentRoot === '') {
            return $documentRoot;
        }
        if (basename($documentRoot) === 'public') {
            return $documentRoot;
        }
        $pub = $documentRoot.DIRECTORY_SEPARATOR.'public';
        if (is_file($pub.DIRECTORY_SEPARATOR.'index.php')) {
            return $pub;
        }

        return $documentRoot;
    }

    /**
     * @return array{key: string, ok: bool, message: string}
     */
    private function preflightAcmeHttpProbe(string $hostname, string $documentRoot): array
    {
        $token = 'panelze_probe_'.Str::random(10);
        $acmeDir = rtrim($documentRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.well-known'.DIRECTORY_SEPARATOR.'acme-challenge';
        $probeFile = $acmeDir.DIRECTORY_SEPARATOR.$token;
        if (@file_put_contents($probeFile, 'ok') === false) {
            return [
                'key' => 'acme_http',
                'ok' => false,
                'message' => (string) __('ssl.diag_acme_http_fail'),
            ];
        }

        $url = 'http://'.$hostname.'/.well-known/acme-challenge/'.$token;
        $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        @unlink($probeFile);

        $ok = is_string($body) && trim($body) === 'ok';
        if (! $ok && is_file(dirname($documentRoot).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'index.php')) {
            return [
                'key' => 'acme_http',
                'ok' => false,
                'message' => (string) __('ssl.diag_acme_http_laravel', ['host' => $hostname]),
            ];
        }

        return [
            'key' => 'acme_http',
            'ok' => $ok,
            'message' => $ok
                ? (string) __('ssl.diag_acme_http_ok')
                : (string) __('ssl.diag_acme_http_fail'),
        ];
    }
}
