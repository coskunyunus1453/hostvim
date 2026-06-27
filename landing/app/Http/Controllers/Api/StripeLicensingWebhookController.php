<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingSiteSetting;
use App\Models\SaasCheckoutOrder;
use App\Services\Licensing\LicenseRetailFulfillmentService;
use App\Services\Licensing\StripeLicensingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class StripeLicensingWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        StripeLicensingService $stripe,
        LicenseRetailFulfillmentService $fulfillment,
    ): Response {
        $secret = trim((string) (LandingSiteSetting::getValue('billing.stripe.webhook_secret', (string) config('panelze_saas.stripe.webhook_secret', '')) ?? ''));
        if ($secret === '') {
            return response('webhook secret not configured', 503);
        }

        $payload = $request->getContent();
        $sig = (string) $request->header('Stripe-Signature', '');

        $event = $stripe->parseWebhookEvent($payload, $sig);
        if ($event === null) {
            return response('invalid signature', 400);
        }

        $object = $event->data->object ?? null;
        if (! is_object($object)) {
            return response('ignored', 200);
        }

        return match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($object, $fulfillment),
            'invoice.paid', 'invoice.payment_succeeded' => $this->handleInvoicePaid($object, $fulfillment),
            'invoice.payment_failed' => $this->handleSubscriptionStatus($object, $fulfillment, 'past_due'),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object, $fulfillment),
            default => response('ignored', 200),
        };
    }

    private function handleCheckoutCompleted(object $session, LicenseRetailFulfillmentService $fulfillment): Response
    {
        $mode = (string) ($session->mode ?? '');
        if (! in_array($mode, ['payment', 'subscription'], true)) {
            return response('ignored', 200);
        }

        $orderRef = '';
        if (isset($session->metadata) && isset($session->metadata['order_ref'])) {
            $orderRef = (string) $session->metadata['order_ref'];
        }
        if ($orderRef === '') {
            Log::warning('Stripe licensing webhook missing order_ref');

            return response('no order_ref', 200);
        }

        $order = SaasCheckoutOrder::query()
            ->where('order_ref', $orderRef)
            ->where('provider', 'stripe')
            ->first();

        if (! $order) {
            return response('OK', 200);
        }

        // Tek seferlik ödemede tutarı doğrula; abonelikte tutar Stripe tarafında yönetilir.
        if ($mode === 'payment') {
            $total = (int) ($session->amount_total ?? 0);
            if ($total > 0 && $total !== (int) $order->amount_minor) {
                Log::warning('Stripe licensing amount mismatch', [
                    'order_ref' => $orderRef,
                    'expected' => $order->amount_minor,
                    'got' => $total,
                ]);

                return response('OK', 200);
            }
        }

        $ref = (string) ($session->id ?? '');
        if ($ref === '') {
            return response('OK', 200);
        }

        $subscriptionId = $mode === 'subscription' ? $this->stringOrNull($session->subscription ?? null) : null;

        $fulfillment->fulfillIfPending($order->fresh(), $ref, $subscriptionId);

        return response('OK', 200);
    }

    private function handleInvoicePaid(object $invoice, LicenseRetailFulfillmentService $fulfillment): Response
    {
        $subscriptionId = $this->invoiceSubscriptionId($invoice);
        if ($subscriptionId === null) {
            return response('ignored', 200);
        }

        $periodEnd = $this->invoicePeriodEnd($invoice);
        if ($periodEnd === null) {
            return response('OK', 200);
        }

        $fulfillment->renewSubscription($subscriptionId, $periodEnd);

        return response('OK', 200);
    }

    private function handleSubscriptionStatus(object $invoice, LicenseRetailFulfillmentService $fulfillment, string $status): Response
    {
        $subscriptionId = $this->invoiceSubscriptionId($invoice);
        if ($subscriptionId !== null) {
            $fulfillment->markSubscriptionStatus($subscriptionId, $status);
        }

        return response('OK', 200);
    }

    private function handleSubscriptionDeleted(object $subscription, LicenseRetailFulfillmentService $fulfillment): Response
    {
        $subscriptionId = $this->stringOrNull($subscription->id ?? null);
        if ($subscriptionId !== null) {
            $fulfillment->markSubscriptionStatus($subscriptionId, 'canceled');
        }

        return response('OK', 200);
    }

    private function invoiceSubscriptionId(object $invoice): ?string
    {
        $direct = $this->stringOrNull($invoice->subscription ?? null);
        if ($direct !== null) {
            return $direct;
        }

        // Stripe API 2024+ — abonelik kimliği parent altında olabilir.
        $parent = $invoice->parent ?? null;
        if (is_object($parent)) {
            $details = $parent->subscription_details ?? null;
            if (is_object($details)) {
                return $this->stringOrNull($details->subscription ?? null);
            }
        }

        return null;
    }

    private function invoicePeriodEnd(object $invoice): ?Carbon
    {
        $lines = $invoice->lines->data ?? null;
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $end = $line->period->end ?? null;
                if (is_int($end) && $end > 0) {
                    return Carbon::createFromTimestampUTC($end);
                }
            }
        }

        $periodEnd = $invoice->period_end ?? null;
        if (is_int($periodEnd) && $periodEnd > 0) {
            return Carbon::createFromTimestampUTC($periodEnd);
        }

        return null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }
}
