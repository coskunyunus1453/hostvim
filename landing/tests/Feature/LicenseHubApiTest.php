<?php

namespace Tests\Feature;

use App\Models\PanelRelease;
use App\Models\SaasCustomer;
use App\Models\SaasLicense;
use App\Models\SaasLicenseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseHubApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_license_validate_returns_valid_for_active_key(): void
    {
        config(['panelze_saas.license_api_secret' => 'test-secret']);

        $customer = SaasCustomer::query()->create([
            'name' => 'Test Co',
            'email' => 'test@example.com',
        ]);
        $product = SaasLicenseProduct::query()->create([
            'code' => 'pro',
            'name' => 'Pro',
            'default_limits' => ['max_sites' => 10],
            'default_modules' => [],
            'is_active' => true,
            'sort_order' => 0,
        ]);
        SaasLicense::query()->create([
            'saas_customer_id' => $customer->id,
            'saas_license_product_id' => $product->id,
            'license_key' => 'hv_testkey123',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/license/validate', ['key' => 'hv_testkey123'], [
            'Authorization' => 'Bearer test-secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('plan', 'pro');
    }

    public function test_license_validate_rejects_wrong_bearer_when_secret_configured(): void
    {
        config(['panelze_saas.license_api_secret' => 'test-secret']);

        $response = $this->postJson('/api/v1/license/validate', ['key' => 'hv_any']);

        $response->assertUnauthorized()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('code', 'unauthorized');
    }

    public function test_panel_update_check_returns_latest_published_release(): void
    {
        config(['panelze_saas.panel_updates_api_secret' => 'upd-secret']);

        PanelRelease::query()->create([
            'version' => '1.0.0',
            'channel' => 'stable',
            'profile' => 'customer',
            'title' => 'Initial',
            'changelog' => 'First',
            'artifact_url' => 'https://cdn.example.com/panel-1.0.0.tar.gz',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        PanelRelease::query()->create([
            'version' => '1.1.0',
            'channel' => 'stable',
            'profile' => 'customer',
            'title' => 'Update',
            'changelog' => 'Fixes',
            'artifact_url' => 'https://cdn.example.com/panel-1.1.0.tar.gz',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/panel-updates/check?current=1.0.0&profile=customer&channel=stable', [
            'Authorization' => 'Bearer upd-secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('update_available', true)
            ->assertJsonPath('latest.version', '1.1.0');
    }

    public function test_panel_update_check_no_update_when_current_is_latest(): void
    {
        PanelRelease::query()->create([
            'version' => '1.2.0',
            'channel' => 'stable',
            'profile' => 'all',
            'title' => 'Current',
            'changelog' => 'Ok',
            'git_tag' => 'v1.2.0',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/panel-updates/check?current=1.2.0&profile=customer&channel=stable');

        $response->assertOk()
            ->assertJsonPath('update_available', false);
    }
}
