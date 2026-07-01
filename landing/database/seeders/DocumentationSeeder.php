<?php

namespace Database\Seeders;

use App\Models\DocPage;
use App\Support\DocumentationCatalog;
use Illuminate\Database\Seeder;

class DocumentationSeeder extends Seeder
{
    public function run(): void
    {
        $idsByLocaleSlug = [];

        foreach (DocumentationCatalog::pages() as $def) {
            foreach (['tr', 'en'] as $locale) {
                $data = $def[$locale];
                $page = DocPage::query()->updateOrCreate(
                    ['locale' => $locale, 'slug' => $def['slug']],
                    [
                        'title' => $data['title'],
                        'meta_description' => $data['meta'],
                        'content' => $data['content'],
                        'sort_order' => $def['sort_order'],
                        'is_published' => true,
                        'parent_id' => null,
                    ]
                );
                $idsByLocaleSlug[$locale][$def['slug']] = $page->id;
            }
        }

        foreach (DocumentationCatalog::pages() as $def) {
            if ($def['parent'] === null) {
                continue;
            }
            foreach (['tr', 'en'] as $locale) {
                $parentId = $idsByLocaleSlug[$locale][$def['parent']] ?? null;
                DocPage::query()
                    ->where('locale', $locale)
                    ->where('slug', $def['slug'])
                    ->update(['parent_id' => $parentId]);
            }
        }
    }
}
