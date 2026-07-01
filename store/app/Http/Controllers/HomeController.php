<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\HeroSection;
use App\Models\ProductCategory;
use App\Models\Testimonial;
use App\Services\CacheService;
use App\Services\SeoService;

class HomeController extends Controller
{
    public function index(SeoService $seo, CacheService $cache)
    {
        $hero = $cache->remember('home:hero', fn () => HeroSection::where('page', 'home')->where('is_active', true)->orderBy('sort_order')->first());
        $categories = $cache->remember('home:categories', fn () => ProductCategory::where('is_active', true)
            ->whereHas('activeProducts')
            ->with(['activeProducts' => fn ($q) => $q->limit(3)])
            ->orderBy('sort_order')
            ->get());
        $features = $cache->remember('home:features', fn () => Feature::where('is_active', true)->orderBy('sort_order')->limit(6)->get());
        $testimonials = $cache->remember('home:testimonials', fn () => Testimonial::where('is_active', true)->orderBy('sort_order')->limit(6)->get());
        $faqs = $cache->remember('home:faqs', fn () => Faq::where('is_active', true)->orderBy('sort_order')->limit(8)->get());
        $posts = $cache->remember('home:posts', fn () => BlogPost::where('is_published', true)
            ->with('category')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get());

        $breadcrumbs = [
            ['label' => 'Ana Sayfa', 'url' => null],
        ];

        $schemas = array_values(array_filter([
            $seo->organizationSchema(),
            $seo->websiteSchema(),
            $seo->faqSchema($faqs),
        ]));

        return view('home', [
            'hero' => $hero,
            'categories' => $categories,
            'features' => $features,
            'testimonials' => $testimonials,
            'faqs' => $faqs,
            'posts' => $posts,
            'seo' => $seo->forHome(),
            'breadcrumbs' => $breadcrumbs,
            'schemas' => $schemas,
        ]);
    }
}
