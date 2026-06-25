<?php

namespace App\Jobs;

use App\Models\CloudServer;
use App\Services\Cloud\CloudSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;

class InstallPanelOnServerJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 180;

    public int $tries = 2;

    public function __construct(public int $serverId) {}

    public function uniqueId(): string
    {
        return 'install-panel-'.$this->serverId;
    }

    public function handle(CloudSettings $settings): void
    {
        $server = CloudServer::find($this->serverId);
        if ($server === null) {
            return;
        }

        $installUrl = $settings->remoteInstallUrl();
        if ($installUrl === '' || ! preg_match('#^https?://#', $installUrl)) {
            $this->fail($server, 'Panel kurulum URL\'i yapılandırılmamış.');

            return;
        }

        $ip = $server->ipv4;
        $password = $server->root_password;
        if (! $ip || ! $password) {
            $this->fail($server, 'IP veya root şifre eksik.');

            return;
        }

        if (! class_exists(SSH2::class)) {
            $this->fail($server, 'SSH kütüphanesi (phpseclib) yüklü değil.');

            return;
        }

        try {
            $ssh = new SSH2($ip, 22, 25);
            if (! $ssh->login('root', $password)) {
                $this->fail($server, 'SSH girişi başarısız (root şifre hatalı olabilir).');

                return;
            }

            $safeUrl = escapeshellarg($installUrl);
            $safeHost = escapeshellarg($server->hostname ?: 'panelze');
            // Kurulum uzun surdugu icin arka planda baslatip cikiyoruz.
            $cmd = 'export DEBIAN_FRONTEND=noninteractive PANELZE_AUTO_INSTALL=1; '
                .'hostnamectl set-hostname '.$safeHost.' || true; '
                .'nohup bash -c "curl -fsSL '.$safeUrl.' | bash" > /var/log/panelze-install.log 2>&1 & echo STARTED';

            $output = $ssh->exec($cmd);
            $ssh->disconnect();

            if (! str_contains((string) $output, 'STARTED')) {
                $this->fail($server, 'Kurulum komutu başlatılamadı.');

                return;
            }

            $server->update(['meta' => array_merge($server->meta ?? [], [
                'panel_install' => 'running',
                'panel_install_started_at' => now()->toIso8601String(),
            ])]);

            Log::info('cloud.panel_install.started', ['server_id' => $server->id, 'ip' => $ip]);
        } catch (\Throwable $e) {
            report($e);
            $this->fail($server, 'SSH hatası: '.$e->getMessage());
        }
    }

    private function fail(CloudServer $server, string $message): void
    {
        $server->update(['meta' => array_merge($server->meta ?? [], [
            'panel_install' => 'failed',
            'panel_install_error' => $message,
        ])]);
        Log::warning('cloud.panel_install.failed', ['server_id' => $server->id, 'error' => $message]);
    }
}
