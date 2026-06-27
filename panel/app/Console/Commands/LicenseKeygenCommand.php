<?php

namespace App\Console\Commands;

use App\Services\OfflineLicenseService;
use Illuminate\Console\Command;

/**
 * Satıcı (vendor) kurulumu: offline lisans imzalama için Ed25519 anahtar çifti üretir.
 * PUBLIC key panele/engine'e gömülür; SECRET key yalnızca satıcıda kalır.
 */
class LicenseKeygenCommand extends Command
{
    protected $signature = 'license:keygen
                            {--out= : Secret key\'in yazılacağı dosya (varsayılan: sadece ekrana)}';

    protected $description = 'Offline lisans imzalama için yeni bir Ed25519 anahtar çifti üretir (satıcı)';

    public function handle(OfflineLicenseService $offline): int
    {
        $kp = $offline->generateKeypair();

        $this->newLine();
        $this->line('<fg=green;options=bold>Ed25519 lisans anahtar çifti üretildi.</>');
        $this->newLine();
        $this->line('PUBLIC KEY (panele/engine\'e gömülür — config + engine):');
        $this->line('  <fg=cyan>'.$kp['public'].'</>');
        $this->newLine();
        $this->line('SECRET KEY (YALNIZCA SATICIDA KALIR — asla ürünle dağıtma!):');
        $this->line('  <fg=yellow>'.$kp['secret'].'</>');
        $this->newLine();

        $out = trim((string) $this->option('out'));
        if ($out !== '') {
            $dir = dirname($out);
            if (! is_dir($dir)) {
                @mkdir($dir, 0700, true);
            }
            file_put_contents($out, $kp['secret']."\n");
            @chmod($out, 0600);
            $this->info("Secret key kaydedildi: {$out} (chmod 600)");
        }

        $this->warn('config/panelze.php → license.public_key ve engine license.public_key değerlerini bu PUBLIC key ile güncelle.');

        return self::SUCCESS;
    }
}
