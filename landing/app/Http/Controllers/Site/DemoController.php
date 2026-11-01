<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DemoController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $demo = config('panelze.public_demo', []);
        if (! ($demo['enabled'] ?? true)) {
            return redirect()->route('landing.home');
        }

        $locale = app()->getLocale();
        $tr = $locale === 'tr';

        return view('site.demo', [
            'tr' => $tr,
            'email' => (string) ($demo['email'] ?? 'demo@panelze.com'),
            'password' => (string) ($demo['password'] ?? 'DemoPanelze2026!'),
            'loginUrl' => (string) ($demo['login_url'] ?? 'https://panel.hostvim.com/login?demo=1'),
            'sites' => is_array($demo['sites'] ?? null) ? $demo['sites'] : [],
            'seoCanonical' => landing_url_with_lang(route('site.demo', absolute: true), $locale),
        ]);
    }
}
