<?php

namespace App\Console\Commands;

use Database\Seeders\DocumentationSeeder;
use Illuminate\Console\Command;

class SyncDocumentationCommand extends Command
{
    protected $signature = 'panelze:sync-docs {--force : Onay sormadan çalıştır}';

    protected $description = 'Panelze /docs dokümantasyon içeriğini DocumentationCatalog ile senkronlar (TR + EN)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Mevcut doc_pages kayıtları katalog içeriği ile güncellenecek. Devam?', true)) {
            $this->info('İptal edildi.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => DocumentationSeeder::class, '--force' => true]);
        $this->info('Dokümantasyon senkronlandı: '.count(\App\Support\DocumentationCatalog::pages()).' sayfa tanımı × 2 dil.');

        return self::SUCCESS;
    }
}
