<?php

namespace Tests\Feature;

use App\Models\SaasCheckoutOrder;
use App\Models\SaasLicenseProduct;
use App\Services\Licensing\LicenseRetailFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecurringBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function product(array $overrides = []): SaasLicenseProduct
    {
        return SaasLicenseProduct::query()->create(array_merge([
            'code' => 'pro-monthly',
            'name' => 'Panelze Pro (Aylık)',
            'default_limits' => ['max_sites' => 500],
            'default_modules' => [],
            'is_active' => true,
            'sort_order' => 11,
            'price_try_minor' => 199_900,
            'billing_interval' => 'month',
        ], $overrides));
    }

    private function order(SaasLicenseProduct $product, string $provider = 'stripe'): SaasCheckoutOrder
    {
        return SaasCheckoutOrder::query()->create([
            'order_ref' => 'hv'.Str::lower(Str::random(22)),
            'provider' => $provider,
            'locale' => 'tr',
            'email' => 'buyer@example.com',
            'name' => 'Buyer',
            'saas_license_product_id' => $product->id,
            'amount_minor' => $product->price_try_minor,
            'currency' => 'TRY',
            'status' => 'pending',
        ]);
    }

    public function test_stripe_subscription_fulfillment_sets_expiry_and_subscription(): void
    {
        $product = $this->product();
        $order = $this->order($product);

        $license = app(LicenseRetailFulfillmentService::class)
            ->fulfillIfPending($order, 'cs_test_123', 'sub_abc123');

        $this->assertNotNull($license);
        $this->assertSame('active', $license->subscription_status);
        $this->assertSame('sub_abc123', $license->subscription_provider_id);
        $this->assertNotNull($license->expires_at);
        $this->assertNotNull($license->subscription_renews_at);
        $this->assertTrue($license->expires_at->greaterThan(now()->addDays(27)));
        $this->assertTrue($license->expires_at->lessThan(now()->addDays(35)));
    }

    public function test_non_stripe_recurring_is_manual_without_subscription_id(): void
    {
        $product = $this->product();
        $order = $this->order($product, 'paytr');

        $license = app(LicenseRetailFulfillmentService::class)
            ->fulfillIfPending($order, 'paytr_ref_1');

        $this->assertSame('manual', $license->subscription_status);
        $this->assertNull($license->subscription_provider_id);
        $this->assertNull($license->subscription_renews_at);
        $this->assertNotNull($license->expires_at);
    }

    public function test_one_time_product_is_lifetime(): void
    {
        $product = $this->product([
            'code' => 'pro-lifetime',
            'name' => 'Panelze Pro (Sınırsız)',
            'billing_interval' => null,
        ]);
        $order = $this->order($product);

        $license = app(LicenseRetailFulfillmentService::class)
            ->fulfillIfPending($order, 'cs_test_lifetime');

        $this->assertNull($license->expires_at);
        $this->assertSame('active', $license->subscription_status);
    }

    public function test_renew_subscription_sets_absolute_period_end(): void
    {
        $product = $this->product();
        $order = $this->order($product);
        $service = app(LicenseRetailFulfillmentService::class);
        $license = $service->fulfillIfPending($order, 'cs_test', 'sub_renew');

        $newEnd = Carbon::now()->addMonthNoOverflow()->addDays(2);
        $renewed = $service->renewSubscription('sub_renew', $newEnd);

        $this->assertNotNull($renewed);
        $this->assertSame($license->id, $renewed->id);
        $this->assertSame($newEnd->toDateString(), $renewed->expires_at->toDateString());
        $this->assertSame($newEnd->toDateString(), $renewed->subscription_renews_at->toDateString());
        $this->assertSame('active', $renewed->subscription_status);
    }

    public function test_cancel_subscription_stops_renewal_but_keeps_access(): void
    {
        $product = $this->product();
        $order = $this->order($product);
        $service = app(LicenseRetailFulfillmentService::class);
        $service->fulfillIfPending($order, 'cs_test', 'sub_cancel');

        $canceled = $service->markSubscriptionStatus('sub_cancel', 'canceled');

        $this->assertSame('canceled', $canceled->subscription_status);
        $this->assertNull($canceled->subscription_renews_at);
        // Erişim dönem sonuna kadar sürer: lisans hâlâ active ve expires_at dolu.
        $this->assertSame('active', $canceled->status);
        $this->assertNotNull($canceled->expires_at);
    }

    public function test_renew_unknown_subscription_is_noop(): void
    {
        $this->assertNull(
            app(LicenseRetailFulfillmentService::class)->renewSubscription('sub_nope', Carbon::now())
        );
    }
}
