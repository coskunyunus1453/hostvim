<?php

namespace Tests\Feature;

use App\Models\LandingSiteSetting;
use App\Models\SaasLicenseProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $overrides = []): SaasLicenseProduct
    {
        return SaasLicenseProduct::query()->create(array_merge([
            'code' => 'pro',
            'name' => 'Panelze Pro',
            'description' => 'Tüm Pro modüller',
            'default_limits' => ['max_sites' => 500],
            'default_modules' => [],
            'is_active' => true,
            'sort_order' => 10,
            'price_try_minor' => 199_900,
        ], $overrides));
    }

    public function test_buy_page_lists_priced_active_product_and_buy_button(): void
    {
        $this->makeProduct();

        $response = $this->get('/buy');

        $response->assertOk()
            ->assertSee('Panelze Pro')
            ->assertSee('api/v1/licensing/checkout');
    }

    public function test_buy_page_hides_products_without_price(): void
    {
        $this->makeProduct([
            'code' => 'community',
            'name' => 'Panelze Community',
            'price_try_minor' => null,
            'price_usd_minor' => null,
            'price_eur_minor' => null,
            'sort_order' => 0,
        ]);

        $this->get('/buy')
            ->assertOk()
            ->assertDontSee('Panelze Community');
    }

    public function test_checkout_bank_transfer_returns_bank_details(): void
    {
        $this->makeProduct();
        LandingSiteSetting::put('billing.methods.bank_transfer.enabled', '1');
        LandingSiteSetting::put('billing.bank_transfer.iban', 'TR000000000000000000000000');
        LandingSiteSetting::put('billing.bank_transfer.account_name', 'Acme Yazılım');

        $response = $this->postJson('/api/v1/licensing/checkout', [
            'product_code' => 'pro',
            'email' => 'buyer@example.com',
            'billing' => 'bank_transfer',
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'bank_transfer')
            ->assertJsonPath('status', 'awaiting_transfer')
            ->assertJsonPath('bank.iban', 'TR000000000000000000000000');

        $this->assertDatabaseHas('saas_checkout_orders', [
            'email' => 'buyer@example.com',
            'status' => 'awaiting_transfer',
        ]);
    }

    public function test_checkout_rejects_unknown_product(): void
    {
        $this->postJson('/api/v1/licensing/checkout', [
            'product_code' => 'does-not-exist',
            'email' => 'buyer@example.com',
        ])->assertStatus(422);
    }
}
