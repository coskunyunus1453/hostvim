<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\SeoService;

class PageController extends Controller
{
    public function show(string $slug, SeoService $seo)
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->firstOrFail();

        $breadcrumbs = [
            ['label' => 'Ana Sayfa', 'url' => route('home')],
            ['label' => $page->title, 'url' => null],
        ];

        return view('pages.show', [
            'page' => $page,
            'seo' => $seo->forPage($page),
            'breadcrumbs' => $breadcrumbs,
            'schemas' => [$seo->breadcrumbSchema($breadcrumbs)],
        ]);
    }
}
