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
                        ."\n<style>.fi-logo{font:600 1.25rem/1.2 ui-sans-serif,system-ui,sans-serif}</style>";
                },
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@livewire(\App\Livewire\AdminTopbarActions::class)'),
            );
    }
}
