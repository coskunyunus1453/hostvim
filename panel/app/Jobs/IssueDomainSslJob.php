<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\PanelDnsSettingsService;
use App\Services\SslIssueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bir alan adı için Let's Encrypt SSL'i "en iyi çaba" (best-effort) ile otomatik verir.
 *
 * Hosting kurulumundan sonra tetiklenir; ayrıca zamanlanmış komut (panelze:ssl-auto-issue)
 * ile DNS'i sonradan sunucuya yönlendirilen siteler için periyodik olarak yeniden denenir.
 *
 * Güvenli davranış:
 * - Zaten aktif SSL varsa çıkar.
 * - Alan adı sunucumuza (server_ip) çözümlenmiyorsa çıkar (ACME nasılsa başarısız olurdu).
 *   Böylece henüz DNS'i yönlendirilmemiş "kendi alan adım" siparişlerinde gürültü olmaz.
 * - Hatalar kurulumu bozmaz; yalnızca loglanır.
 */
class IssueDomainSslJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 180, 300];
    }

    public function __construct(public int $domainId) {}

    public function handle(SslIssueService $ssl, PanelDnsSettingsService $dns): void
    {
        $domain = Domain::query()->with(['user', 'sslCertificate'])->find($this->domainId);
        if (! $domain || $domain->status !== 'active') {
            return;
        }

        if ($domain->ssl_enabled || ($domain->sslCertificate && $domain->sslCertificate->status === 'active')) {
            return;
        }

        $user = $domain->user;
        if (! $user) {
            return;
        }

        if (! $this->dnsPointsHere($domain->name, $dns->serverIp())) {
            Log::info('panelze.auto_ssl.skip_dns', [
                'domain' => $domain->name,
                'reason' => 'DNS henüz sunucuya yönlenmiş değil',
            ]);

            return;
        }

        try {
            $result = $ssl->issue(
                $user,
                $domain,
                null,
                config('panelze.lets_encrypt_email') ?: null,
            );

            Log::info('panelze.auto_ssl.result', [
                'domain' => $domain->name,
                'ok' => $result['ok'] ?? false,
                'message' => $result['message'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::warning('panelze.auto_ssl.exception', [
                'domain' => $domain->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Alan adı (ve www) sunucunun IP'sine çözümleniyor mu?
     * Sunucu IP'si bilinmiyorsa muhafazakâr davranıp true döneriz (ACME kendi doğrular).
     */
    private function dnsPointsHere(string $host, string $serverIp): bool
    {
        $serverIp = trim($serverIp);
        if ($serverIp === '' || ! filter_var($serverIp, FILTER_VALIDATE_IP)) {
            return true;
        }

        foreach ([$host, 'www.'.$host] as $candidate) {
            $ip = gethostbyname($candidate);
            if ($ip === $serverIp) {
                return true;
            }
        }

        return false;
    }
}
