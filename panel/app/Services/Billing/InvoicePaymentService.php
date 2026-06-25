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

    /** @return array<string, mixed> */
    public function initiate(Invoice $invoice, User $user): array
    {
        if (! $invoice->isPayable()) {
            throw new RuntimeException('Bu fatura ödenebilir durumda değil.');
        }

        $provider = $this->resolver->resolve($invoice);

        return match ($provider) {
            'paytr' => $this->paytrPayload($invoice, $user),
            'iyzico' => $this->iyzicoPayload($invoice, $user),
            'stripe' => $this->stripePayload($invoice, $user),
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

    /** @return array<string, mixed> */
    private function paytrPayload(Invoice $invoice, User $user): array
    {
        $data = $this->paytr->initiate($invoice, $user);

        return [
            'method' => 'paytr',
            'iframe_url' => $data['iframe_url'],
            'reference' => $data['merchant_ref'],
        ];
    }

    /** @return array<string, mixed> */
    private function iyzicoPayload(Invoice $invoice, User $user): array
    {
        $data = $this->iyzico->initiate($invoice, $user);

        return [
            'method' => 'iyzico',
            'url' => $data['url'],
            'reference' => $data['merchant_ref'],
        ];
    }

    /** @return array<string, mixed> */
    private function stripePayload(Invoice $invoice, User $user): array
    {
        $secret = config('services.stripe.secret');
        if (! $secret) {
            return $this->manualPayload($invoice);
        }

        try {
            $stripe = new StripeClient($secret);
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
                'success_url' => url('/invoices?paid=1'),
                'cancel_url' => url('/invoices'),
            ]);

            return ['method' => 'stripe', 'url' => $session->url, 'id' => $session->id];
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
}
