<?php

namespace App\Support\Filament;

use Filament\FontProviders\LocalFontProvider;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/**
 * Harici font dosyası yüklemeden sistem fontunu kullanır (LCP için daha hızlı).
 */
class SystemFontProvider extends LocalFontProvider
{
    public function getHtml(string $family, ?string $url = null): Htmlable
    {
        return new HtmlString('');
    }
}
