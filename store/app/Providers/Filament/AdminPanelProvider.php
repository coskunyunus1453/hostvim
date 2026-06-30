<?php

namespace App\Providers\Filament;

use App\Support\Filament\FilamentAssetVersion;
use App\Support\Filament\SystemFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('HostVim Yönetim')
            ->favicon(asset('favicon-32.png'))
            ->font('ui-sans-serif', provider: SystemFontProvider::class)
            ->spa()
            ->colors([
                'primary' => Color::hex('#C2410C'),
                'success' => Color::hex('#166534'),
                'warning' => Color::Orange,
                'danger' => Color::Red,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->navigationGroups([
                'Müşteriler & Siparişler',
                'Faturalama & Ödemeler',
                'Ürünler & Hizmetler',
                'Domain Yönetimi',
                'Sunucu & Altyapı',
                'Site İçeriği',
                'Tasarım & Yapılandırma',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                function (): string {
                    $version = rawurlencode(FilamentAssetVersion::query());
                    $assets = [
                        asset('vendor/livewire/livewire.min.js'),
                        asset("js/filament/support/support.js?v={$version}"),
                        asset("css/filament/filament/app.css?v={$version}"),
                    ];

                    return collect($assets)
                        ->map(fn (string $url): string => match (true) {
                            str_ends_with($url, '.css') => '<link rel="preload" href="'.e($url).'" as="style">',
                            default => '<link rel="preload" href="'.e($url).'" as="script">',
                        })
                        ->implode("\n")
                        ."\n<style>.fi-logo{font:600 1.25rem/1.2 ui-sans-serif,system-ui,sans-serif}</style>"
                        ."\n".$this->topbarActionsCss();
                },
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@livewire(\App\Livewire\AdminTopbarActions::class)'),
            );
    }

    /**
     * Topbar eylem simgeleri + bildirim acilir menusu icin tema-duyarli CSS.
     * Filament app.css Tailwind utility'lerini icermediginden bu bilesenler
     * kendi "hv-" siniflarini kullanir ve stiller buradan gelir.
     */
    private function topbarActionsCss(): string
    {
        return <<<'HTML'
<style>
[x-cloak]{display:none !important}
.hv-topbar{display:flex;align-items:center;gap:.625rem}
.hv-topbar__group{display:flex;align-items:center;gap:.25rem;padding:.25rem .375rem;border-radius:9999px;background:rgba(120,120,135,.08);border:1px solid rgba(120,120,135,.16)}
.dark .hv-topbar__group{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.1)}
.hv-topbar__divider{display:inline-block;width:1px;height:1.5rem;background:rgba(120,120,135,.28)}
.dark .hv-topbar__divider{background:rgba(255,255,255,.14)}
.hv-notif{position:relative;display:inline-flex;align-items:center}
.hv-notif__badge{position:absolute;top:-.25rem;inset-inline-end:-.25rem;min-width:1.05rem;height:1.05rem;padding:0 .25rem;display:inline-flex;align-items:center;justify-content:center;border-radius:9999px;background:#dc2626;color:#fff;font-size:.625rem;font-weight:700;line-height:1;box-shadow:0 0 0 2px #fff}
.dark .hv-notif__badge{box-shadow:0 0 0 2px #111827}
.hv-notif__panel{position:absolute;inset-inline-end:0;top:calc(100% + .5rem);width:22rem;max-width:calc(100vw - 1.5rem);max-height:28rem;display:flex;flex-direction:column;overflow:hidden;border-radius:.75rem;background:#fff;color:#111827;border:1px solid rgba(17,24,39,.08);box-shadow:0 12px 34px rgba(0,0,0,.18);z-index:50}
.dark .hv-notif__panel{background:#0f172a;color:#e5e7eb;border-color:rgba(255,255,255,.1)}
.hv-notif__header{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.75rem 1rem;border-bottom:1px solid rgba(17,24,39,.08)}
.dark .hv-notif__header{border-bottom-color:rgba(255,255,255,.08)}
.hv-notif__h-title{font-size:.875rem;font-weight:600}
.hv-notif__h-sub{font-size:.75rem;opacity:.6;margin-top:.0625rem}
.hv-notif__markall{font-size:.75rem;font-weight:500;color:#2563eb;background:none;border:0;cursor:pointer;padding:0}
.dark .hv-notif__markall{color:#60a5fa}
.hv-notif__list{flex:1;overflow-y:auto}
.hv-notif__item{display:flex;gap:.75rem;width:100%;text-align:start;padding:.75rem 1rem;border:0;border-bottom:1px solid rgba(17,24,39,.06);background:none;cursor:pointer;color:inherit}
.hv-notif__item:hover{background:rgba(17,24,39,.04)}
.dark .hv-notif__item{border-bottom-color:rgba(255,255,255,.05)}
.dark .hv-notif__item:hover{background:rgba(255,255,255,.05)}
.hv-notif__item--unread{background:rgba(37,99,235,.06)}
.dark .hv-notif__item--unread{background:rgba(96,165,250,.08)}
.hv-notif__icon{margin-top:.125rem;flex:0 0 auto;display:flex;height:2.25rem;width:2.25rem;align-items:center;justify-content:center;border-radius:.5rem}
.hv-notif__icon svg{height:1.25rem;width:1.25rem}
.hv-notif__body{min-width:0;flex:1}
.hv-notif__row{display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem}
.hv-notif__name{font-size:.875rem;font-weight:500}
.hv-notif__dot{margin-top:.375rem;height:.5rem;width:.5rem;flex:0 0 auto;border-radius:9999px;background:#2563eb}
.dark .hv-notif__dot{background:#60a5fa}
.hv-notif__desc{margin-top:.125rem;font-size:.75rem;line-height:1.45;opacity:.78}
.hv-notif__time{margin-top:.25rem;display:block;font-size:.6875rem;opacity:.5}
.hv-notif__empty{padding:2.5rem 1rem;text-align:center}
.hv-notif__empty svg{height:2rem;width:2rem;margin:0 auto;opacity:.4}
.hv-notif__empty p{margin-top:.75rem;font-size:.875rem;opacity:.6}
.hv-notif__icon--primary{background:rgba(37,99,235,.12);color:#1d4ed8}
.dark .hv-notif__icon--primary{color:#93c5fd}
.hv-notif__icon--success{background:rgba(22,163,74,.12);color:#15803d}
.dark .hv-notif__icon--success{color:#86efac}
.hv-notif__icon--warning{background:rgba(217,119,6,.14);color:#b45309}
.dark .hv-notif__icon--warning{color:#fcd34d}
.hv-notif__icon--danger{background:rgba(220,38,38,.12);color:#b91c1c}
.dark .hv-notif__icon--danger{color:#fca5a5}
.hv-notif__icon--info{background:rgba(2,132,199,.12);color:#0369a1}
.dark .hv-notif__icon--info{color:#7dd3fc}
.hv-notif__icon--gray{background:rgba(120,120,135,.14);color:#374151}
.dark .hv-notif__icon--gray{color:#d1d5db}
</style>
HTML;
    }
}
