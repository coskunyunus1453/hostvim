<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\Payment\Support\OrderPaymentCompleter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Payoneer Checkout — hosted ödeme sayfası (REST API v2).
 *
 * @see https://checkoutdocs.payoneer.com
 */
class PayoneerGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected OrderPaymentCompleter $completer,
    ) {}

    public function initiate(Order $order, PaymentMethod $method): array
    {
        $config = $method->config ?? [];
        $username = trim((string) ($config['api_username'] ?? ''));
        $token = trim((string) ($config['api_token'] ?? ''));
        $programId = trim((string) ($config['program_id'] ?? ''));

        if ($username === '' || $token === '') {
            return ['type' => 'error', 'message' => 'Payoneer API kullanıcı adı ve token yapılandırılmamış.'];
        }

        $currency = strtoupper((string) ($order->currency ?: ($config['default_currency'] ?? 'USD')));
        if (! in_array($currency, ['USD', 'EUR', 'GBP', 'TRY'], true)) {
            $currency = 'USD';
        }

        $payload = [
            'reference_id' => $order->order_number,
            'description' => 'Sipariş '.$order->order_number,
            'amount' => [
                'currency' => $currency,
                'value' => number_format((float) $order->total, 2, '.', ''),
            ],
            'customer' => [
                'email' => $order->customer_email,
                'name' => $order->customer_name,
            ],
            'redirect_url' => URL::temporarySignedRoute('payment.payoneer.return', now()->addDays(7), ['order' => $order->id]),
            'cancel_url' => URL::temporarySignedRoute('payment.fail', now()->addDays(7), ['order' => $order->id]),
            'webhook_url' => route('payment.payoneer.webhook'),
        ];

        if ($programId !== '') {
            $payload['program_id'] = $programId;
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode($username.':'.$token),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($this->apiBase($config).'/v2/checkout/payment-requests', $payload);

        $result = $response->json();
        $checkoutUrl = $result['checkout_url']
            ?? $result['redirect_url']
            ?? ($result['links']['checkout']['href'] ?? null)
            ?? ($result['links']['payment']['href'] ?? null);

        $paymentRequestId = (string) ($result['id'] ?? $result['payment_request_id'] ?? '');

        if ($response->failed() || ! is_string($checkoutUrl) || $checkoutUrl === '') {
            Log::warning('Payoneer payment request failed', [
                'order' => $order->order_number,
                'status' => $response->status(),
                'body' => $result,
            ]);

            return [
                'type' => 'error',
                'message' => $result['message'] ?? $result['error_description'] ?? 'Payoneer ödeme başlatılamadı. API bilgilerini ve program ID kontrol edin.',
            ];
        }

        $order->update([
            'payment_data' => array_merge($order->payment_data ?? [], [
                'payoneer_payment_request_id' => $paymentRequestId,
            ]),
        ]);

        return [
            'type' => 'redirect',
            'payment_page_url' => $checkoutUrl,
            'payment_request_id' => $paymentRequestId,
        ];
    }

    public function completeReturn(Order $order, ?string $paymentRequestId = null): Order
    {
        if ($order->payment_status === 'paid') {
            return $order;
        }

        $config = $order->paymentMethod?->config ?? [];
        $username = trim((string) ($config['api_username'] ?? ''));
        $token = trim((string) ($config['api_token'] ?? ''));
        $storedId = (string) (($order->payment_data ?? [])['payoneer_payment_request_id'] ?? '');
        $requestId = $paymentRequestId ?: $storedId;

        if ($username === '' || $token === '' || $requestId === '') {
            abort(400, 'Payoneer ödeme doğrulaması için veri eksik.');
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode($username.':'.$token),
                'Accept' => 'application/json',
            ])
            ->get($this->apiBase($config).'/v2/checkout/payment-requests/'.$requestId);

        $result = $response->json();
        $status = strtolower((string) ($result['status'] ?? $result['payment_status'] ?? ''));

        if (! in_array($status, ['paid', 'completed', 'success', 'charged'], true)) {
            return $this->completer->markFailed($order, [
                'payoneer_payment_request_id' => $requestId,
                'payoneer_status' => $status,
            ]);
        }

        return $this->completer->markPaid($order, (string) ($result['transaction_id'] ?? $requestId), [
            'payoneer_payment_request_id' => $requestId,
            'payoneer_status' => $status,
        ]);
    }

    public function handleWebhook(array $data): Order
    {
        $reference = (string) ($data['reference_id'] ?? $data['merchant_reference'] ?? '');
        $order = Order::query()->where('order_number', $reference)->firstOrFail();

        if ($order->payment_status === 'paid') {
            return $order;
        }

        $status = strtolower((string) ($data['status'] ?? $data['payment_status'] ?? ''));
        if (in_array($status, ['paid', 'completed', 'success', 'charged'], true)) {
            return $this->completer->markPaid($order, (string) ($data['transaction_id'] ?? $data['id'] ?? ''), [
                'payoneer_webhook' => true,
                'payoneer_status' => $status,
            ]);
        }

        return $order;
    }

    public function verify(array $data): bool
    {
        return true;
    }

    /** @param  array<string, mixed>  $config */
    protected function apiBase(array $config): string
    {
        return filter_var($config['test_mode'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ? 'https://api.sandbox.payoneer.com'
            : 'https://api.payoneer.com';
    }
}
