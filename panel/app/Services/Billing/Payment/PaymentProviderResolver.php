<?php

namespace App\Services\Billing\Payment;

use App\Models\Invoice;
use App\Services\Billing\BillingSettings;

class PaymentProviderResolver
{
    public function __construct(
        private BillingSettings $settings,
        private PaytrBillingGateway $paytr,
        private IyzicoBillingGateway $iyzico,
    ) {}

    public function resolve(Invoice $invoice): string
    {
        $forced = (string) $this->settings->get('payment_provider', 'auto');
        if ($forced !== 'auto' && in_array($forced, ['paytr', 'iyzico', 'stripe', 'manual'], true)) {
            return $this->pickIfReady($forced, $invoice) ?? 'manual';
        }

        if (strtoupper($invoice->currency) === 'TRY') {
            if ($this->paytr->isConfigured() && (bool) $this->settings->get('paytr_enabled', true)) {
                return 'paytr';
            }
            if ($this->iyzico->isConfigured() && (bool) $this->settings->get('iyzico_enabled', true)) {
                return 'iyzico';
            }
        }

        if ($this->stripeConfigured() && (bool) $this->settings->get('stripe_enabled', true)) {
            return 'stripe';
        }

        return 'manual';
    }

    private function pickIfReady(string $provider, Invoice $invoice): ?string
    {
        return match ($provider) {
            'paytr' => $this->paytr->isConfigured() ? 'paytr' : null,
            'iyzico' => $this->iyzico->isConfigured() ? 'iyzico' : null,
            'stripe' => $this->stripeConfigured() ? 'stripe' : null,
            'manual' => 'manual',
            default => null,
        };
    }

    public function stripeConfigured(): bool
    {
        return config('services.stripe.secret') !== null && config('services.stripe.secret') !== '';
    }
}
