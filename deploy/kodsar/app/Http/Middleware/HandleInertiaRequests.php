<?php

namespace App\Http\Middleware;

use App\Helpers\CacheHelper;
use App\Models\Setting;
use App\Helpers\ThemeHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $logoPath = Setting::get('site_logo', '');
        $faviconPath = Setting::get('site_favicon', '');

        $logoUrl = $logoPath && Storage::disk('public')->exists($logoPath)
            ? Storage::disk('public')->url($logoPath)
            : null;

        $faviconUrl = $faviconPath && Storage::disk('public')->exists($faviconPath)
            ? Storage::disk('public')->url($faviconPath)
            : null;

        $activeTheme = ThemeHelper::getActiveTheme();
        $themeSlug = $activeTheme ? $activeTheme->slug : 'ecommerce';

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'seo' => [
                'title' => config('app.name', 'Kodsar'),
                'description' => config('app.description', 'Modern e-ticaret platformu'),
                'keywords' => '',
                'og_image' => asset('images/og-image.jpg'),
            ],
            'site' => [
                'logo' => [
                    'url' => $logoUrl,
                    'width' => Setting::get('site_logo_width', '200'),
                    'height' => Setting::get('site_logo_height', '60'),
                ],
                'favicon' => [
                    'url' => $faviconUrl,
                ],
            ],
            'theme' => [
                'slug' => $themeSlug,
                'name' => $activeTheme ? $activeTheme->name : 'E-Ticaret Teması',
            ],
            'frontend' => [
                'menus' => [
                    'header_main' => CacheHelper::getMenus('header_main'),
                ],
                'footerMenus' => [
                    'footer_links' => CacheHelper::getMenus('footer_links'),
                    'footer_legal' => CacheHelper::getMenus('footer_legal'),
                ],
                'cartCount' => CacheHelper::getCartCount($request->user()?->id),
            ],
        ];
    }
}
