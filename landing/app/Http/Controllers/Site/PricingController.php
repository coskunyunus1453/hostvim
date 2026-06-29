<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\SitePage;
use App\Services\Licensing\PricingPresenter;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(PricingPresenter $pricing): View
    {
        $locale = app()->getLocale();

        $intro = SitePage::query()
            ->published()
            ->forLocale($locale)
            ->where('slug', 'pricing')
            ->first();

        return view('site.pricing', [
            'intro' => $intro,
            'pricing' => $pricing->build($locale),
            'seoCanonical' => landing_url_with_lang(route('site.pricing', absolute: true), $locale),
        ]);
    }
}
