<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\EngineApiService;
use Illuminate\Console\Command;

class PanelzePanelKafesApplyCommand extends Command
{
    protected $signature = 'panelze:panelkafes-apply
                            {--domain= : Yalnızca bu alan adı}
                            {--all : Tüm aktif domainler}';

    protected $description = 'PanelKafes izolasyonunu mevcut sitelere uygular (Linux kullanıcı + PHP-FPM pool)';

    public function handle(EngineApiService $engine): int
    {
        if ($this->option('all')) {
            $this->info('PanelKafes: tüm sitelere uygulanıyor…');
            try {
                $result = $engine->applyPanelKafesAll();
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
            $this->info($result['message'] ?? 'Tamamlandı.');
            foreach ($result['results'] ?? [] as $row) {
                $domain = (string) ($row['domain'] ?? '?');
                $ok = (bool) ($row['ok'] ?? false);
                $msg = (string) ($row['message'] ?? '');
                $this->line(($ok ? 'OK ' : 'FAIL ').$domain.($msg !== '' ? ' — '.$msg : ''));
            }

            return self::SUCCESS;
        }

        $domainName = strtolower(trim((string) $this->option('domain')));
        if ($domainName === '') {
            $this->error('--domain=ornek.com veya --all gerekli.');

            return self::FAILURE;
        }

        if (! Domain::query()->where('name', $domainName)->exists()) {
            $this->warn("Panel veritabanında kayıt yok: {$domainName} (engine yine de uygular)");
        }

        $this->info("PanelKafes: {$domainName}");
        try {
            $result = $engine->applyPanelKafesSite($domainName);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

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
}
