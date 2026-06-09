<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PDO;
use Throwable;

class PanelzeInstallCheckCommand extends Command
{
    protected $signature = 'panelze:install-check {--ping : Engine /health isteği dene}';

    protected $description = 'Üretim öncesi panel yapılandırmasını kontrol eder';

    public function handle(): int
    {
        $ok = true;
        $env = (string) config('app.env');
        $debug = (bool) config('app.debug');
        $key = (string) config('hostvim.engine_internal_key', '');
        $url = rtrim((string) config('hostvim.engine_url', ''), '/');

        if ($env === 'production' && $debug) {
            $this->error('APP_DEBUG üretimde false olmalı.');
            $ok = false;
        } else {
            $this->info('APP_ENV='.$env.' APP_DEBUG='.($debug ? 'true' : 'false'));
        }

        if ($key === '') {
            $this->warn('ENGINE_INTERNAL_KEY boş — engine API çağrıları başarısız olur.');
            $ok = false;
        } else {
            $this->info('ENGINE_INTERNAL_KEY tanımlı.');
        }

        if ($url === '') {
            $this->warn('ENGINE_API_URL boş.');
            $ok = false;
        } else {
            $this->info('ENGINE_API_URL='.$url);
        }

        if ($this->option('ping') && $url !== '') {
            try {
                $r = Http::timeout(5)->get($url.'/health');
                if ($r->successful()) {
                    $this->info('Engine /health: OK');
                } else {
                    $this->error('Engine /health: HTTP '.$r->status());
                    $ok = false;
                }
            } catch (Throwable $e) {
                $this->error('Engine erişilemedi: '.$e->getMessage());
                $ok = false;
            }
        }

        $ok = $this->checkPanelDatabase($ok);
        $ok = $this->checkMysqlProvision($ok);
        $ok = $this->checkHostingWebRoot($ok);

        if (! $ok) {
            $this->newLine();
            $this->comment('Onarım (root): sudo panelze-post-install');
            $this->comment('  veya: MYSQL_ROOT_PASS=... bash /var/www/panelze/deploy/scripts/repair-mysql-users.sh');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function checkPanelDatabase(bool $ok): bool
    {
        if (! in_array((string) config('database.default'), ['mysql', 'mariadb'], true)) {
            return $ok;
        }

        try {
            DB::connection()->getPdo()->query('SELECT 1');
            $this->info('Panel MySQL bağlantısı: OK');
        } catch (Throwable $e) {
            $this->error('Panel MySQL bağlantısı: '.$e->getMessage());
            $ok = false;
        }

        return $ok;
    }

    private function checkMysqlProvision(bool $ok): bool
    {
        if (! (bool) config('hostvim.mysql_provision.enabled', false)) {
            $this->warn('MYSQL_PROVISION_ENABLED kapalı — panelden MySQL DB oluşturulamaz.');

            return $ok;
        }

        $host = (string) config('hostvim.mysql_provision.host', '127.0.0.1');
        $port = (int) config('hostvim.mysql_provision.port', 3306);
        $user = (string) config('hostvim.mysql_provision.username', '');
        $pass = (string) config('hostvim.mysql_provision.password', '');

        if ($user === '') {
            $this->error('MYSQL_PROVISION_USERNAME boş.');
            $ok = false;

            return $ok;
        }

        try {
            $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_TIMEOUT => 4,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->query('SELECT 1');
            $this->info('MySQL provision bağlantısı: OK');
        } catch (Throwable $e) {
            $this->error('MySQL provision bağlantısı: '.$e->getMessage());
            $ok = false;
        }

        return $ok;
    }

    private function checkHostingWebRoot(bool $ok): bool
    {
        $webRoot = (string) config('hostvim.hosting_web_root', '');
        if ($webRoot === '' || ! is_dir($webRoot)) {
            $this->warn('Hosting web kökü bulunamadı veya tanımsız: '.$webRoot);

            return $ok;
        }

        if (is_writable($webRoot)) {
            $this->info('Hosting web kökü yazılabilir: '.$webRoot);
        } else {
            $this->error('Hosting web kökü yazılamıyor (Engine dosya düzenleyemez): '.$webRoot);
            $this->comment('  sudo panelze-fix-hosting-perms');
            $ok = false;
        }

        return $ok;
    }
}
