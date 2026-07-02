<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class PayTRGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, PaymentMethod $method): array
    {
        $config = $method->config ?? [];
        $merchantId = $config['merchant_id'] ?? '';
        $merchantKey = $config['merchant_key'] ?? '';
        $merchantSalt = $config['merchant_salt'] ?? '';

        if ($merchantId === '' || $merchantKey === '' || $merchantSalt === '') {
            return ['type' => 'error', 'message' => 'PayTR API bilgileri yapılandırılmamış.'];
        }

        $userBasket = base64_encode(json_encode(
            $order->items->map(fn ($item) => [
                $item->product_name,
                number_format($item->total, 2, '.', ''),
                $item->quantity,
            ])->values()->toArray()
        ));

        // PayTR merchant_oid YALNIZCA alfanumerik olmalıdır (tire/altçizgi kabul edilmez).
        // order_number "HV-XXXX-NNNN" formatında tire içerdiğinden token isteği reddedilir;
        // bu yüzden temizlenmiş hâlini gönderiyoruz, callback'te de aynı kurala göre eşliyoruz.
        $merchantOid = self::merchantOid($order->order_number);
        $email = $order->customer_email;
        $paymentAmount = (int) round($order->total * 100);
        $userIp = request()->ip() ?? '127.0.0.1';
        $noInstallment = 0;
        $maxInstallment = 0;
        $currency = 'TL';
        $testMode = filter_var($config['test_mode'] ?? false, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

        $hashStr = $merchantId . $userIp . $merchantOid . $email . $paymentAmount . $userBasket
            . $noInstallment . $maxInstallment . $currency . $testMode;
        $paytrToken = base64_encode(hash_hmac('sha256', $hashStr . $merchantSalt, $merchantKey, true));

        $response = Http::timeout(30)->asForm()->post('https://www.paytr.com/odeme/api/get-token', [
            'merchant_id' => $merchantId,
            'user_ip' => $userIp,
            'merchant_oid' => $merchantOid,
            'email' => $email,
            'payment_amount' => $paymentAmount,
            'paytr_token' => $paytrToken,
            'user_basket' => $userBasket,
            'debug_on' => $testMode,
            'no_installment' => $noInstallment,
            'max_installment' => $maxInstallment,
            'user_name' => $order->customer_name,
            'user_address' => $order->customer_address ?? 'Türkiye',
            'user_phone' => $order->customer_phone ?? '05000000000',
            'merchant_ok_url' => URL::temporarySignedRoute('payment.success', now()->addDays(7), ['order' => $order->id]),
            'merchant_fail_url' => URL::temporarySignedRoute('payment.fail', now()->addDays(7), ['order' => $order->id]),
            'timeout_limit' => 30,
            'currency' => $currency,
            'test_mode' => $testMode,
        ]);

        if ($response->failed()) {
            Log::error('PayTR token isteği başarısız (HTTP)', [
                'order' => $order->order_number,
                'status' => $response->status(),
            ]);

            return ['type' => 'error', 'message' => 'PayTR ödeme sağlayıcısına ulaşılamadı.'];
        }

        $result = $response->json();

        if (($result['status'] ?? '') === 'success' && ! empty($result['token'])) {
            return [
                'type' => 'iframe',
                'token' => $result['token'],
                'iframe_url' => 'https://www.paytr.com/odeme/guvenli/' . $result['token'],
            ];
        }

        Log::warning('PayTR token alınamadı', [
            'order' => $order->order_number,
            'reason' => $result['reason'] ?? null,
        ]);

        return [
            'type' => 'error',
            'message' => $result['reason'] ?? 'PayTR ödeme başlatılamadı.',
        ];
    }

    public function handleCallback(array $data): Order
    {
        $order = $this->findOrder($data['merchant_oid'] ?? '');
        if (! $order) {
            Log::warning('PayTR callback: sipariş bulunamadı', ['merchant_oid' => $data['merchant_oid'] ?? null]);
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(Order::class);
        }

        if (! $this->verify($data)) {
            Log::warning('PayTR callback imza doğrulaması başarısız', [
                'order' => $order->order_number,
            ]);

            return $order;
        }

        if ($order->payment_status === 'paid') {
            return $order;
        }

        $status = ($data['status'] ?? '') === 'success' ? 'paid' : 'failed';
        $order->update([
            'payment_status' => $status,
            'status' => $status === 'paid' ? 'processing' : 'cancelled',
            'payment_reference' => $data['payment_id'] ?? null,
            'payment_data' => $this->sanitizePaymentData($data),
        ]);

        return $order;
    }

    public function verify(array $data): bool
    {
        $order = $this->findOrder($data['merchant_oid'] ?? '');
        if (! $order || ! $order->paymentMethod) {
            return false;
        }

        $config = $order->paymentMethod->config ?? [];
        $merchantKey = $config['merchant_key'] ?? '';
        $merchantSalt = $config['merchant_salt'] ?? '';

        if ($merchantKey === '' || $merchantSalt === '') {
            return false;
        }

        $hash = base64_encode(hash_hmac(
            'sha256',
            ($data['merchant_oid'] ?? '') . $merchantSalt . ($data['status'] ?? '') . ($data['total_amount'] ?? ''),
            $merchantKey,
            true
        ));

        return hash_equals($hash, $data['hash'] ?? '');
    }

    /** @param  array<string, mixed>  $data */
    protected function sanitizePaymentData(array $data): array
    {
        unset($data['hash']);

        return $data;
    }

    /** order_number → PayTR merchant_oid (yalnızca harf/rakam). */
    public static function merchantOid(string $orderNumber): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $orderNumber) ?? '';
    }

    /**
     * PayTR'dan gelen (alfanumerik) merchant_oid ile siparişi bulur.
     * order_number tire içerebildiğinden, tireler yok sayılarak eşleştirilir.
     */
    protected function findOrder(string $merchantOid): ?Order
    {
        $merchantOid = trim($merchantOid);
        if ($merchantOid === '') {
            return null;
        }

        $order = Order::where('order_number', $merchantOid)->first();
        if ($order) {
            return $order;
        }

        return Order::whereRaw("REPLACE(REPLACE(order_number, '-', ''), '_', '') = ?", [$merchantOid])->first();
    }
}
