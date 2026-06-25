<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Payment\Support\OrderPaymentCompleter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class PayPalGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected OrderPaymentCompleter $completer,
    ) {}

    public function initiate(Order $order, PaymentMethod $method): array
    {
        $config = $method->config ?? [];
        $token = $this->accessToken($config);
        if ($token === null) {
            return ['type' => 'error', 'message' => 'PayPal API kimlik bilgileri geçersiz veya eksik.'];
        }

        $currency = strtoupper((string) ($order->currency ?: 'TRY'));
        if (! in_array($currency, ['TRY', 'USD', 'EUR', 'GBP'], true)) {
            $currency = 'TRY';
        }

        $response = Http::timeout(30)
            ->withToken($token)
            ->acceptJson()
            ->post($this->apiBase($config).'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->order_number,
                    'description' => 'Sipariş '.$order->order_number,
                    'custom_id' => (string) $order->id,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format((float) $order->total, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => config('app.name', 'HostVim'),
                    'landing_page' => 'NO_PREFERENCE',
                    'user_action' => 'PAY_NOW',
                    'return_url' => URL::temporarySignedRoute('payment.paypal.return', now()->addDays(7), ['order' => $order->id]),
                    'cancel_url' => URL::temporarySignedRoute('payment.fail', now()->addDays(7), ['order' => $order->id]),
                ],
            ]);

        $result = $response->json();
        $paypalOrderId = (string) ($result['id'] ?? '');
        $approveUrl = collect($result['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if ($response->failed() || $paypalOrderId === '' || ! is_string($approveUrl)) {
            Log::warning('PayPal order create failed', [
                'order' => $order->order_number,
                'body' => $result,
            ]);

            return [
                'type' => 'error',
                'message' => $result['message'] ?? ($result['error_description'] ?? 'PayPal ödeme başlatılamadı.'),
            ];
        }

        $order->update([
            'payment_data' => array_merge($order->payment_data ?? [], [
                'paypal_order_id' => $paypalOrderId,
            ]),
        ]);

        return [
            'type' => 'redirect',
            'payment_page_url' => $approveUrl,
            'paypal_order_id' => $paypalOrderId,
        ];
    }

    public function captureReturn(Order $order, string $paypalOrderId): Order
    {
        if ($order->payment_status === 'paid') {
            return $order;
        }

        $storedId = (string) (($order->payment_data ?? [])['paypal_order_id'] ?? '');
        if ($storedId !== '' && $storedId !== $paypalOrderId) {
            abort(400, 'PayPal sipariş kimliği uyuşmuyor.');
        }

        $config = $order->paymentMethod?->config ?? [];
        $token = $this->accessToken($config);
        if ($token === null) {
            abort(500, 'PayPal yapılandırması eksik.');
        }

        $response = Http::timeout(30)
            ->withToken($token)
            ->acceptJson()
            ->withHeaders(['PayPal-Request-Id' => $order->order_number.'-capture'])
            ->post($this->apiBase($config).'/v2/checkout/orders/'.$paypalOrderId.'/capture');

        $result = $response->json();
        $status = (string) ($result['status'] ?? '');

        if ($status !== 'COMPLETED') {
            return $this->completer->markFailed($order, [
                'paypal_order_id' => $paypalOrderId,
                'paypal_status' => $status,
            ]);
        }

        $capture = $result['purchase_units'][0]['payments']['captures'][0] ?? [];
        $paidValue = (float) ($capture['amount']['value'] ?? 0);
        if (abs($paidValue - (float) $order->total) > 0.02) {
            Log::warning('PayPal amount mismatch', [
                'order' => $order->order_number,
                'expected' => $order->total,
                'paid' => $paidValue,
            ]);
            abort(400, 'Ödeme tutarı uyuşmuyor.');
        }

        return $this->completer->markPaid($order, (string) ($capture['id'] ?? $paypalOrderId), [
            'paypal_order_id' => $paypalOrderId,
            'paypal_capture_id' => $capture['id'] ?? null,
        ]);
    }

    public function handleCallback(array $data): Order
    {
        abort(400, 'PayPal callback doğrudan kullanılmaz.');
    }

    public function verify(array $data): bool
    {
        return false;
    }

    /** @param  array<string, mixed>  $config */
    protected function accessToken(array $config): ?string
    {
        $clientId = trim((string) ($config['client_id'] ?? ''));
        $clientSecret = trim((string) ($config['client_secret'] ?? ''));
        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        $response = Http::timeout(20)
            ->asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post($this->apiBase($config).'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            Log::warning('PayPal token failed', ['body' => $response->json()]);

            return null;
        }

        return $response->json('access_token');
    }

    /** @param  array<string, mixed>  $config */
    protected function apiBase(array $config): string
    {
        return filter_var($config['test_mode'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }
}
