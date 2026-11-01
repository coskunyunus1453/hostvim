<?php

namespace App\Console\Commands;

use Database\Seeders\PublicDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetPublicDemoCommand extends Command
{
    protected $signature = 'panelze:reset-public-demo
                            {--force : Production kontrolünü atla}';

    protected $description = 'Public demo hesabını ve örnek siteleri güvenli şekilde yeniler (müşteri verisine dokunmaz).';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')
            && ! filter_var((string) env('PANELZE_PUBLIC_DEMO_ENABLED', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->warn('PANELZE_PUBLIC_DEMO_ENABLED=0 — atlandı.');

            return self::SUCCESS;
        }

        $this->info('Public demo sıfırlanıyor…');
        Artisan::call('db:seed', ['--class' => PublicDemoSeeder::class, '--force' => true]);
        $this->output->write(Artisan::output());

        return self::SUCCESS;
    }
}
