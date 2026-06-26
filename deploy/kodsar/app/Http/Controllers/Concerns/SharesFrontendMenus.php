<?php

namespace App\Http\Controllers\Concerns;

use App\Helpers\CacheHelper;
use Illuminate\Support\Facades\Auth;

trait SharesFrontendMenus
{
    protected function frontendMenusPayload(): array
    {
        return [
            'menus' => [
                'header_main' => CacheHelper::getMenus('header_main'),
            ],
            'footerMenus' => [
                'footer_links' => CacheHelper::getMenus('footer_links'),
                'footer_legal' => CacheHelper::getMenus('footer_legal'),
            ],
            'cartCount' => CacheHelper::getCartCount(Auth::id()),
        ];
    }
}
