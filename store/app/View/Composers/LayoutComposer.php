<?php

namespace App\View\Composers;

use App\Models\Menu;
use App\Models\ProductCategory;
use App\Services\BrandingService;
use App\Services\CacheService;
use App\Services\CampaignService;
use App\Services\CartService;
use App\Services\SeoService;
use App\Services\SettingsService;
use App\Services\ThemeService;
use App\View\Support\LayoutViewData;

class LayoutComposer
{
    public function __construct(
        protected SettingsService $settings,
        protected CartService $cart,
        protected CacheService $cache,
        protected ThemeService $theme,
        protected BrandingService $branding,
        protected CampaignService $campaigns,
        protected SeoService $seo,
    ) {}

    public function compose($view): void
    {
        $view->with(LayoutViewData::resolve(fn (): array => $this->buildPayload($view)));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload($view): array
    {
        $viewName = (string) $view->getName();
        // Giriş/kayıt: kampanya şeridi ve popup kapalı; header/footer site ile aynı kalır.
        $minimalShell = str_starts_with($viewName, 'auth.')
            || request()->routeIs('login', 'register');

        return [
            'siteSettings' => $this->settings->all(),
            'seo' => $this->resolveSeo($view),
            'siteName' => $this->settings->get('site_name', 'HostVim'),
            'siteLogoUrl' => $this->branding->logoUrl(),
            'siteLogoDarkUrl' => $this->branding->logoUrl(true),
            'siteFaviconUrl' => $this->branding->faviconUrl(),
            'siteLogoHeight' => $this->branding->logoHeight('header'),
            'siteLogoFooterHeight' => $this->branding->logoHeight('footer'),
            'siteLogoMobileHeight' => $this->branding->logoHeight('mobile'),
            'siteLogoShowName' => $this->branding->showSiteName(),
            'headerMenu' => $this->cache->remember('layout:header_menu', fn () => Menu::where('location', 'header')->with(['activeRootItems' => fn ($q) => $q->with(['activeChildren.page'])])->first()),
            'footerMenu' => $this->cache->remember('layout:footer_menu', fn () => Menu::where('location', 'footer')->with('activeItems.page')->first()),
            'navCategories' => $this->cache->remember('layout:nav_categories', fn () => ProductCategory::where('is_active', true)->orderBy('sort_order')->get()),
            'cartCount' => $this->cart->count(),
            'themeDefaultMode' => $this->theme->defaultMode(),
            'themeToggleEnabled' => $this->theme->isToggleEnabled(),
            'themeHeaderStyle' => $this->theme->headerStyle(),
            'themeFooterStyle' => $this->theme->footerStyle(),
            'themeHeaderSticky' => filter_var($this->settings->get('design_header_sticky', '1'), FILTER_VALIDATE_BOOLEAN),
            'themeHeaderBlur' => filter_var($this->settings->get('design_header_blur', '1'), FILTER_VALIDATE_BOOLEAN),
            'themeHeaderBorder' => filter_var($this->settings->get('design_header_border', '1'), FILTER_VALIDATE_BOOLEAN),
            'themeFooterShowStats' => filter_var($this->settings->get('design_footer_show_stats', '1'), FILTER_VALIDATE_BOOLEAN),
            'themePresetId' => $this->theme->presetId(),
            'themeShell' => $this->theme->shell(),
            'themeFontUrl' => $this->theme->fontUrl(),
            'themeCssVariables' => $this->theme->cssVariables(),
            'campaignService' => $this->campaigns,
            'flashCampaign' => $minimalShell ? null : $this->cache->remember('layout:flash_campaign', fn () => $this->campaigns->flashBar()),
            'popupCampaign' => $minimalShell ? null : $this->cache->remember('layout:popup_campaign', fn () => $this->campaigns->popup()),
            'panelLoginUrl' => rtrim((string) ($this->settings->get('panel_login_url') ?: config('panelze.panel_login_url', '')), '/').'/login',
            'accountUrl' => route('account.dashboard'),
            'isCustomerLoggedIn' => auth()->check() && ! auth()->user()?->is_admin,
            'minimalLayoutShell' => $minimalShell,
        ];
    }

    protected function resolveSeo($view): ?array
    {
        $existing = $view->getData()['seo'] ?? null;
        if (! empty($existing)) {
            return $existing;
        }

        $title = $this->privatePageTitle();
        if ($title === null) {
            return null;
        }

        return $this->seo->forPrivate($title);
    }

    protected function privatePageTitle(): ?string
    {
        return match (true) {
            request()->routeIs('cart.*') => 'Sepetim',
            request()->routeIs('checkout.*', 'payment.*') => 'Ödeme',
            request()->routeIs('login') => 'Giriş Yap',
            request()->routeIs('register') => 'Kayıt Ol',
            request()->routeIs('account.*') => 'Hesabım',
            default => null,
        };
    }
}
