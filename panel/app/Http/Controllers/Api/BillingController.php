<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HostingPackage;
use App\Models\Invoice;
use App\Models\Subscription as PanelSubscription;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use App\Services\PanelLicenseService;
use App\Services\SafeAuditLogger;
use App\Services\UserHostingPackageSync;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException as StripeUnexpectedValueException;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Subscription as StripeSubscription;
use Stripe\Webhook;

class BillingController extends Controller
{
    public function __construct(
        private UserHostingPackageSync $hostingPackageSync,
        private PanelLicenseService $panelLicense,
        private InvoiceService $invoiceService,
    ) {}

    public function licenseSummary(): JsonResponse
    {
        $summary = $this->panelLicense->billingSummary();
        unset($summary['customer'], $summary['code']);

        return response()->json([
            'license' => $summary,
        ]);
    }

    public function packages(): JsonResponse
    {
        return response()->json([
            'packages' => HostingPackage::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $subs = $request->user()->subscriptions()->with('hostingPackage')->latest()->paginate(20);

        return response()->json($subs);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:hosting_packages,id',
            'billing_cycle' => 'required|string|in:monthly,yearly',
            'success_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
        ]);

        $secret = config('services.stripe.secret');
        if (! $secret) {
            return response()->json([
                'message' => __('billing.stripe_not_configured'),
                'demo' => true,
                'package_id' => $validated['package_id'],
            ], 422);
        }

        $package = HostingPackage::query()
            ->where('is_active', true)
            ->findOrFail($validated['package_id']);
        $amount = $validated['billing_cycle'] === 'yearly' ? $package->price_yearly : $package->price_monthly;
        $stripe = new StripeClient($secret);

        $user = $request->user();
        $meta = [
            'user_id' => (string) $user->id,
            'hosting_package_id' => (string) $package->id,
            'billing_cycle' => $validated['billing_cycle'],
        ];

        $session = $stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer_email' => $user->email,
            'client_reference_id' => (string) $user->id,
            'metadata' => $meta,
            'subscription_data' => [
                'metadata' => $meta,
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($package->currency),
                    'product_data' => ['name' => $package->name],
                    'unit_amount' => (int) round($amount * 100),
                    'recurring' => ['interval' => $validated['billing_cycle'] === 'yearly' ? 'year' : 'month'],
                ],
                'quantity' => 1,
            ]],
            'success_url' => $this->validatedRedirectUrl(
                $validated['success_url'] ?? null,
                url('/billing?checkout=success')
            ),
            'cancel_url' => $this->validatedRedirectUrl(
                $validated['cancel_url'] ?? null,
                url('/billing?checkout=cancel')
            ),
        ]);

        return response()->json(['url' => $session->url, 'id' => $session->id]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $secret = config('services.stripe.webhook_secret');
        if (! $secret) {
            return response()->json(['message' => __('billing.webhook_not_configured')], 503);
        }

        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (StripeUnexpectedValueException) {
            return response()->json(['message' => __('billing.webhook_invalid_payload')], 400);
        } catch (SignatureVerificationException) {
            return response()->json(['message' => __('billing.webhook_invalid_signature')], 400);
        }

        match ($event->type) {
            'customer.subscription.created',
            'customer.subscription.updated' => $this->syncSubscriptionFromStripeEvent($event),
            'customer.subscription.deleted' => $this->syncSubscriptionFromStripeEvent($event),
            'checkout.session.completed' => $this->handleCheckoutCompleted($event),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    /** Tek seferlik fatura ödemesi (Checkout payment mode) tamamlandığında faturayı ödenmiş işaretle. */
    private function handleCheckoutCompleted(Event $event): void
    {
        $session = $event->data->object;
        $mode = $session->mode ?? null;
        if ($mode !== 'payment') {
            return; // abonelik akışı subscription event'leriyle yönetilir
        }

        $invoiceId = null;
        if (isset($session->metadata['invoice_id'])) {
            $raw = (string) $session->metadata['invoice_id'];
            $invoiceId = ctype_digit($raw) ? (int) $raw : null;
        }
        if ($invoiceId === null) {
            return;
        }

        $invoice = Invoice::query()->find($invoiceId);
        if ($invoice === null || ! $invoice->isPayable()) {
            return;
        }

        $this->invoiceService->markPaid(
            $invoice,
            method: 'stripe',
            reference: (string) ($session->payment_intent ?? $session->id ?? ''),
        );
    }

    private function syncSubscriptionFromStripeEvent(Event $event): void
    {
        $stripeSub = $event->data->object;
        if (! $stripeSub instanceof StripeSubscription) {
            return;
        }

        $this->syncPanelSubscription($stripeSub);
    }

    private function syncPanelSubscription(StripeSubscription $stripe): void
    {
        $existing = PanelSubscription::query()
            ->where('stripe_subscription_id', $stripe->id)
            ->first();

        $userId = $this->stripeMetaInt($stripe->metadata, 'user_id') ?? $existing?->user_id;
        $packageId = $this->stripeMetaInt($stripe->metadata, 'hosting_package_id') ?? $existing?->hosting_package_id;

        if ($userId === null || $packageId === null) {
            SafeAuditLogger::warning('panelze.billing_stripe', [
                'action' => 'subscription_webhook_skipped',
                'reason' => 'missing_meta',
                'stripe_subscription_fp' => substr(hash('sha256', (string) $stripe->id), 0, 16),
            ], null);

            return;
        }

        if (! User::query()->whereKey($userId)->exists() || ! HostingPackage::query()->whereKey($packageId)->exists()) {
            SafeAuditLogger::warning('panelze.billing_stripe', [
                'action' => 'subscription_webhook_skipped',
                'reason' => 'invalid_user_or_package',
                'stripe_subscription_fp' => substr(hash('sha256', (string) $stripe->id), 0, 16),
                'user_id' => $userId,
                'hosting_package_id' => $packageId,
            ], null);

            return;
        }

        $billingCycle = $this->stripeMetaString($stripe->metadata, 'billing_cycle');
        $price = $stripe->items->data[0]->price ?? null;
        if ($billingCycle !== 'yearly' && $billingCycle !== 'monthly' && $price?->recurring) {
            $billingCycle = ($price->recurring->interval ?? 'month') === 'year' ? 'yearly' : 'monthly';
        }
        if ($billingCycle !== 'yearly' && $billingCycle !== 'monthly') {
            $billingCycle = 'monthly';
        }

        $amount = 0.0;
        $currency = strtoupper($stripe->currency ?? 'USD');
        if ($price && $price->unit_amount !== null) {
            $amount = round($price->unit_amount / 100, 2);
            $currency = strtoupper($price->currency ?? $currency);
        }

        $startsAt = $stripe->start_date
            ? Carbon::createFromTimestamp($stripe->start_date)
            : ($stripe->current_period_start
                ? Carbon::createFromTimestamp($stripe->current_period_start)
                : now());

        $endsAt = null;
        if ($stripe->status === StripeSubscription::STATUS_CANCELED && $stripe->ended_at) {
            $endsAt = Carbon::createFromTimestamp($stripe->ended_at);
        }

        $cancelledAt = $stripe->canceled_at
            ? Carbon::createFromTimestamp($stripe->canceled_at)
            : null;

        $trialEndsAt = $stripe->trial_end
            ? Carbon::createFromTimestamp($stripe->trial_end)
            : null;

        PanelSubscription::query()->updateOrCreate(
            ['stripe_subscription_id' => $stripe->id],
            [
                'user_id' => $userId,
                'hosting_package_id' => $packageId,
                'payment_provider' => 'stripe',
                'status' => $stripe->status,
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'currency' => $currency,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'cancelled_at' => $cancelledAt,
                'trial_ends_at' => $trialEndsAt,
            ],
        );

        $this->hostingPackageSync->syncFromSubscriptions($userId);
    }

    private function stripeMetaInt(?StripeObject $metadata, string $key): ?int
    {
        $raw = $this->stripeMetaString($metadata, $key);
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! ctype_digit($raw)) {
            return null;
        }

        return (int) $raw;
    }

    private function stripeMetaString(?StripeObject $metadata, string $key): ?string
    {
        if ($metadata === null || ! isset($metadata[$key])) {
            return null;
        }

        $value = $metadata[$key];

        return $value === null || $value === '' ? null : (string) $value;
    }

    private function validatedRedirectUrl(?string $url, string $fallback): string
    {
        if ($url === null || trim($url) === '') {
            return $fallback;
        }

        $appBase = rtrim((string) config('app.url', ''), '/');
        $appParts = parse_url($appBase);
        $parts = parse_url($url);
        if (! is_array($parts) || ! is_array($appParts)) {
            return $fallback;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return $fallback;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $appHost = strtolower((string) ($appParts['host'] ?? ''));
        if ($host === '' || $appHost === '' || $host !== $appHost) {
            return $fallback;
        }

        return $url;
    }
}
