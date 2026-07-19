<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Payment\Support\OrderPaymentCompleter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected OrderPaymentCompleter $completer,
    ) {}

    public function initiate(Order $order, PaymentMethod $method): array
    {
        $config = $method->config ?? [];
        $secret = trim((string) ($config['secret_key'] ?? ''));

        if ($secret === '') {
            return ['type' => 'error', 'message' => 'Stripe secret key yapılandırılmamış.'];
        }

        $currency = strtolower((string) ($order->currency ?: 'try'));
        if (! in_array($currency, ['try', 'usd', 'eur', 'gbp'], true)) {
            $currency = 'try';
        }

        try {
            $stripe = new StripeClient($secret);
            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'customer_email' => $order->customer_email,
                'client_reference_id' => $order->order_number,
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => $order->order_number,
                    ],
                ],
                'line_items' => $order->items->map(fn ($item) => [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $item->product_name,
                        ],
                        'unit_amount' => (int) round((float) $item->total * 100),
                    ],
                    'quantity' => 1,
                ])->values()->all(),
                'success_url' => URL::temporarySignedRoute('payment.stripe.return', now()->addDays(7), [
                    'order' => $order->id,
                ]).'&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => URL::temporarySignedRoute('payment.fail', now()->addDays(7), ['order' => $order->id]),
            ]);

            $order->update([
                'payment_data' => array_merge($order->payment_data ?? [], [
                    'stripe_session_id' => $session->id,
                ]),
            ]);

            if (empty($session->url)) {
                return ['type' => 'error', 'message' => 'Stripe oturumu oluşturulamadı.'];
            }

            return [
                'type' => 'redirect',
                'payment_page_url' => $session->url,
                'session_id' => $session->id,
            ];
        } catch (\Throwable $e) {
            Log::error('Stripe initiate failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);

            return ['type' => 'error', 'message' => 'Stripe ödeme başlatılamadı: '.$e->getMessage()];
        }
    }

    public function completeReturn(Order $order, string $sessionId): Order
    {
        if ($order->payment_status === 'paid') {
            return $order;
        }

        $config = $order->paymentMethod?->config ?? [];
        $secret = trim((string) ($config['secret_key'] ?? ''));
        if ($secret === '') {
            abort(500, 'Stripe yapılandırması eksik.');
        }

        $stripe = new StripeClient($secret);
        $session = $stripe->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent'],
        ]);

        if (($session->client_reference_id ?? '') !== $order->order_number) {
            abort(400, 'Stripe oturumu sipariş ile eşleşmiyor.');
        }

        if (($session->payment_status ?? '') !== 'paid') {
            return $this->completer->markFailed($order, [
                'stripe_session_id' => $sessionId,
                'stripe_payment_status' => $session->payment_status ?? 'unpaid',
            ]);
        }

        $expectedMinor = (int) round((float) $order->total * 100);
        if ((int) ($session->amount_total ?? 0) !== $expectedMinor) {
            Log::warning('Stripe amount mismatch', [
                'order' => $order->order_number,
                'expected' => $expectedMinor,
                'paid' => $session->amount_total,
            ]);
            abort(400, 'Ödeme tutarı uyuşmuyor.');
        }

        return $this->completer->markPaid($order, $session->payment_intent->id ?? $sessionId, [
            'stripe_session_id' => $sessionId,
            'stripe_payment_status' => $session->payment_status,
        ]);
    }

    public function handleWebhook(string $rawPayload, string $signature, PaymentMethod $method): bool
    {
        $webhookSecret = trim((string) (($method->config ?? [])['webhook_secret'] ?? ''));
        if ($webhookSecret === '') {
            return false;
        }

        try {
            $event = Webhook::constructEvent($rawPayload, $signature, $webhookSecret);
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            Log::warning('Stripe webhook verify failed', ['error' => $e->getMessage()]);

            return false;
        }

        if ($event->type !== 'checkout.session.completed') {
            return true;
        }

        /** @var Session $session */
        $session = $event->data->object;
        $orderNumber = (string) ($session->client_reference_id ?? '');
        $order = Order::query()->where('order_number', $orderNumber)->first();
        if ($order === null) {
            return false;
        }

        if (($session->payment_status ?? '') === 'paid') {
            $amountTotal = isset($session->amount_total) ? (int) $session->amount_total : null;
            $expected = (int) round(((float) $order->total) * 100);
            if ($amountTotal !== null && $amountTotal !== $expected) {
                Log::warning('Stripe webhook tutar uyuşmazlığı', [
                    'order' => $order->order_number,
                    'amount_total' => $amountTotal,
                    'expected' => $expected,
                ]);

                return false;
            }

            $this->completer->markPaid($order, $session->payment_intent ?? $session->id, [
                'stripe_session_id' => $session->id,
                'stripe_webhook' => true,
            ]);
        }

        return true;
    }

    public function handleCallback(array $data): Order
    {
        abort(400, 'Stripe callback doğrudan kullanılmaz.');
    }

    public function verify(array $data): bool
    {
        return false;
    }
}
