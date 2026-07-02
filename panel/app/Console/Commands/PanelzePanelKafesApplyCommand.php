<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\PanelKafesApplyService;
use Illuminate\Console\Command;

class PanelzePanelKafesApplyCommand extends Command
{
    protected $signature = 'panelze:panelkafes-apply
                            {--domain= : Yalnızca bu alan adı}
                            {--all : Tüm aktif domainler}';

    protected $description = 'PanelKafes izolasyonunu sitelere uygular (Linux kullanıcı + PHP-FPM + paket bazlı CPU/RAM cgroup limiti)';

    public function handle(PanelKafesApplyService $apply): int
    {
        if ($this->option('all')) {
            $result = $apply->applyAllActive();
            foreach ($result['results'] as $row) {
                if ($row['ok'] ?? false) {
                    $this->line('OK   '.$row['domain'].'  ['.$this->limitText((int) ($row['cpu'] ?? 0), (int) ($row['memory_mb'] ?? 0)).']');
                } else {
                    $this->line('FAIL '.$row['domain'].' — '.($row['error'] ?? '?'));
                }
            }
            $this->info("Tamamlandı: {$result['ok']} başarılı, {$result['failed']} hatalı.");

            return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
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
        [$cpu, $mem] = $domain ? $apply->limitsForDomain($domain) : [0, 0];

        $this->info("PanelKafes: {$domainName}  [".$this->limitText($cpu, $mem).']');
        try {
            $result = $apply->applySite($domainName);
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

    private function limitText(int $cpu, int $mem): string
    {
        $c = $cpu > 0 ? "CPU {$cpu}%" : 'CPU global';
        $m = $mem > 0 ? "RAM {$mem}MB" : 'RAM global';

        return $c.', '.$m;
    }
}
