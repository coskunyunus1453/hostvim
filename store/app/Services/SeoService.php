<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Str;

class SeoService
{
    public function __construct(
        protected SettingsService $settings,
    ) {}

    public function build(array $overrides = []): array
    {
        $siteName = $this->settings->get('site_name', 'HostVim');
        $suffix = $this->settings->get('seo_title_suffix', ' | ' . $siteName);

        $defaults = [
            'title' => $siteName,
            'description' => $this->settings->get('meta_description', ''),
            'keywords' => $this->settings->get('seo_default_keywords', ''),
            'canonical' => url()->current(),
            'robots' => 'index,follow',
            'og_type' => 'website',
            'og_image' => $this->absoluteUrl($this->settings->get('seo_default_og_image')),
            'twitter_card' => 'summary_large_image',
            'site_name' => $siteName,
            'locale' => 'tr_TR',
        ];

        $seo = array_merge($defaults, $overrides);

        $addSuffix = $overrides['title_suffix'] ?? true;
        if ($addSuffix && $suffix && ! str_contains($seo['title'], $siteName)) {
            $seo['title'] = rtrim($seo['title']) . $suffix;
        }

        if ($seo['og_image']) {
            $seo['og_image'] = $this->absoluteUrl($seo['og_image']);
        }

        return $seo;
    }

    public function forHome(): array
    {
        return $this->build([
            'title' => $this->settings->get('seo_home_title', $this->settings->get('site_name', 'HostVim')),
            'description' => $this->settings->get('seo_home_description', $this->settings->get('meta_description', '')),
            'canonical' => route('home'),
        ]);
    }

    public function forProduct(Product $product, ProductCategory $category): array
    {
        $robots = $product->no_index ? 'noindex,nofollow' : 'index,follow';

        return $this->build([
            'title' => $product->meta_title ?: $product->name,
            'description' => $product->meta_description ?: Str::limit(strip_tags($product->short_description ?? ''), 160),
            'keywords' => $product->meta_keywords,
            'canonical' => route('products.show', [$category->slug, $product->slug]),
            'robots' => $robots,
            'og_type' => 'product',
            'og_image' => $product->og_image ?: $this->settings->get('seo_default_og_image'),
        ]);
    }

    public function forCategory(ProductCategory $category): array
    {
        return $this->build([
            'title' => $category->meta_title ?: $category->name . ' Paketleri',
            'description' => $category->meta_description ?: Str::limit($category->description ?? '', 160),
            'keywords' => $category->meta_keywords,
            'canonical' => route('products.category', $category->slug),
            'robots' => $category->no_index ? 'noindex,nofollow' : 'index,follow',
        ]);
    }

    public function forPage(Page $page): array
    {
        return $this->build([
            'title' => $page->meta_title ?: $page->title,
            'description' => $page->meta_description ?: Str::limit(strip_tags($page->excerpt ?? $page->content ?? ''), 160),
            'keywords' => $page->meta_keywords,
            'canonical' => route('pages.show', $page->slug),
            'robots' => $page->no_index ? 'noindex,nofollow' : 'index,follow',
            'og_image' => $page->og_image ?: $this->settings->get('seo_default_og_image'),
        ]);
    }

    public function forBlogPost(BlogPost $post): array
    {
        return $this->build([
            'title' => $post->meta_title ?: $post->title,
            'description' => $post->meta_description ?: Str::limit(strip_tags($post->excerpt ?? $post->content ?? ''), 160),
            'keywords' => $post->meta_keywords,
            'canonical' => route('blog.show', $post->slug),
            'robots' => $post->no_index ? 'noindex,nofollow' : 'index,follow',
            'og_type' => 'article',
            'og_image' => $post->og_image ?: $post->featured_image ?: $this->settings->get('seo_default_og_image'),
            'published_at' => $post->published_at?->toIso8601String(),
            'modified_at' => $post->updated_at?->toIso8601String(),
            'author' => $post->author?->name,
        ]);
    }

    public function forBlogIndex(): array
    {
        return $this->build([
            'title' => $this->settings->get('seo_blog_title', 'Blog & Rehberler'),
            'description' => $this->settings->get('seo_blog_description', 'Hosting, sunucu ve domain rehberleri.'),
            'canonical' => route('blog.index'),
        ]);
    }

    public function forProductsIndex(): array
    {
        return $this->build([
            'title' => $this->settings->get('seo_products_title', 'Hosting & Sunucu Paketleri'),
            'description' => $this->settings->get('seo_products_description', 'Hosting, VPS, VDS ve domain paketleri.'),
            'canonical' => route('products.index'),
        ]);
    }

    public function forDomain(): array
    {
        return $this->build([
            'title' => 'Alan Adı Sorgula & Domain Kayıt',
            'description' => 'Domain müsaitlik kontrolü, .com, .com.tr ve yüzlerce uzantıda anında kayıt — HostVim.',
            'canonical' => route('domain.index'),
        ]);
    }

