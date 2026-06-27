<?php

namespace App\Services\Licensing;

use App\Models\LandingSiteSetting;
use App\Models\SaasCheckoutOrder;
use App\Models\SaasLicenseProduct;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeLicensingService
{
    public function isConfigured(): bool
    {
        return $this->setting('billing.stripe.secret', (string) config('panelze_saas.stripe.secret', '')) !== '';
    }

    public function createCheckoutSession(SaasCheckoutOrder $order, SaasLicenseProduct $product): Session
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('stripe_not_configured');
        }

        Stripe::setApiKey($this->setting('billing.stripe.secret', (string) config('panelze_saas.stripe.secret', '')));

        $successUrl = url('/license/success?ref='.urlencode($order->order_ref));
        $cancelUrl = url('/license/cancel?ref='.urlencode($order->order_ref));

        $metadata = [
            'order_ref' => $order->order_ref,
            'saas_license_product_id' => (string) $product->id,
        ];

        $priceData = [
            'currency' => strtolower((string) $order->currency),
            'unit_amount' => (int) $order->amount_minor,
            'product_data' => [
                'name' => $product->name,
                'description' => $product->description ?: $product->name,
            ],
        ];

        $recurring = $product->isRecurring();
        if ($recurring) {
            $priceData['recurring'] = ['interval' => $product->billing_interval === 'year' ? 'year' : 'month'];
        }

        $params = [
            'mode' => $recurring ? 'subscription' : 'payment',
            'customer_email' => $order->email,
            'client_reference_id' => $order->order_ref,
            'metadata' => $metadata,
            'line_items' => [[
                'price_data' => $priceData,
                'quantity' => 1,
            ]],
            'success_url' => $successUrl.'&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
        ];

        if ($recurring) {
            // Yenileme faturalarında (invoice.paid) order_ref'i bulabilmek için aboneliğe de metadata yaz.
            $params['subscription_data'] = ['metadata' => $metadata];
        }

        try {
            return Session::create($params);
        } catch (ApiErrorException $e) {
            Log::warning('Stripe checkout create failed: '.$e->getMessage());
            throw new RuntimeException('stripe_checkout_failed');
        }
    }

    /**
     * @return Event|null null if secret missing or invalid
     */
    public function parseWebhookEvent(string $payload, string $signatureHeader): ?Event
    {
        $secret = $this->setting('billing.stripe.webhook_secret', (string) config('panelze_saas.stripe.webhook_secret', ''));
        if ($secret === '') {
            return null;
        }

        try {
            return Webhook::constructEvent($payload, $signatureHeader, $secret);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature failed: '.$e->getMessage());

            return null;
        }
    }

    private function setting(string $key, string $default): string
    {
        return trim((string) (LandingSiteSetting::getValue($key, $default) ?? $default));
    }
}
