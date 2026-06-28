<?php

namespace App\Providers;

use App\Models\HeroSection;
use App\Models\Menu;
use App\Models\MenuItem;
use App\View\Composers\LayoutComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $publicHtml = $this->app->basePath('public_html');

        if (is_dir($publicHtml)) {
            $this->app->usePublicPath($publicHtml);
        }

        config(['filament.default_filesystem_disk' => 'public']);
    }

    public function boot(): void
    {
        // Tum provider'lar (SettingsService dahil) yuklendikten SONRA uygula.
        // boot() icinde dogrudan cagrildiginda saglayici sirasi nedeniyle
        // SettingsService henuz hazir olmayabiliyor -> mail ayarlari uygulanmiyor
        // ve mailler "log" surucusune dusup musteriye gitmiyordu (web/console/queue hepsinde).
        $this->app->booted(function (): void {
            \App\Services\OutboundMailConfigurator::apply();
        });

        View::composer(['layouts.*', 'home', 'products.*', 'landing.*', 'blog.*', 'pages.*', 'cart.*', 'checkout.*', 'contact.*', 'auth.*', 'account.*', 'domain.*'], LayoutComposer::class);

        \App\Models\SiteSetting::observe(\App\Observers\SiteSettingObserver::class);
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
        \App\Models\Order::observe(\App\Observers\AdminNotificationOrderObserver::class);
        \App\Models\ContactMessage::observe(\App\Observers\AdminNotificationContactMessageObserver::class);
        \App\Models\SupportTicket::observe(\App\Observers\AdminNotificationSupportTicketObserver::class);
        \App\Models\SupportTicketMessage::observe(\App\Observers\AdminNotificationSupportTicketMessageObserver::class);
        \App\Models\CloudServer::observe(\App\Observers\AdminNotificationCloudServerObserver::class);
        \App\Models\PaymentMethod::observe(\App\Observers\PaymentMethodObserver::class);

        $seoObserver = \App\Observers\SeoContentObserver::class;
        foreach ([\App\Models\Product::class, \App\Models\ProductCategory::class, \App\Models\Page::class, \App\Models\BlogPost::class, Menu::class, MenuItem::class, HeroSection::class, \App\Models\Campaign::class] as $model) {
            $model::observe($seoObserver);
        }
    }
}
