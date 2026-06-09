<?php

namespace App\Console\Commands;

use App\Models\SslCertificate;
use App\Services\EngineApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SslRenewDueCommand extends Command
{
    protected $signature = 'ssl:renew-due';

    protected $description = 'Renew Let\'s Encrypt certificates with auto_renew enabled that expire soon';

    public function handle(EngineApiService $engine): int
    {
        $rows = SslCertificate::query()
            ->where('auto_renew', true)
            ->where('status', 'active')
            ->where('provider', 'letsencrypt')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->with('domain')
            ->limit(20)
            ->get();

        foreach ($rows as $cert) {
            $domain = $cert->domain;
            if (! $domain) {
                continue;
            }

            $res = $engine->renewSSL($domain->name);
            if (! empty($res['error'])) {
                Log::warning('panelze.ssl_auto_renew_failed', [
                    'domain' => $domain->name,
                    'certificate_id' => $cert->id,
                    'error' => $res['error'],
                ]);

                continue;
            }

            $cert->update([
                'status' => 'active',
                'expires_at' => now()->addDays(90),
            ]);
            $domain->update(['ssl_expiry' => $cert->expires_at]);

            Log::info('panelze.ssl_auto_renew_ok', [
                'domain' => $domain->name,
                'certificate_id' => $cert->id,
            ]);
        }

        return self::SUCCESS;
    }
}
