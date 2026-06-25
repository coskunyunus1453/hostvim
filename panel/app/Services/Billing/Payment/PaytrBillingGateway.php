<?php

namespace App\Services\Billing\Payment;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\BillingSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PaytrBillingGateway
{
    public function __construct(private BillingSettings $settings) {}

    public function isConfigured(): bool
    {
        return $this->merchantId() !== '' && $this->merchantKey() !== '' && $this->merchantSalt() !== '';
    }

    /**
     * @return array{iframe_url: string, merchant_ref: string}
     */
    public function initiate(Invoice $invoice, User $user): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('PayTR yapılandırılmamış.');
        }

        $merchantRef = $this->merchantRefFor($invoice);
        $invoice->forceFill(['payment_merchant_ref' => $merchantRef])->save();

        $userIp = $this->clientIp();
        $amountMinor = (int) round((float) $invoice->total * 100);
        $basket = base64_encode(json_encode([
            ['Fatura '.$invoice->number, number_format((float) $invoice->total, 2, '.', ''), 1],
        ], JSON_UNESCAPED_UNICODE));

        $testMode = (bool) $this->settings->get('paytr_test_mode', false) ? '1' : '0';
        $debugOn = (bool) $this->settings->get('paytr_debug_on', false) ? '1' : '0';
        $noInstallment = '1';
        $maxInstallment = '0';
        $currency = 'TL';
        $email = substr($user->email, 0, 100);
        $userName = substr(trim($user->name ?: $user->email), 0, 60);

        $hashStr = $this->merchantId().$userIp.$merchantRef.$email.$amountMinor.$basket.$noInstallment.$maxInstallment.$currency.$testMode;
        $paytrToken = base64_encode(hash_hmac('sha256', $hashStr.$this->merchantSalt(), $this->merchantKey(), true));

        $response = Http::asForm()->timeout(25)->post('https://www.paytr.com/odeme/api/get-token', [
            'merchant_id' => $this->merchantId(),
            'user_ip' => $userIp,
            'merchant_oid' => $merchantRef,
            'email' => $email,
            'payment_amount' => (string) $amountMinor,
            'paytr_token' => $paytrToken,
            'user_basket' => $basket,
            'debug_on' => $debugOn,
            'no_installment' => $noInstallment,
            'max_installment' => $maxInstallment,
            'user_name' => $userName,
            'user_address' => '-',
            'user_phone' => '05000000000',
            'merchant_ok_url' => url('/invoices?paid=1'),
            'merchant_fail_url' => url('/invoices'),
            'timeout_limit' => (string) max(1, (int) $this->settings->get('paytr_timeout_minutes', 30)),
            'currency' => $currency,
            'test_mode' => $testMode,
            'lang' => app()->getLocale() === 'tr' ? 'tr' : 'en',
        ]);

        if (! $response->successful()) {
            Log::warning('PayTR get-token HTTP error', ['status' => $response->status(), 'body' => Str::limit($response->body(), 500)]);
            throw new RuntimeException('PayTR bağlantı hatası.');
        }

        $json = $response->json();
        if (! is_array($json) || ($json['status'] ?? '') !== 'success' || empty($json['token'])) {
            throw new RuntimeException('PayTR token alınamadı: '.(is_array($json) ? (string) ($json['reason'] ?? '') : ''));
        }

        return [
            'iframe_url' => 'https://www.paytr.com/odeme/guvenli/'.(string) $json['token'],
            'merchant_ref' => $merchantRef,
        ];
    }

    /** @param  array<string, string>  $post */
    public function verifyCallback(array $post): bool
    {
        $oid = (string) ($post['merchant_oid'] ?? '');
        $hash = (string) ($post['hash'] ?? '');
        if ($oid === '' || $hash === '' || ! $this->isConfigured()) {
            return false;
        }

        $calc = base64_encode(hash_hmac(
            'sha256',
            $oid.$this->merchantSalt().($post['status'] ?? '').($post['total_amount'] ?? ''),
            $this->merchantKey(),
            true,
        ));

        return hash_equals($calc, $hash);
    }

    public function merchantRefFor(Invoice $invoice): string
    {
        return 'PZI'.$invoice->id.'T'.time();
    }

    private function clientIp(): string
    {
        $ip = request()->ip();
        if (! is_string($ip) || $ip === '' || $ip === '127.0.0.1') {
            $ip = '8.8.8.8';
        }

        return substr($ip, 0, 39);
    }

    private function merchantId(): string
    {
        return trim((string) $this->settings->get('paytr_merchant_id', ''));
    }

    private function merchantKey(): string
    {
        return trim((string) $this->settings->get('paytr_merchant_key', ''));
    }

    private function merchantSalt(): string
    {
        return trim((string) $this->settings->get('paytr_merchant_salt', ''));
    }
}
