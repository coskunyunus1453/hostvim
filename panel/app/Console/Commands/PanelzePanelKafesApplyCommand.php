<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\User;
use App\Services\EngineApiService;
use Illuminate\Console\Command;

class PanelzePanelKafesApplyCommand extends Command
{
    protected $signature = 'panelze:panelkafes-apply
                            {--domain= : Yalnızca bu alan adı}
                            {--all : Tüm aktif domainler}';

    protected $description = 'PanelKafes izolasyonunu sitelere uygular (Linux kullanıcı + PHP-FPM + paket bazlı CPU/RAM cgroup limiti)';

    public function handle(EngineApiService $engine): int
    {
        if ($this->option('all')) {
            $domains = Domain::query()->where('status', 'active')->get();
            $this->info("PanelKafes: {$domains->count()} aktif siteye paket bazlı CPU/RAM limitleri uygulanıyor…");
            $ok = 0;
            $fail = 0;
            foreach ($domains as $domain) {
                [$cpu, $mem] = $this->limitsForDomain($domain);
                try {
                    $result = $engine->applyPanelKafesSite($domain->name, $cpu, $mem);
                    if (! empty($result['error'])) {
                        throw new \RuntimeException((string) $result['error']);
                    }
                    $this->line('OK   '.$domain->name.'  ['.$this->limitText($cpu, $mem).']');
                    $ok++;
                } catch (\Throwable $e) {
                    $this->line('FAIL '.$domain->name.' — '.$e->getMessage());
                    $fail++;
                }
            }
            $this->info("Tamamlandı: {$ok} başarılı, {$fail} hatalı.");

            return $fail > 0 ? self::FAILURE : self::SUCCESS;
        }

        $domainName = strtolower(trim((string) $this->option('domain')));
        if ($domainName === '') {
            $this->error('--domain=ornek.com veya --all gerekli.');

            return self::FAILURE;
        }

        $domain = Domain::query()->where('name', $domainName)->first();
        if (! $domain) {
            $this->warn("Panel veritabanında kayıt yok: {$domainName} (engine global varsayılanla uygular)");
        }
        [$cpu, $mem] = $domain ? $this->limitsForDomain($domain) : [0, 0];

        $this->info("PanelKafes: {$domainName}  [".$this->limitText($cpu, $mem).']');
        try {
            $result = $engine->applyPanelKafesSite($domainName, $cpu, $mem);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        if (! empty($result['error'])) {
            $this->error((string) $result['error']);

            return self::FAILURE;
        }

        $this->info($result['message'] ?? 'Tamamlandı.');
        if (! empty($result['cage_user'])) {
            $this->line('Linux kullanıcı: '.$result['cage_user']);
        }
        $status = $result['status'] ?? null;
        if (is_array($status)) {
            $this->line('Durum: '.json_encode($status, JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }

    /**
     * Sitenin sahibinin hosting paketindeki CPU/RAM limitleri.
     *
     * @return array{0:int,1:int} [cpuPercent, memoryMB] — 0 = engine global varsayılanı
     */
    private function limitsForDomain(Domain $domain): array
    {
        /** @var User|null $user */
        $user = $domain->user()->first();
        if (! $user) {
            return [0, 0];
        }
        $pkg = $user->hostingPackage()->first();
        if (! $pkg) {
            return [0, 0];
        }

        return [
            max(0, (int) ($pkg->cpu_limit ?? 0)),
            max(0, (int) ($pkg->memory_limit_mb ?? 0)),
        ];
    }

    private function limitText(int $cpu, int $mem): string
    {
        $c = $cpu > 0 ? "CPU {$cpu}%" : 'CPU global';
        $m = $mem > 0 ? "RAM {$mem}MB" : 'RAM global';

        return $c.', '.$m;
    }
}
