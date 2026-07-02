<?php

namespace App\Console\Commands;

use App\Services\ManagedBackupService;
use Illuminate\Console\Command;

/**
 * Tüm aktif hosting siteleri için merkezi (şirket Google Drive havuzuna) günlük
 * yedekleme zamanlamalarını oluşturur/senkronlar. Yeni eklenen siteler otomatik kapsanır.
 */
class BackupsProvisionManagedCommand extends Command
{
    protected $signature = 'backups:provision-managed';

    protected $description = 'Merkezi otomatik yedekleme zamanlamalarını tüm hosting siteleri için oluşturur/senkronlar';

    public function handle(ManagedBackupService $svc): int
    {
        $r = $svc->provision();

        if (($r['enabled'] ?? false) === false) {
            $this->warn('Merkezi yedekleme kapalı. Aktif zamanlamalar pasifleştirildi: '.($r['disabled'] ?? 0));

            return self::SUCCESS;
        }

        if (($r['error'] ?? null) === 'no_pool') {
            $this->error('Şirket Google Drive havuzunda aktif hesap yok. Önce en az bir hesap bağlayın.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Merkezi yedekleme: %d hesap, %d site — oluşturulan: %d, güncellenen: %d, pasifleştirilen: %d',
            $r['pool_accounts'] ?? 0,
            $r['domains'] ?? 0,
            $r['created'] ?? 0,
            $r['updated'] ?? 0,
            $r['disabled_stale'] ?? 0,
        ));

        return self::SUCCESS;
    }
}
