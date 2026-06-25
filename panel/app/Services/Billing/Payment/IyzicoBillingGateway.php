<?php

namespace App\Services\Billing\Payment;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\BillingSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class IyzicoBillingGateway
{
    public function __construct(private BillingSettings $settings) {}

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '' && $this->secretKey() !== '';
    }

    /**
     * @return array{url: string, merchant_ref: string}
     */
    public function initiate(Invoice $invoice, User $user): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('iyzico yapılandırılmamış.');
        }

        $merchantRef = 'PZI'.$invoice->id.'T'.time();
        $invoice->forceFill(['payment_merchant_ref' => $merchantRef])->save();

        $nameParts = explode(' ', trim($user->name ?: $user->email), 2);
        $price = number_format((float) $invoice->total, 2, '.', '');

        $request = [
            'locale' => app()->getLocale() === 'tr' ? 'tr' : 'en',
            'conversationId' => $merchantRef,
            'price' => $price,
            'paidPrice' => $price,
            'currency' => strtoupper($invoice->currency) === 'TRY' ? 'TRY' : 'TRY',
            'basketId' => $invoice->number,
            'paymentGroup' => 'PRODUCT',
            'callbackUrl' => url('/api/billing/iyzico/callback'),
            'buyer' => [
                'id' => 'U'.$user->id,
                'name' => $nameParts[0] ?: 'Müşteri',
                'surname' => $nameParts[1] ?? 'Müşteri',
                'email' => $user->email,
                'identityNumber' => '11111111111',
                'registrationAddress' => 'Türkiye',
                'city' => 'Istanbul',
                'country' => 'Turkey',
            ],
            'shippingAddress' => [
                'contactName' => $user->name ?: $user->email,
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => 'Türkiye',
            ],
            'billingAddress' => [
                'contactName' => $user->name ?: $user->email,
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'address' => 'Türkiye',
            ],
            'basketItems' => [[
                'id' => 'INV'.$invoice->id,
                'name' => Str::limit('Fatura '.$invoice->number, 100, ''),
                'category1' => 'Hosting',
                'itemType' => 'VIRTUAL',
                'price' => $price,
            ]],
        ];

        $response = Http::timeout(30)
            ->withHeaders($this->headers($request))
            ->post($this->baseUrl().'/payment/iyzipos/checkoutform/initialize/auth/ecom', $request);

        $result = $response->json();
        if (! is_array($result) || ($result['status'] ?? '') !== 'success' || empty($result['paymentPageUrl'])) {
            throw new RuntimeException((string) ($result['errorMessage'] ?? 'iyzico ödeme başlatılamadı.'));
        }

        $url = (string) $result['paymentPageUrl'];
        if (! $this->isAllowedRedirectUrl($url)) {
            throw new RuntimeException('Geçersiz iyzico yönlendirme adresi.');
        }

        return ['url' => $url, 'merchant_ref' => $merchantRef];
    }

    /** @return array{paid: bool, merchant_ref: string, payment_id: ?string} */
    public function retrieveByToken(string $token): array
    {
        $request = ['locale' => 'tr', 'token' => $token];
        $response = Http::timeout(30)
            ->withHeaders($this->headers($request))
            ->post($this->baseUrl().'/payment/iyzipos/checkoutform/auth/ecom/detail', $request);

        $result = $response->json();
        if (! is_array($result)) {
            return ['paid' => false, 'merchant_ref' => '', 'payment_id' => null];
        }

        $ref = (string) ($result['conversationId'] ?? $result['conversation_id'] ?? '');
        $paid = ($result['paymentStatus'] ?? '') === 'SUCCESS' && ($result['status'] ?? '') === 'success';

        return [
            'paid' => $paid,
            'merchant_ref' => $ref,
            'payment_id' => isset($result['paymentId']) ? (string) $result['paymentId'] : null,
            'paid_price' => isset($result['paidPrice']) ? (float) $result['paidPrice'] : null,
        ];
    }

    /** @param  array<string, mixed>  $request */
    private function headers(array $request): array
    {
        $random = Str::random(32);
        $payload = json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $random.$payload, $this->secretKey());

        return [
            'Authorization' => 'IYZWS '.$this->apiKey().':'.$signature,
            'x-iyzi-rnd' => $random,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function baseUrl(): string
    {
        return (bool) $this->settings->get('iyzico_test_mode', false)
            ? 'https://sandbox-api.iyzipay.com'
            : 'https://api.iyzipay.com';
    }

    private function isAllowedRedirectUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($host, ['sandbox-cpp.iyzipay.com', 'cpp.iyzipay.com', 'www.iyzico.com', 'iyzico.com'], true);
    }

    private function apiKey(): string
    {
        return trim((string) $this->settings->get('iyzico_api_key', ''));
    }

    private function secretKey(): string
    {
        return trim((string) $this->settings->get('iyzico_secret_key', ''));
    }
}
