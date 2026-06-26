<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IyzicoGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, PaymentMethod $method): array
    {
        $config = $method->config ?? [];
        $apiKey = $config['api_key'] ?? '';
        $secretKey = $config['secret_key'] ?? '';

        if ($apiKey === '' || $secretKey === '') {
            return ['type' => 'error', 'message' => 'iyzico API bilgileri yapılandırılmamış.'];
        }

        $baseUrl = filter_var($config['test_mode'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ? 'https://sandbox-api.iyzipay.com'
            : 'https://api.iyzipay.com';

        $nameParts = explode(' ', trim($order->customer_name), 2);

        $request = [
            'locale' => 'tr',
            'conversationId' => $order->order_number,
            'price' => number_format($order->total, 2, '.', ''),
            'paidPrice' => number_format($order->total, 2, '.', ''),
            'currency' => 'TRY',
            'basketId' => $order->order_number,
            'paymentGroup' => 'PRODUCT',
            'callbackUrl' => route('payment.iyzico.callback'),
            'buyer' => [
                'id' => 'BY' . $order->id,
                'name' => $nameParts[0] ?: 'Müşteri',
                'surname' => $nameParts[1] ?? 'Müşteri',
                'email' => $order->customer_email,
                'identityNumber' => '11111111111',
                'registrationAddress' => $order->customer_address ?? 'Türkiye',
                'city' => 'Istanbul',
                'country' => 'Turkey',
            ],
            'shippingAddress' => [
                'contactName' => $order->customer_name,
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => $order->customer_address ?? 'Türkiye',
            ],
            'billingAddress' => [
                'contactName' => $order->customer_name,
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => $order->customer_address ?? 'Türkiye',
            ],
            'basketItems' => $order->items->map(fn ($item) => [
                'id' => 'PR' . $item->product_id,
                'name' => Str::limit($item->product_name, 100, ''),
                'category1' => 'Hosting',
                'itemType' => 'VIRTUAL',
                'price' => number_format($item->total, 2, '.', ''),
            ])->values()->toArray(),
        ];

        $response = Http::timeout(30)
            ->withHeaders($this->getHeaders($request, $apiKey, $secretKey))
            ->post("{$baseUrl}/payment/iyzipos/checkoutform/initialize/auth/ecom", $request);

        $result = $response->json();

        if (($result['status'] ?? '') === 'success' && ! empty($result['paymentPageUrl'])) {
            $paymentPageUrl = $result['paymentPageUrl'];
            if (! $this->isAllowedRedirectUrl($paymentPageUrl)) {
                return ['type' => 'error', 'message' => 'Geçersiz ödeme yönlendirme adresi.'];
            }

            return [
                'type' => 'redirect',
                'payment_page_url' => $paymentPageUrl,
                'token' => $result['token'] ?? null,
            ];
        }

        return [
            'type' => 'error',
            'message' => $result['errorMessage'] ?? 'iyzico ödeme başlatılamadı.',
        ];
    }

    public function handleCallback(array $data): Order
    {
        $token = $data['token'] ?? '';
        if ($token === '') {
            abort(400, 'Geçersiz iyzico callback.');
        }

        $method = PaymentMethod::where('code', 'iyzico')->where('is_active', true)->firstOrFail();
        $config = $method->config ?? [];
        $apiKey = $config['api_key'] ?? '';
        $secretKey = $config['secret_key'] ?? '';
        $baseUrl = filter_var($config['test_mode'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ? 'https://sandbox-api.iyzipay.com'
            : 'https://api.iyzipay.com';

        $request = ['locale' => 'tr', 'token' => $token];
        $response = Http::timeout(30)
            ->withHeaders($this->getHeaders($request, $apiKey, $secretKey))
            ->post("{$baseUrl}/payment/iyzipos/checkoutform/auth/ecom/detail", $request);

        $result = $response->json();
        $conversationId = $result['conversationId'] ?? $result['conversation_id'] ?? '';

        $order = Order::where('order_number', $conversationId)->firstOrFail();

        if ($order->payment_status === 'paid') {
            return $order;
        }

        if (($result['paymentStatus'] ?? '') === 'SUCCESS' && ($result['status'] ?? '') === 'success') {
            $paidPrice = (float) ($result['paidPrice'] ?? 0);
            if (abs($paidPrice - (float) $order->total) > 0.01) {
                Log::warning('iyzico tutar uyuşmazlığı', [
                    'order' => $order->order_number,
                    'expected' => $order->total,
                    'paid' => $paidPrice,
                ]);
                abort(400, 'Ödeme tutarı uyuşmuyor.');
            }

            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'payment_reference' => $result['paymentId'] ?? null,
                'payment_data' => ['token' => $token, 'paymentStatus' => $result['paymentStatus'] ?? ''],
            ]);
        } else {
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
                'payment_data' => ['token' => $token, 'paymentStatus' => $result['paymentStatus'] ?? 'FAILURE'],
            ]);
        }

        return $order;
    }

    public function verify(array $data): bool
    {
        return false;
    }

    /** @param  array<string, mixed>  $request */
    protected function getHeaders(array $request, string $apiKey, string $secretKey): array
    {
        $randomString = Str::random(32);
        $payload = json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $randomString . $payload, $secretKey);

        return [
            'Authorization' => 'IYZWS ' . $apiKey . ':' . $signature,
            'x-iyzi-rnd' => $randomString,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    protected function isAllowedRedirectUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($host, ['sandbox-cpp.iyzipay.com', 'cpp.iyzipay.com', 'www.iyzico.com', 'iyzico.com'], true);
    }
}
