<?php

namespace App\Console\Commands;

use Database\Seeders\BlogPostsSeeder;
use Illuminate\Console\Command;

class SyncBlogPostsCommand extends Command
{
    protected $signature = 'panelze:sync-blog {--force : Onay sormadan çalıştır}';

    protected $description = 'SEO blog yazılarını katalog dosyaları ile senkronlar (TR)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Mevcut blog kayıtları katalog içeriği ile güncellenecek. Devam?', true)) {
            $this->info('İptal edildi.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => BlogPostsSeeder::class, '--force' => true]);

        $manifest = require database_path('seeders/blog/manifest.php');
        $this->info('Blog senkronlandı: '.count($manifest).' yazı (TR).');

        return self::SUCCESS;
    }
}
