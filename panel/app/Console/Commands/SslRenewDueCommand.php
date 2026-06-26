<?php

namespace App\Console\Commands;

use App\Models\SslCertificate;
use App\Services\EngineApiService;
use App\Services\HostingSiteTargetResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SslRenewDueCommand extends Command
{
    protected $signature = 'ssl:renew-due';

    protected $description = 'Renew Let\'s Encrypt certificates with auto_renew enabled that expire soon';

    public function handle(EngineApiService $engine, HostingSiteTargetResolver $targets): int
    {
        $rows = SslCertificate::query()
            ->where('auto_renew', true)
            ->where('status', 'active')
            ->where('provider', 'letsencrypt')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->with(['domain', 'siteSubdomain'])
            ->limit(20)
            ->get();

        foreach ($rows as $cert) {
            $domain = $cert->domain;
            if (! $domain) {
                continue;
            }

            $target = $targets->forDomain($domain, $cert->site_subdomain_id);
            $res = $engine->renewSSL(
                $target->hostname,
                $target->isSubdomain() ? $target->engineSiteName : null,
                $target->subdomain?->path_segment,
            );
            if (! empty($res['error'])) {
                Log::warning('panelze.ssl_auto_renew_failed', [
                    'hostname' => $target->hostname,
                    'certificate_id' => $cert->id,
                    'error' => $res['error'],
                ]);

                continue;
            }

            $cert->update([
                'status' => 'active',
                'expires_at' => now()->addDays(90),
            ]);

            if ($target->isSubdomain() && $target->subdomain) {
                $target->subdomain->update([
                    'ssl_enabled' => true,
                    'ssl_expiry' => $cert->expires_at,
                ]);
            } else {
                $domain->update([
                    'ssl_enabled' => true,
                    'ssl_expiry' => $cert->expires_at,
                ]);
            }

            Log::info('panelze.ssl_auto_renew_ok', [
                'hostname' => $target->hostname,
                'certificate_id' => $cert->id,
            ]);
        }

        return self::SUCCESS;
    }
}
