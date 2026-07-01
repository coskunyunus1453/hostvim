<?php

namespace App\Console\Commands;

use App\Services\EngineApiService;
use App\Services\MailStackService;
use Illuminate\Console\Command;

class EnsureMailStackCommand extends Command
{
    protected $signature = 'panelze:ensure-mail-stack';

    protected $description = 'Roundcube webmail + posta yığınını kurar (yoksa)';

    public function handle(MailStackService $mailStack, EngineApiService $engine): int
    {
        $this->callSilent('panelze:repair-stack-installs');

        if ($mailStack->isWebmailStackInstalled()) {
            $this->info('mail-stack-webmail zaten kurulu.');
            $sync = $engine->mailProvisionSync();
            if (! empty($sync['error'])) {
                $this->warn('Mail provision: '.$sync['error']);
            } else {
                $this->info('Mail provision tamam.');
            }
            $this->callSilent('panelze:ensure-webmail-ssl', ['--all' => true]);

            return self::SUCCESS;
        }

        $this->info('mail-stack-webmail kuruluyor (birkaç dakika sürebilir)...');
        $result = $mailStack->ensureWebmailStack();
        if (! ($result['ok'] ?? false)) {
            $this->error($result['error'] ?? 'Kurulum başarısız');
            if (! empty($result['output'])) {
                $this->line($result['output']);
            }

            return self::FAILURE;
        }

        $sync = $engine->mailProvisionSync();
        if (! empty($sync['error'])) {
            $this->warn('Mail provision: '.$sync['error']);
        }

        $this->info('Tam posta + webmail kurulumu tamamlandı.');
        $this->callSilent('panelze:ensure-webmail-ssl', ['--all' => true]);

        return self::SUCCESS;
    }
}
