<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class HostvimBlogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Hosting Rehberi', 'slug' => 'hosting-rehberi', 'description' => 'Web hosting seçimi, performans ve taşıma rehberleri.', 'sort_order' => 1],
            ['name' => 'Domain ve DNS', 'slug' => 'domain-dns', 'description' => 'Alan adı kaydı, uzantılar ve DNS yönetimi.', 'sort_order' => 2],
            ['name' => 'Sunucu ve VPS', 'slug' => 'sunucu-vps', 'description' => 'VPS, bulut sunucu ve kaynak planlama.', 'sort_order' => 3],
            ['name' => 'WordPress ve CMS', 'slug' => 'wordpress-cms', 'description' => 'WordPress ve içerik yönetim sistemleri için hosting.', 'sort_order' => 4],
            ['name' => 'Güvenlik ve SSL', 'slug' => 'guvenlik-ssl', 'description' => 'SSL, HTTPS ve site güvenliği.', 'sort_order' => 5],
        ];

        foreach ($categories as $cat) {
            BlogCategory::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        $authorId = User::query()->orderBy('id')->value('id');
        if (! $authorId) {
            $this->command?->error('Yazar (user) bulunamadı.');

            return;
        }

        $files = glob(database_path('seeders/blog/[0-9]*.php')) ?: [];
        sort($files);

        foreach ($files as $file) {
            /** @var array<string, mixed> $data */
            $data = require $file;
            $category = BlogCategory::where('slug', $data['category_slug'])->first();
            $slug = BlogPost::uniqueSlug($data['slug'], $data['title']);
            $image = 'blog/'.ltrim((string) $data['image'], '/');

            BlogPost::updateOrCreate(
                ['slug' => $slug],
                [
                    'blog_category_id' => $category?->id,
                    'user_id' => $authorId,
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'excerpt' => $data['excerpt'],
                    'featured_image' => $image,
                    'og_image' => $image,
                    'meta_title' => $data['meta_title'],
                    'meta_description' => $data['meta_description'],
                    'meta_keywords' => $data['meta_keywords'] ?? null,
                    'is_published' => true,
                    'published_at' => now()->subDays((int) ($data['days_ago'] ?? 0)),
                    'no_index' => false,
                ]
            );
        }

        $this->command?->info('Hostvim blog yazıları eklendi: '.count($files));
    }
}
