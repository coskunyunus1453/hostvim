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
    /**
     * Bölüm varsayılanları: [enabled, changefreq, priority].
     * Admin "Sitemap" sayfasından bölüm bazında değiştirilebilir.
     *
     * @var array<string, array{0:bool,1:string,2:string}>
     */
    public const SECTION_DEFAULTS = [
        'home' => [true, 'daily', '1.0'],
        'products' => [true, 'weekly', '0.8'],
        'categories' => [true, 'weekly', '0.8'],
        'domain' => [true, 'weekly', '0.8'],
        'pages' => [true, 'monthly', '0.6'],
        'blog' => [true, 'weekly', '0.7'],
        'contact' => [true, 'monthly', '0.6'],
    ];

    public function index(SettingsService $settings, CacheService $cache): Response
    {
        if (! filter_var($settings->get('seo_sitemap_enabled', '1'), FILTER_VALIDATE_BOOLEAN)) {
            abort(404);
        }

        $xml = $cache->remember('sitemap_xml', function () use ($settings) {
            $urls = [];

            if ($this->sectionEnabled($settings, 'home')) {
                [, $freq, $pri] = $this->section($settings, 'home');
                $urls[] = $this->entry(route('home'), now(), $freq, $pri);
            }

            if ($this->sectionEnabled($settings, 'products')) {
                [, $freq, $pri] = $this->section($settings, 'products');
                $urls[] = $this->entry(route('products.index'), now(), $freq, $pri);

                Product::where('is_active', true)->where('no_index', false)
                    ->with('category')
                    ->each(function ($product) use (&$urls, $freq, $pri) {
                        if ($product->category) {
                            $urls[] = $this->entry(
                                route('products.show', [$product->category->slug, $product->slug]),
                                $product->updated_at,
                                $freq,
                                $pri
                            );
                        }
                    });
            }

            if ($this->sectionEnabled($settings, 'categories')) {
                [, $freq, $pri] = $this->section($settings, 'categories');
                ProductCategory::where('is_active', true)->where('no_index', false)->each(function ($cat) use (&$urls, $freq, $pri) {
                    $urls[] = $this->entry(route('products.category', $cat->slug), $cat->updated_at, $freq, $pri);
                });
            }

            if ($this->sectionEnabled($settings, 'domain')) {
                [, $freq, $pri] = $this->section($settings, 'domain');
                $urls[] = $this->entry(route('domain.index'), now(), $freq, $pri);
                $urls[] = $this->entry(route('domain.value.index'), now(), $freq, $pri);
            }

            if ($this->sectionEnabled($settings, 'blog')) {
                [, $freq, $pri] = $this->section($settings, 'blog');
                $urls[] = $this->entry(route('blog.index'), now(), $freq, $pri);

                BlogPost::where('is_published', true)->where('no_index', false)->each(function ($post) use (&$urls, $freq, $pri) {
                    $urls[] = $this->entry(route('blog.show', $post->slug), $post->updated_at, $freq, $pri);
                });
            }

            if ($this->sectionEnabled($settings, 'pages')) {
                [, $freq, $pri] = $this->section($settings, 'pages');
                Page::where('is_published', true)->where('no_index', false)->each(function ($page) use (&$urls, $freq, $pri) {
                    $urls[] = $this->entry(route('pages.show', $page->slug), $page->updated_at, $freq, $pri);
                });
            }

            if ($this->sectionEnabled($settings, 'contact')) {
                [, $freq, $pri] = $this->section($settings, 'contact');
                $urls[] = $this->entry(route('contact.index'), now(), $freq, $pri);
            }

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

    private function sectionEnabled(SettingsService $settings, string $key): bool
    {
        $default = self::SECTION_DEFAULTS[$key][0] ?? true;

        return filter_var(
            $settings->get("sitemap_{$key}_enabled", $default ? '1' : '0'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * @return array{0:bool,1:string,2:string} [enabled, changefreq, priority]
     */
    private function section(SettingsService $settings, string $key): array
    {
        [$defEnabled, $defFreq, $defPri] = self::SECTION_DEFAULTS[$key] ?? [true, 'weekly', '0.5'];

        $freq = trim((string) $settings->get("sitemap_{$key}_changefreq", '')) ?: $defFreq;
        $pri = trim((string) $settings->get("sitemap_{$key}_priority", '')) ?: $defPri;

        return [$this->sectionEnabled($settings, $key), $freq, $pri];
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
