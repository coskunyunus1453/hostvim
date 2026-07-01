<?php

namespace App\Support;

final class AdminNavigation
{
    /**
     * @return list<array{label: string, route: string, active: list<string>}>
     */
    public static function topLevel(): array
    {
        return [
            [
                'label' => 'Özet',
                'route' => 'admin.dashboard',
                'active' => ['admin.dashboard'],
            ],
        ];
    }

    /**
     * @return list<array{id: string, label: string, items: list<array{label: string, route: string, active: list<string>}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'id' => 'saas',
                'label' => 'Lisans & Satış',
                'items' => [
                    ['label' => 'Lisans özeti', 'route' => 'admin.saas.dashboard', 'active' => ['admin.saas.dashboard']],
                    ['label' => 'Müşteriler', 'route' => 'admin.saas.customers.index', 'active' => ['admin.saas.customers.*']],
                    ['label' => 'Lisanslar', 'route' => 'admin.saas.licenses.index', 'active' => ['admin.saas.licenses.*']],
                    ['label' => 'Ürünler (tier)', 'route' => 'admin.saas.products.index', 'active' => ['admin.saas.products.*']],
                    ['label' => 'Modüller', 'route' => 'admin.saas.modules.index', 'active' => ['admin.saas.modules.*']],
                    ['label' => 'Planlar', 'route' => 'admin.plans.index', 'active' => ['admin.plans.*']],
                    ['label' => 'Ödeme yöntemleri', 'route' => 'admin.billing-settings.edit', 'active' => ['admin.billing-settings.*']],
                ],
            ],
            [
                'id' => 'product',
                'label' => 'Panel ürünü',
                'items' => [
                    ['label' => 'Panel sürümleri', 'route' => 'admin.panel-releases.index', 'active' => ['admin.panel-releases.*']],
                    ['label' => 'Entegrasyonlar', 'route' => 'admin.integrations-settings.edit', 'active' => ['admin.integrations-settings.*']],
                ],
            ],
            [
                'id' => 'content',
                'label' => 'Site & içerik',
                'items' => [
                    [
                        'label' => 'Görünüm',
                        'route' => 'admin.appearance.index',
                        'active' => [
                            'admin.appearance.*',
                            'admin.site-settings.*',
                            'admin.theme-settings.*',
                            'admin.public-home-content.*',
                            'admin.install-settings.*',
                        ],
                    ],
                    ['label' => 'Menüler', 'route' => 'admin.nav-menu.index', 'active' => ['admin.nav-menu.*']],
                    ['label' => 'Site sayfaları', 'route' => 'admin.site-pages.index', 'active' => ['admin.site-pages.*']],
                    ['label' => 'Blog', 'route' => 'admin.blog-posts.index', 'active' => ['admin.blog-posts.*']],
                    ['label' => 'Blog kategorileri', 'route' => 'admin.blog-categories.index', 'active' => ['admin.blog-categories.*']],
                    ['label' => 'Dokümanlar', 'route' => 'admin.doc-pages.index', 'active' => ['admin.doc-pages.*']],
                ],
            ],
            [
                'id' => 'community',
                'label' => 'Topluluk',
                'items' => [
                    ['label' => 'SEO ayarları', 'route' => 'admin.community.settings.edit', 'active' => ['admin.community.settings.*']],
                    ['label' => 'Moderasyon', 'route' => 'admin.community.moderation.index', 'active' => ['admin.community.moderation.*']],
                    ['label' => 'Kategoriler', 'route' => 'admin.community.categories.index', 'active' => ['admin.community.categories.*']],
                    ['label' => 'Üyeler', 'route' => 'admin.community.members.index', 'active' => ['admin.community.members.*']],
                    ['label' => 'Konular', 'route' => 'admin.community.topics.index', 'active' => ['admin.community.topics.*']],
                ],
            ],
            [
                'id' => 'system',
                'label' => 'Sistem',
                'items' => [
                    ['label' => 'Dil ayarları', 'route' => 'admin.locale-settings.edit', 'active' => ['admin.locale-settings.*']],
                    ['label' => 'Çeviriler', 'route' => 'admin.translations.index', 'active' => ['admin.translations.*']],
                    ['label' => 'Sistem logları', 'route' => 'admin.system.logs.index', 'active' => ['admin.system.logs.*']],
                ],
            ],
        ];
    }

    /**
     * @param  list<string>  $patterns
     */
    public static function isActive(array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{id: string, label: string, items: list<array{label: string, route: string, active: list<string>}>}  $group
     */
    public static function isGroupActive(array $group): bool
    {
        foreach ($group['items'] as $item) {
            if (self::isActive($item['active'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function openGroupIds(): array
    {
        $open = [];

        foreach (self::groups() as $group) {
            if (self::isGroupActive($group)) {
                $open[] = $group['id'];
            }
        }

        if ($open === []) {
            $open[] = 'saas';
        }

        return $open;
    }
}