    public function forDomainValue(): array
    {
        return $this->build([
            'title' => 'Domain Değer Sorgulama — Alan Adı Tahmini Piyasa Değeri',
            'description' => 'Ücretsiz domain değer sorgulama: uzantı, uzunluk, marka potansiyeli ve kayıt yaşına göre tahmini alan adı değeri. Anında sonuç — HostVim.',
            'keywords' => 'domain değer sorgulama, alan adı değeri, domain appraisal, domain fiyat tahmini, domain değeri hesaplama',
            'canonical' => route('domain.value.index'),
        ]);
    }

    /**
     * Hosting / Bulut Sunucu gibi tanıtım (landing) sayfaları için SEO.
     *
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    public function forLanding(string $routeName, array $seo = []): array
    {
        $canonical = \Illuminate\Support\Facades\Route::has($routeName)
            ? route($routeName)
            : url()->current();

        return $this->build(array_filter([
            'title' => $seo['title'] ?? null,
            'description' => $seo['description'] ?? null,
            'keywords' => $seo['keywords'] ?? null,
            'og_image' => $seo['og_image'] ?? null,
            'canonical' => $canonical,
            'robots' => 'index,follow',
        ], fn ($v) => $v !== null && $v !== ''));
    }

    /** Özel / oturum sayfaları — arama motorlarından gizle */
    public function forPrivate(string $title, ?string $description = null): array
    {
        return $this->build([
            'title' => $title,
            'description' => $description ?? '',
            'canonical' => url()->current(),
            'robots' => 'noindex,nofollow',
            'title_suffix' => false,
        ]);
    }

    /** @param  array<int, array{label: string, url: string|null}>  $items */
    public function breadcrumbSchema(array $items): array
    {
        $list = [];
        foreach ($items as $i => $item) {
            $url = $item['url'] ?? ($i === array_key_last($items) ? url()->current() : null);
            if (! $url) {
                continue;
            }
            $list[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['label'],
                'item' => $url,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    public function organizationSchema(): array
    {
        $siteName = $this->settings->get('site_name', 'HostVim');
        $orgUrl = rtrim((string) $this->settings->get('schema_org_url', config('app.url')), '/');
        $homeUrl = rtrim((string) config('app.url'), '/');

        if ($orgUrl === '' || str_contains($orgUrl, '/iletisim')) {
            $orgUrl = $homeUrl;
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->settings->get('schema_org_name', $siteName),
            'url' => $orgUrl,
            'logo' => $this->absoluteUrl($this->settings->get('schema_org_logo')),
            'email' => $this->settings->get('contact_email'),
            'telephone' => $this->settings->get('contact_phone'),
            'address' => $this->settings->get('contact_address') ? [
                '@type' => 'PostalAddress',
                'addressLocality' => $this->settings->get('contact_address'),
                'addressCountry' => 'TR',
            ] : null,
            'sameAs' => array_values(array_filter([
                $this->settings->get('social_facebook'),
                $this->settings->get('social_twitter'),
                $this->settings->get('social_instagram'),
                $this->settings->get('social_linkedin'),
            ])),
        ]);
    }

    public function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->settings->get('site_name', 'HostVim'),
            'url' => config('app.url'),
        ];
    }

    public function productSchema(Product $product, ProductCategory $category): array
    {
        $price = $product->price_monthly ?? $product->price_yearly ?? $product->price_onetime;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => strip_tags($product->short_description ?? ''),
            'category' => $category->name,
            'url' => route('products.show', [$category->slug, $product->slug]),
            'brand' => [
                '@type' => 'Brand',
                'name' => $this->settings->get('site_name', 'HostVim'),
            ],
            'offers' => $price ? [
                '@type' => 'Offer',
                'price' => number_format((float) $price, 2, '.', ''),
                'priceCurrency' => $product->currency ?? 'TRY',
                'availability' => 'https://schema.org/InStock',
                'url' => route('products.show', [$category->slug, $product->slug]),
            ] : null,
        ]);
    }

    public function articleSchema(BlogPost $post): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => Str::limit(strip_tags($post->excerpt ?? $post->content ?? ''), 200),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => $post->author ? [
                '@type' => 'Person',
                'name' => $post->author->name,
            ] : null,
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->settings->get('site_name', 'HostVim'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $this->absoluteUrl($this->settings->get('schema_org_logo')),
                ],
            ],
            'image' => $this->absoluteUrl($post->og_image ?: $post->featured_image),
            'mainEntityOfPage' => route('blog.show', $post->slug),
        ]);
    }

    public function faqSchema(iterable $faqs): ?array
    {
        $entities = [];
        foreach ($faqs as $faq) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($faq->answer),
                ],
            ];
        }

        if (empty($entities)) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    protected function absoluteUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }
}
