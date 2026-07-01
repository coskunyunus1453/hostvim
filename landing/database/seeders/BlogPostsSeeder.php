<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BlogPostsSeeder extends Seeder
{
    public function run(): void
    {
        $basePath = database_path('seeders/blog');
        /** @var list<array<string, mixed>> $manifest */
        $manifest = require $basePath.'/manifest.php';

        foreach ($manifest as $item) {
            $filePath = $basePath.'/'.$item['file'];
            if (! File::isFile($filePath)) {
                $this->command?->warn("Blog içeriği bulunamadı: {$item['file']}");

                continue;
            }

            $category = BlogCategory::query()
                ->where('locale', 'tr')
                ->where('slug', $item['category_slug'])
                ->first();

            if (! $category) {
                $this->command?->warn("Kategori bulunamadı: {$item['category_slug']} ({$item['slug']})");

                continue;
            }

            $content = trim(File::get($filePath));

            BlogPost::query()->updateOrCreate(
                ['locale' => 'tr', 'slug' => $item['slug']],
                [
                    'blog_category_id' => $category->id,
                    'title' => $item['title'],
                    'meta_title' => $item['meta_title'],
                    'meta_description' => $item['meta_description'],
                    'excerpt' => $item['excerpt'],
                    'og_image' => $item['og_image'],
                    'robots' => 'index,follow',
                    'content' => $content,
                    'is_published' => true,
                    'published_at' => now()->subDays((int) $item['published_days_ago']),
                ]
            );
        }

        $this->command?->info('SEO blog yazıları senkronlandı: '.count($manifest).' yazı (TR).');
    }
}
