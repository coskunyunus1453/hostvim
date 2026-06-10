<?php

/**
 * Panel Pro modül kayıt defteri (landing saas_product_modules ile senkron tutun).
 * Landing admin ui_paths / api_route_prefixes boşsa bu varsayılanlar kullanılır.
 */
return [
    'curious_tools' => [
        'label' => 'Meraklısına',
        'ui_paths' => ['/curious'],
        'api_route_prefixes' => ['curious'],
    ],
    'phpmyadmin_sso' => [
        'label' => 'phpMyAdmin SSO',
        'ui_paths' => [],
        'api_route_prefixes' => ['databases'],
    ],
    'ai_advisor' => [
        'label' => 'PanelZeka / AI',
        'ui_paths' => ['/ai-advisor'],
        'api_route_prefixes' => ['ai', 'ai-assistant'],
    ],
    'backups_pro' => [
        'label' => 'Gelişmiş yedekleme',
        'ui_paths' => ['/backups'],
        'api_route_prefixes' => ['backups/google-drive', 'backups/restore-remote'],
    ],
    'monitoring_advanced' => [
        'label' => 'Gelişmiş izleme',
        'ui_paths' => ['/monitoring'],
        'api_route_prefixes' => ['monitoring/server'],
    ],
    'stripe_billing' => [
        'label' => 'Faturalama',
        'ui_paths' => ['/billing'],
        'api_route_prefixes' => ['billing/checkout'],
    ],
    'vendor_panel' => [
        'label' => 'Vendor panel',
        'ui_paths' => [],
        'api_route_prefixes' => ['vendor'],
    ],
];
