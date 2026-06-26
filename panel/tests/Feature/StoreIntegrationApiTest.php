<?php

namespace Tests\Feature;

use App\Models\HostingPackage;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StoreIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['panelze.store_integration.secret' => 'test-store-secret-32chars-minimum']);
        Role::query()->create(['name' => 'user', 'guard_name' => 'web']);
    }

    public function test_rejects_missing_secret_header(): void
    {
        $this->postJson('/api/integrations/store/fulfill', [
            'store_order_number' => 'HV-TEST-1',
            'customer' => ['name' => 'Test', 'email' => 'store@example.com'],
            'items' => [['package_id' => 1]],
        ])->assertStatus(401);
    }

    public function test_fulfill_creates_user_order_and_invoice(): void
    {
        $package = HostingPackage::query()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price_monthly' => 99,
            'price_yearly' => 990,
            'is_active' => true,
        ]);

        $headers = ['Authorization' => 'Bearer test-store-secret-32chars-minimum'];

        $this->getJson('/api/integrations/store/test', $headers)
            ->assertOk()
            ->assertJsonPath('integration', 'store');

        $response = $this->postJson('/api/integrations/store/fulfill', [
            'store_order_number' => 'HV-STORE-1001',
            'customer' => [
                'name' => 'Mağaza Müşteri',
                'email' => 'magaza@example.com',
                'locale' => 'tr',
            ],
            'items' => [
                ['item_type' => 'hosting', 'package_id' => $package->id, 'billing_cycle' => 'monthly'],
            ],
            'payment' => [
                'method' => 'paytr',
                'reference' => 'PAYTR-123',
            ],
        ], $headers);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('user_created', true)
            ->assertJsonStructure(['panel_order_number', 'temporary_password']);

        $this->assertDatabaseHas('users', [
            'email' => 'magaza@example.com',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('orders', [
            'store_order_number' => 'HV-STORE-1001',
        ]);

        $this->assertDatabaseHas('invoices', [
            'status' => 'paid',
        ]);

        $this->getJson('/api/integrations/store/fulfill/status?store_order_number=HV-STORE-1001', $headers)
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('needs_password_setup', true);

        $this->postJson('/api/integrations/store/fulfill', [
            'store_order_number' => 'HV-STORE-1001',
            'customer' => [
                'name' => 'Mağaza Müşteri',
                'email' => 'magaza@example.com',
            ],
            'items' => [
                ['item_type' => 'hosting', 'package_id' => $package->id, 'billing_cycle' => 'monthly'],
            ],
        ], $headers)->assertOk()->assertJsonPath('user_created', false);
    }

    public function test_domain_check_and_manual_fulfill(): void
    {
        Mail::fake();
        \App\Models\DomainTld::query()->updateOrCreate(
            ['tld' => '.com'],
            ['register_price' => 299, 'renew_price' => 299, 'enabled' => true, 'sort_order' => 1]
        );

        $headers = ['Authorization' => 'Bearer test-store-secret-32chars-minimum'];

        $this->getJson('/api/integrations/store/domains/tlds', $headers)
            ->assertOk()
            ->assertJsonPath('enabled', true);

        $this->getJson('/api/integrations/store/domains/check?domain=test-avail-123.com', $headers)
            ->assertOk()
            ->assertJsonStructure(['domain', 'available', 'register_price']);

        $response = $this->postJson('/api/integrations/store/fulfill', [
            'store_order_number' => 'HV-MANUAL-1',
            'customer' => ['name' => 'VPS Müşteri', 'email' => 'vps@example.com'],
            'items' => [
                [
                    'item_type' => 'manual',
                    'product_name' => 'VPS Starter',
                    'billing_cycle' => 'monthly',
                    'unit_price' => 149.90,
                ],
            ],
        ], $headers);

        $response->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('orders', ['store_order_number' => 'HV-MANUAL-1']);
        $this->assertDatabaseMissing('support_tickets', ['subject' => 'Sipariş kurulumu — VPS Starter']);
    }

    public function test_settings_sync_from_store(): void
    {
        $headers = ['Authorization' => 'Bearer test-store-secret-32chars-minimum'];

        $this->postJson('/api/integrations/store/settings/sync', [
            'billing' => [
                'currency' => 'TRY',
                'tax_rate' => 20,
                'support_email' => 'destek@example.com',
                'paytr_enabled' => true,
            ],
            'mail' => [
                'driver' => 'smtp',
                'smtp_host' => 'mail.example.com',
                'smtp_port' => 587,
                'from_address' => 'noreply@example.com',
                'from_name' => 'HostVim',
            ],
        ], $headers)->assertOk()->assertJsonPath('applied.billing', true);

        $this->assertDatabaseHas('panel_settings', [
            'key' => 'outbound_mail.from_address',
            'value' => 'noreply@example.com',
        ]);
    }
}
