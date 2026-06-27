<?php

namespace App\Console\Commands;

use App\Services\OfflineLicenseService;
use App\Services\PanelStoredLicenseService;
use Illuminate\Console\Command;

/**
 * Bir lisans anahtarını çözer ve gömülü public key ile doğrular (destek/teşhis).
 * Argüman verilmezse bu kurulumun etkin (env/db) anahtarını kullanır.
 */
class LicenseInspectCommand extends Command
{
    protected $signature = 'license:inspect
                            {key? : Lisans anahtarı (boşsa kurulumun etkin anahtarı)}
                            {--host= : Domain bağlama kontrolü için host (varsayılan APP_URL host)}';

    protected $description = 'Lisans anahtarını çözer ve imza/süre/domain doğrulamasını gösterir';

    public function handle(OfflineLicenseService $offline, PanelStoredLicenseService $stored): int
    {
        $key = trim((string) $this->argument('key'));
        if ($key === '') {
            $key = $stored->effectiveKey();
        }
        if ($key === '') {
            $this->error('Anahtar verilmedi ve bu kurulumda kayıtlı anahtar yok.');

            return self::FAILURE;
        }

        if ($offline->publicKey() === '') {
            $this->warn('Gömülü public key yok (license.public_key boş). Offline doğrulama devre dışı.');
        }

        $host = trim((string) $this->option('host'))
            ?: (parse_url((string) config('app.url'), PHP_URL_HOST) ?: null);

        $result = $offline->inspect($key, null);
        $v = $result['verification'];

        $this->newLine();
        $this->line('Anahtar : '.PanelStoredLicenseService::maskKey($key));
        if (is_array($result['claims'])) {
            $c = $result['claims'];
            $this->line('Referans: '.($c['lid'] ?? '-'));
            $this->line('Sahip   : '.($c['to'] ?? '-'));
            $this->line('Plan    : '.($c['plan'] ?? '-'));
            $this->line('Domain  : '.(empty($c['dom']) ? '* (her host)' : implode(', ', (array) $c['dom'])));
            $this->line('Bitiş   : '.(empty($c['exp']) ? 'süresiz' : gmdate('c', (int) $c['exp'])));
        }
        $this->newLine();

        // Host'a göre yeniden doğrula (domain kontrolü dahil)
        $withHost = $offline->verify($key, is_string($host) ? $host : null);
        if ($withHost['valid'] ?? false) {
            $this->info('GEÇERLİ ('.($withHost['status'] ?? 'active').') — host: '.($host ?: 'kontrol edilmedi'));

            return self::SUCCESS;
        }

        $this->error('GEÇERSİZ: ['.($withHost['code'] ?? '?').'] '.($withHost['message'] ?? ''));

        return self::FAILURE;
    }
}
