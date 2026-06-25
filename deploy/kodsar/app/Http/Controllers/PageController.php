<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Helpers\ThemeHelper;
use App\Models\Page;
use App\Models\SeoSetting;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $headerMenu = CacheHelper::getMenus('header_main');
        $footerMenu = CacheHelper::getMenus('footer_links');
        $footerLegalMenu = CacheHelper::getMenus('footer_legal');
        $cartCount = CacheHelper::getCartCount(Auth::id());

        $seoSettings = SeoSetting::getSettings();
        $title = $page->meta_title ?: $page->title;
        $description = $page->meta_description ?: strip_tags(substr($page->content, 0, 160));

        $seoMetaTags = '';
        $seoMetaTags .= '<meta name="title" content="'.htmlspecialchars($title).'">'."\n";
        $seoMetaTags .= '<meta name="description" content="'.htmlspecialchars($description).'">'."\n";
        if ($page->meta_keywords) {
            $seoMetaTags .= '<meta name="keywords" content="'.htmlspecialchars($page->meta_keywords).'">'."\n";
        }
        $seoMetaTags .= '<meta property="og:title" content="'.htmlspecialchars($title).'">'."\n";
        $seoMetaTags .= '<meta property="og:description" content="'.htmlspecialchars($description).'">'."\n";
        $seoMetaTags .= '<meta property="og:url" content="'.route('page.show', $page->slug).'">'."\n";

        $activeTheme = ThemeHelper::getActiveTheme();
        $themeSlug = $activeTheme ? $activeTheme->slug : 'ecommerce';

        return Inertia::render('Page/Show', [
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'updated_at' => $page->updated_at?->format('d.m.Y'),
            ],
            'theme' => $activeTheme ? [
                'slug' => $activeTheme->slug,
                'name' => $activeTheme->name,
            ] : ['slug' => 'ecommerce', 'name' => 'E-Ticaret'],
            'seoMetaTags' => $seoMetaTags,
            'menus' => [
                'header_main' => $headerMenu,
            ],
            'footerMenus' => [
                'footer_links' => $footerMenu,
                'footer_legal' => $footerLegalMenu,
            ],
            'cartCount' => $cartCount,
            'themeSlug' => $themeSlug,
        ]);
    }
}
