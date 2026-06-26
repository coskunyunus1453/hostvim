<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\PageContentService;
use App\Services\SeoService;
use Illuminate\Database\Eloquent\Collection;

class LandingController extends Controller
{
    public function hosting(PageContentService $content, SeoService $seo)
    {
        $data = $content->hosting();
        $products = $this->products(fn (Product $p) => $p->isHosting());

        return $this->render('hosting.index', 'Web Hosting', $data, $products, $seo);
    }

    public function cloud(PageContentService $content, SeoService $seo)
    {
        $data = $content->cloud();
        $products = $this->products(fn (Product $p) => $p->isCloudProvision());

        return $this->render('cloud.index', 'Bulut Sunucu', $data, $products, $seo);
    }

    /**
     * Aktif urunleri yukleyip provision tipine gore filtreler.
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    protected function products(callable $filter)
    {
        return Product::query()
            ->where('is_active', true)
            ->with('category')
            ->orderBy('sort_order')
            ->get()
            ->filter($filter)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function render(string $routeName, string $label, array $data, $products, SeoService $seo)
    {
        $breadcrumbs = [
            ['label' => 'Ana Sayfa', 'url' => route('home')],
            ['label' => $label, 'url' => null],
        ];

        return view('landing.page', [
            'content' => $data,
            'products' => $products,
            'pageLabel' => $label,
            'seo' => $seo->forLanding($routeName, $data['seo'] ?? []),
            'breadcrumbs' => $breadcrumbs,
            'schemas' => array_values(array_filter([
                $seo->breadcrumbSchema($breadcrumbs),
                $this->faqSchema($data['faqs'] ?? []),
            ])),
        ]);
    }

    /**
     * @param  array<int, array{q?: string, a?: string}>  $faqs
     * @return array<string, mixed>|null
     */
    protected function faqSchema(array $faqs): ?array
    {
        $entities = [];
        foreach ($faqs as $faq) {
            if (empty($faq['q']) || empty($faq['a'])) {
                continue;
            }
            $entities[] = [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
            ];
        }

        if ($entities === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }
}
