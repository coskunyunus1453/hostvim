<?php

namespace App\Support;

/**
 * Panel entegrasyon varsayılanları (landing modül kaydı boşsa).
 */
class SaasModuleDefaults
{
    /**
     * @return array{ui_paths: list<string>, api_route_prefixes: list<string>}
     */
    public static function integration(string $key): array
    {
        $map = [
            'curious_tools' => [
                'ui_paths' => ['/curious'],
                'api_route_prefixes' => ['curious'],
            ],
            'phpmyadmin_sso' => [
                'ui_paths' => [],
                'api_route_prefixes' => ['databases'],
            ],
            'ai_advisor' => [
                'ui_paths' => ['/ai-advisor'],
                'api_route_prefixes' => ['ai', 'ai-assistant'],
            ],
            'backups_pro' => [
                'ui_paths' => ['/backups'],
                'api_route_prefixes' => ['backups/google-drive', 'backups/restore-remote'],
            ],
            'monitoring_advanced' => [
                'ui_paths' => ['/monitoring'],
                'api_route_prefixes' => ['monitoring/server'],
            ],
            'stripe_billing' => [
                'ui_paths' => ['/billing'],
                'api_route_prefixes' => ['billing/checkout'],
            ],
            'vendor_panel' => [
                'ui_paths' => [],
                'api_route_prefixes' => ['vendor'],
            ],
        ];

        return $map[$key] ?? ['ui_paths' => [], 'api_route_prefixes' => []];
    }
}
