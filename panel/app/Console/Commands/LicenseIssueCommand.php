<?php

namespace App\Console\Commands;

use App\Services\OfflineLicenseService;
use Illuminate\Console\Command;

/**
 * Satıcı (vendor): bir müşteri/kurulum için offline imzalı lisans anahtarı üretir.
 *
 * Örnek:
 *   php artisan license:issue --to="Acme Bilişim" --plan=enterprise \
 *     --domains="panel.acme.com" --days=365 --feat=phpmyadmin_sso,security_pro --id=HV-2026-0001
 */
class LicenseIssueCommand extends Command
{
    protected $signature = 'license:issue
                            {--to= : Lisans sahibi / firma adı}
                            {--plan=standard : Plan kodu (community, standard, pro, enterprise...)}
                            {--domains= : Bağlı host(lar), virgülle. Boş ya da * = her host}
                            {--days=365 : Geçerlilik (gün). 0 = süresiz}
                            {--grace= : Bitiş sonrası ek gün (varsayılan config)}
                            {--feat= : Aktif modüller, virgülle (ör. phpmyadmin_sso,security_pro)}
                            {--id= : Lisans referansı (boşsa otomatik üretilir)}
                            {--secret= : Private key (base64). Yoksa --secret-file / env / varsayılan dosya}
                            {--secret-file= : Private key dosya yolu}';

    protected $description = 'Offline imzalı lisans anahtarı üretir (satıcı, private key ile)';

    public function handle(OfflineLicenseService $offline): int
    {
        $secret = $this->resolveSecret();
        if ($secret === null) {
            $this->error('Private key bulunamadı. --secret, --secret-file, VENDOR_LICENSE_SIGNING_PRIVATE_KEY env ya da ~/.panelze/vendor-license-private.key kullanın.');

            return self::FAILURE;
        }

        $domains = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('domains')))));
        $feat = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('feat')))));
        $days = (int) $this->option('days');

        $claims = [
            'lid' => trim((string) $this->option('id')) ?: ('HV-'.now()->format('Y').'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 6))),
            'to' => trim((string) $this->option('to')),
            'plan' => trim((string) $this->option('plan')) ?: 'standard',
            'dom' => $domains,
            'iat' => time(),
            'exp' => $days > 0 ? time() + $days * 86400 : 0,
        ];
        if ($feat !== []) {
            $claims['feat'] = $feat;
        }
        if ($this->option('grace') !== null && trim((string) $this->option('grace')) !== '') {
            $claims['grace'] = (int) $this->option('grace');
        }

        try {
            $key = $offline->issue($claims, $secret);
        } catch (\Throwable $e) {
            $this->error('İmzalama hatası: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('<fg=green;options=bold>Lisans anahtarı üretildi:</>');
        $this->line('  Referans : '.$claims['lid']);
        $this->line('  Sahip    : '.($claims['to'] ?: '(belirtilmedi)'));
        $this->line('  Plan     : '.$claims['plan']);
        $this->line('  Domain   : '.($domains === [] ? '* (her host)' : implode(', ', $domains)));
        $this->line('  Bitiş    : '.($claims['exp'] === 0 ? 'süresiz' : gmdate('c', $claims['exp'])));
        if ($feat !== []) {
            $this->line('  Modüller : '.implode(', ', $feat));
        }
        $this->newLine();
        $this->line('<fg=cyan>'.$key.'</>');
        $this->newLine();
        $this->info('Müşteriye bu anahtarı verin: Panel → Lisans ekranında "Aktive Et" ya da .env LICENSE_KEY.');

        return self::SUCCESS;
    }

    private function resolveSecret(): ?string
    {
        $direct = trim((string) $this->option('secret'));
        if ($direct !== '') {
            return $direct;
        }

        $file = trim((string) $this->option('secret-file'));
        $candidates = array_filter([
            $file !== '' ? $file : null,
            getenv('VENDOR_LICENSE_SIGNING_PRIVATE_KEY_FILE') ?: null,
            ($home = getenv('HOME')) ? $home.'/.panelze/vendor-license-private.key' : null,
        ]);
        foreach ($candidates as $path) {
            if (is_string($path) && is_file($path) && is_readable($path)) {
                $content = trim((string) file_get_contents($path));
                if ($content !== '') {
                    return $content;
                }
            }
        }

        $env = trim((string) (getenv('VENDOR_LICENSE_SIGNING_PRIVATE_KEY') ?: ''));

        return $env !== '' ? $env : null;
    }
}
