<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\CacheService;
use App\Services\SettingsService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(SettingsService $settings, CacheService $cache): Response
    {
        if (! filter_var($settings->get('seo_sitemap_enabled', '1'), FILTER_VALIDATE_BOOLEAN)) {
            abort(404);
        }

        $xml = $cache->remember('sitemap_xml', function () {
            $urls = [];

            $urls[] = $this->entry(route('home'), now(), 'daily', '1.0');
            $urls[] = $this->entry(route('products.index'), now(), 'daily', '0.9');
            $urls[] = $this->entry(route('domain.index'), now(), 'weekly', '0.8');
            $urls[] = $this->entry(route('blog.index'), now(), 'daily', '0.8');
            $urls[] = $this->entry(route('contact.index'), now(), 'monthly', '0.6');

            ProductCategory::where('is_active', true)->where('no_index', false)->each(function ($cat) use (&$urls) {
                $urls[] = $this->entry(route('products.category', $cat->slug), $cat->updated_at, 'weekly', '0.8');
            });

            Product::where('is_active', true)->where('no_index', false)
                ->with('category')
                ->each(function ($product) use (&$urls) {
                    if ($product->category) {
                        $urls[] = $this->entry(
                            route('products.show', [$product->category->slug, $product->slug]),
                            $product->updated_at,
                            'weekly',
                            '0.7'
                        );
                    }
                });

            Page::where('is_published', true)->where('no_index', false)->each(function ($page) use (&$urls) {
                $urls[] = $this->entry(route('pages.show', $page->slug), $page->updated_at, 'monthly', '0.6');
            });

            BlogPost::where('is_published', true)->where('no_index', false)->each(function ($post) use (&$urls) {
                $urls[] = $this->entry(route('blog.show', $post->slug), $post->updated_at, 'weekly', '0.7');
            });

            $body = collect($urls)->map(fn ($u) => $this->urlXml($u))->implode('');

            return '<?xml version="1.0" encoding="UTF-8"?>' .
                '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' .
                $body .
                '</urlset>';
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=' . max(300, $cache->queryTtl()),
        ]);
    }

    protected function entry(string $loc, $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod?->toAtomString() ?? now()->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }

    protected function urlXml(array $u): string
    {
        return '<url>' .
            '<loc>' . e($u['loc']) . '</loc>' .
            '<lastmod>' . e($u['lastmod']) . '</lastmod>' .
            '<changefreq>' . e($u['changefreq']) . '</changefreq>' .
            '<priority>' . e($u['priority']) . '</priority>' .
            '</url>';
    }
}
