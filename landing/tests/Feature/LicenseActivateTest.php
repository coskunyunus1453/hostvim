<?php

namespace Tests\Feature;

use App\Models\SaasCustomer;
use App\Models\SaasLicense;
use App\Models\SaasLicenseProduct;
use App\Models\SaasProductModule;
use App\Services\OfflineLicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseActivateTest extends TestCase
{
    use RefreshDatabase;

    private function configureSigning(): array
    {
        $kp = (new OfflineLicenseService)->generateKeypair();
        config([
            'panelze_saas.offline_signing_secret' => $kp['secret'],
            'panelze_saas.offline_public_key' => $kp['public'],
            'panelze_saas.license_api_secret' => '',
            'panelze_saas.default_max_activations' => 0,
        ]);

        return $kp;
    }

    private function makeLicense(string $key, array $overrides = []): SaasLicense
    {
        $customer = SaasCustomer::query()->create([
            'name' => 'Acme Ltd',
            'email' => 'acme@example.com',
        ]);
        $product = SaasLicenseProduct::query()->create(array_merge([
            'code' => 'pro',
            'name' => 'Pro',
            'default_limits' => [],
            'default_modules' => ['phpmyadmin_sso' => true],
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides['product'] ?? []));
        SaasProductModule::query()->create([
            'key' => 'phpmyadmin_sso',
            'label' => 'phpMyAdmin SSO',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return SaasLicense::query()->create(array_merge([
            'saas_customer_id' => $customer->id,
            'saas_license_product_id' => $product->id,
            'license_key' => $key,
            'status' => 'active',
        ], $overrides['license'] ?? []));
    }

    public function test_activate_returns_domain_bound_signed_key(): void
    {
        $kp = $this->configureSigning();
        $this->makeLicense('hv_activatekey1');

        $response = $this->postJson('/api/v1/license/activate', [
            'key' => 'hv_activatekey1',
            'host' => 'panel.customer.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('plan', 'pro')
            ->assertJsonPath('bound_host', 'panel.customer.com');

        $signed = $response->json('signed_key');
        $this->assertIsString($signed);
        $this->assertStringStartsWith('PLZ1.', $signed);

        // İmzalı anahtar yalnızca bağlı host'ta geçerli olmalı.
        $offline = new OfflineLicenseService;
        $okHost = $offline->verify($signed, 'panel.customer.com', $kp['public']);
        $this->assertTrue($okHost['valid']);
        $this->assertSame(['panel.customer.com'], $okHost['bound_domains']);

        $wrongHost = $offline->verify($signed, 'baska.com', $kp['public']);
        $this->assertFalse($wrongHost['valid']);
        $this->assertSame('domain_mismatch', $wrongHost['code']);

        $this->assertDatabaseHas('saas_license_activations', [
            'host' => 'panel.customer.com',
        ]);
    }

    public function test_activate_rejects_revoked_license(): void
    {
        $this->configureSigning();
        $this->makeLicense('hv_revokedkey', ['license' => ['status' => 'revoked']]);

        $response = $this->postJson('/api/v1/license/activate', [
            'key' => 'hv_revokedkey',
            'host' => 'panel.customer.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('code', 'revoked');
    }

    public function test_activate_enforces_activation_limit(): void
    {
        $this->configureSigning();
        $this->makeLicense('hv_limitkey', [
            'license' => ['limits_override' => ['max_activations' => 1]],
        ]);

        $first = $this->postJson('/api/v1/license/activate', [
            'key' => 'hv_limitkey',
            'host' => 'host-a.com',
        ]);
        $first->assertJsonPath('valid', true);

        // Aynı host tekrar → limit tüketmez.
        $again = $this->postJson('/api/v1/license/activate', [
            'key' => 'hv_limitkey',
            'host' => 'host-a.com',
        ]);
        $again->assertJsonPath('valid', true);

        // Farklı host → limit aşıldı.
        $second = $this->postJson('/api/v1/license/activate', [
            'key' => 'hv_limitkey',
            'host' => 'host-b.com',
        ]);
        $second->assertJsonPath('valid', false)
            ->assertJsonPath('code', 'activation_limit');
    }

    public function test_activate_without_signing_secret_returns_no_signed_key(): void
    {
        config([
            'panelze_saas.offline_signing_secret' => '',
            'panelze_saas.license_api_secret' => '',
        ]);
        $this->makeLicense('hv_nosecret');

        $response = $this->postJson('/api/v1/license/activate', [
            'key' => 'hv_nosecret',
            'host' => 'panel.customer.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('valid', true);
        $this->assertNull($response->json('signed_key'));
    }
}
