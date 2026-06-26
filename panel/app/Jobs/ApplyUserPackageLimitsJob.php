<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\EngineApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Kullanıcının hosting paketindeki CPU/RAM limitini, kullanıcının tüm aktif
 * sitelerine PanelKafes (systemd cgroup) ile yeniden uygular.
 *
 * Paket atandığında/değiştiğinde (admin veya billing senkronu) tetiklenir.
 * Paket yoksa veya limit 0 ise engine global varsayılanına döner.
 */
class ApplyUserPackageLimitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public int $userId) {}

    public function handle(EngineApiService $engine): void
    {
        $user = User::query()->with('hostingPackage')->find($this->userId);
        if (! $user) {
            return;
        }

        $pkg = $user->hostingPackage;
        $cpu = $pkg ? max(0, (int) ($pkg->cpu_limit ?? 0)) : 0;
        $mem = $pkg ? max(0, (int) ($pkg->memory_limit_mb ?? 0)) : 0;

        foreach ($user->domains()->where('status', 'active')->get() as $domain) {
            try {
                $res = $engine->applyPanelKafesSite($domain->name, $cpu, $mem);
                if (! empty($res['error'])) {
                    Log::warning('panelze.apply_user_package_limits.site_failed', [
                        'user_id' => $this->userId,
                        'domain' => $domain->name,
                        'error' => $res['error'],
                    ]);
                }
            } catch (\Throwable $e) {
                // Bir sitedeki hata diğerlerini durdurmasın.
                Log::warning('panelze.apply_user_package_limits.exception', [
                    'user_id' => $this->userId,
                    'domain' => $domain->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
