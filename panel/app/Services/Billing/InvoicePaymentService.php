<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\Payment\IyzicoBillingGateway;
use App\Services\Billing\Payment\PaytrBillingGateway;
use App\Services\Billing\Payment\PaymentProviderResolver;
use RuntimeException;
use Stripe\StripeClient;
use Throwable;

class InvoicePaymentService
{
    public function __construct(
        private BillingSettings $settings,
        private PaymentProviderResolver $resolver,
        private PaytrBillingGateway $paytr,
        private IyzicoBillingGateway $iyzico,
        private InvoiceService $invoices,
    ) {}

    /**
     * @param  array{return_ok_url?: ?string, return_fail_url?: ?string}  $options
     * @return array<string, mixed>
     */
    public function initiate(Invoice $invoice, User $user, array $options = []): array
    {
        if (! $invoice->isPayable()) {
            throw new RuntimeException('Bu fatura ödenebilir durumda değil.');
        }

        $provider = $this->resolver->resolve($invoice);

        return match ($provider) {
            'paytr' => $this->paytrPayload($invoice, $user, $options),
            'iyzico' => $this->iyzicoPayload($invoice, $user, $options),
            'stripe' => $this->stripePayload($invoice, $user, $options),
            default => $this->manualPayload($invoice),
        };
    }

    public function completePaytr(string $merchantRef, string $status, string $totalAmount, ?string $paymentId = null): bool
    {
        $invoice = Invoice::query()->where('payment_merchant_ref', $merchantRef)->first();
        if ($invoice === null || ! $invoice->isPayable()) {
            return false;
        }

        if ($status !== 'success') {
            return true;
        }

        $expectedMinor = (int) round((float) $invoice->total * 100);
        if ((int) $totalAmount !== $expectedMinor) {
            report(new RuntimeException('PayTR tutar uyuşmazlığı: '.$merchantRef));

            return false;
        }

        $this->invoices->markPaid($invoice, 'paytr', $paymentId);

        return true;
    }

    public function completeIyzico(string $token): ?Invoice
    {
        $detail = $this->iyzico->retrieveByToken($token);
        if ($detail['merchant_ref'] === '') {
            return null;
        }

        $invoice = Invoice::query()->where('payment_merchant_ref', $detail['merchant_ref'])->first();
        if ($invoice === null) {
            return null;
        }

        if (! $invoice->isPayable()) {
            return $invoice;
        }

        if (! $detail['paid']) {
            return $invoice;
        }

        if ($detail['paid_price'] !== null && abs($detail['paid_price'] - (float) $invoice->total) > 0.02) {
            report(new RuntimeException('iyzico tutar uyuşmazlığı: '.$detail['merchant_ref']));

            return null;
        }

        return $this->invoices->markPaid($invoice, 'iyzico', $detail['payment_id']);
    }

    /**
     * @param  array{return_ok_url?: ?string, return_fail_url?: ?string}  $options
     * @return array<string, mixed>
     */
    private function paytrPayload(Invoice $invoice, User $user, array $options = []): array
    {
        $data = $this->paytr->initiate($invoice, $user, [
            'return_ok_url' => $this->safeReturnUrl($options['return_ok_url'] ?? null, url('/invoices?paid=1')),
            'return_fail_url' => $this->safeReturnUrl($options['return_fail_url'] ?? null, url('/invoices')),
        ]);

        return [
            'method' => 'paytr',
            'iframe_url' => $data['iframe_url'],
            'redirect_url' => $data['iframe_url'],
            'reference' => $data['merchant_ref'],
        ];
    }

    /**
     * @param  array{return_ok_url?: ?string, return_fail_url?: ?string}  $options
     * @return array<string, mixed>
     */
    private function iyzicoPayload(Invoice $invoice, User $user, array $options = []): array
    {
        $data = $this->iyzico->initiate($invoice, $user, $options);

        return [
            'method' => 'iyzico',
            'url' => $data['url'],
            'redirect_url' => $data['url'],
            'reference' => $data['merchant_ref'],
        ];
    }

    /**
     * @param  array{return_ok_url?: ?string, return_fail_url?: ?string}  $options
     * @return array<string, mixed>
     */
    private function stripePayload(Invoice $invoice, User $user, array $options = []): array
    {
        $secret = config('services.stripe.secret');
        if (! $secret) {
            return $this->manualPayload($invoice);
        }

        try {
            $stripe = new StripeClient($secret);
            $okUrl = $this->safeReturnUrl($options['return_ok_url'] ?? null, url('/invoices?paid=1'));
            $failUrl = $this->safeReturnUrl($options['return_fail_url'] ?? null, url('/invoices'));
            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'customer_email' => $user->email,
                'client_reference_id' => (string) $user->id,
                'metadata' => ['invoice_id' => (string) $invoice->id],
                'payment_intent_data' => ['metadata' => ['invoice_id' => (string) $invoice->id]],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($invoice->currency),
                        'product_data' => ['name' => 'Fatura '.$invoice->number],
                        'unit_amount' => (int) round((float) $invoice->total * 100),
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $okUrl,
                'cancel_url' => $failUrl,
            ]);

            return ['method' => 'stripe', 'url' => $session->url, 'redirect_url' => $session->url, 'id' => $session->id];
        } catch (Throwable $e) {
            report($e);

            return $this->manualPayload($invoice);
        }
    }

    /** @return array<string, mixed> */
    private function manualPayload(Invoice $invoice): array
    {
        return [
            'method' => 'manual',
            'instructions' => (string) $this->settings->get('payment_instructions', ''),
            'amount' => (float) $invoice->total,
            'currency' => $invoice->currency,
            'reference' => $invoice->number,
        ];
    }

    private function safeReturnUrl(?string $url, string $fallback): string
    {
        if (! is_string($url) || $url === '') {
            return $fallback;
        }

        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        if (! in_array($scheme, ['https', 'http'], true) || $host === '') {
            return $fallback;
        }

        $allowed = array_values(array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            parse_url((string) config('services.store.url', ''), PHP_URL_HOST),
            'hostvim.com',
            'www.hostvim.com',
        ]));

        foreach ($allowed as $allowedHost) {
            if (strcasecmp((string) $allowedHost, $host) === 0) {
                return $url;
            }
            if (str_ends_with(strtolower($host), '.'.strtolower((string) $allowedHost))) {
                return $url;
            }
        }

        return $fallback;
    }
}
