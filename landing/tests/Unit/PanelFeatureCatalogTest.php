<?php

namespace Tests\Unit;

use App\Support\PanelFeatureCatalog;
use App\Support\SaasModuleDefaults;
use PHPUnit\Framework\TestCase;

class PanelFeatureCatalogTest extends TestCase
{
    public function test_pro_modules_match_panel_registry_keys(): void
    {
        $keys = array_column(PanelFeatureCatalog::proModuleDefs(), 'key');
        $this->assertCount(8, $keys);
        $this->assertContains('security_pro', $keys);
        $this->assertContains('phpmyadmin_sso', $keys);
    }

    public function test_security_pro_has_integration_defaults(): void
    {
        $integration = SaasModuleDefaults::integration('security_pro');
        $this->assertContains('/security', $integration['ui_paths']);
        $this->assertNotEmpty($integration['api_route_prefixes']);
    }

    public function test_pro_plan_features_include_all_modules(): void
    {
        $features = PanelFeatureCatalog::proPlanFeatures('en');
        $this->assertGreaterThanOrEqual(9, count($features));
        $this->assertStringContainsString('phpMyAdmin', $features[1]);
    }
}
