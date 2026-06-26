<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorInvoice;
use App\Models\VendorPayment;
use App\Models\VendorLicense;
use App\Models\VendorSubscription;
use App\Models\VendorTenant;
use App\Services\VendorAuditService;
use App\Services\VendorSubscriptionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(
        private VendorSubscriptionService $subscriptionService,
        private VendorAuditService $audit,
    ) {}

    public function subscriptions(): JsonResponse
    {
        return response()->json([
            'items' => VendorSubscription::query()->with(['tenant:id,name,slug', 'license:id,license_key,status'])->latest('id')->paginate(20),
        ]);
    }

    public function upsertSubscription(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:vendor_tenants,id'],
            'license_id' => ['nullable', 'integer', 'exists:vendor_licenses,id'],
            'provider' => ['nullable', 'string', 'max:32'],
            'external_id' => ['nullable', 'string', 'max:191'],
            'status' => ['required', Rule::in(['active', 'trialing', 'past_due', 'canceled', 'unpaid'])],
            'amount_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'billing_cycle' => ['nullable', Rule::in(['monthly', 'yearly'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'cancelled_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);

        $provider = (string) ($validated['provider'] ?? 'manual');
        $externalId = $validated['external_id'] ?? null;
        $subscription = VendorSubscription::query()->updateOrCreate(
            ['provider' => $provider, 'external_id' => $externalId],
            array_merge($validated, ['provider' => $provider, 'external_id' => $externalId])
        );
        $this->subscriptionService->reconcileLicenseStatus($subscription);
        $this->audit->record('vendor.subscription.upserted', 'info', (int) $subscription->tenant_id, $subscription->license_id ? (int) $subscription->license_id : null, (int) $request->user()->id, [
            'subscription_id' => (int) $subscription->id,
            'status' => (string) $subscription->status,
        ], $request);

        return response()->json(['item' => $subscription], 201);
    }

    public function invoices(): JsonResponse
    {
        return response()->json([
            'items' => VendorInvoice::query()->with(['tenant:id,name,slug', 'subscription:id,status'])->latest('id')->paginate(20),
        ]);
    }

    public function payments(): JsonResponse
    {
        return response()->json([
            'items' => VendorPayment::query()->with(['tenant:id,name,slug', 'invoice:id,status'])->latest('id')->paginate(20),
        ]);
    }

    /**
     * Public webhook endpoint (provider->vendor).
     */
    public function webhook(Request $request): JsonResponse
    {
        $secret = (string) config('panelze.vendor_billing_webhook_secret', '');
        if ($secret === '') {
            return response()->json(['message' => 'Webhook secret not configured'], 503);
        }

        $raw = $request->getContent();
        $sig = (string) $request->header('X-Vendor-Signature', '');
        $timestamp = (int) $request->header('X-Vendor-Timestamp', '0');
        $nonce = (string) $request->header('X-Vendor-Nonce', '');
        $ttl = max(30, (int) config('panelze.vendor_request_replay_ttl_seconds', 300));
        if ($timestamp <= 0 || abs(time() - $timestamp) > $ttl) {
            return response()->json(['message' => 'Invalid webhook timestamp'], 401);
        }
        if ($nonce === '' || strlen($nonce) < 12 || strlen($nonce) > 128) {
            return response()->json(['message' => 'Invalid webhook nonce'], 401);
        }
        $nonceKey = 'vendor:webhook:nonce:'.$nonce;
        if (! Cache::add($nonceKey, now()->toIso8601String(), $ttl)) {
            return response()->json(['message' => 'Webhook replay detected'], 409);
        }
        $expected = hash_hmac('sha256', $raw, $secret);
        if ($sig === '' || ! hash_equals($expected, $sig)) {
            return response()->json(['message' => 'Invalid webhook signature'], 401);
        }

        $event = $request->validate([
            'type' => ['required', 'string', 'max:64'],
            'data' => ['required', 'array'],
        ]);
        $data = $event['data'];

        if ($event['type'] === 'subscription.updated' || $event['type'] === 'subscription.created') {
            $externalId = (string) ($data['external_id'] ?? '');
            if ($externalId === '') {
                return response()->json(['message' => 'data.external_id required'], 422);
            }
            $tenantId = (int) ($data['tenant_id'] ?? 0);
            if ($tenantId <= 0 || ! VendorTenant::query()->whereKey($tenantId)->exists()) {
                return response()->json(['message' => 'invalid tenant_id'], 422);
            }
            $licenseId = isset($data['license_id']) ? (int) $data['license_id'] : null;
            if ($licenseId === 0) {
                $licenseId = null;
            }
            if ($licenseId !== null && ! VendorLicense::query()->whereKey($licenseId)->exists()) {
                return response()->json(['message' => 'invalid license_id'], 422);
            }

            $sub = VendorSubscription::query()->updateOrCreate(
                [
                    'provider' => (string) ($data['provider'] ?? 'manual'),
                    'external_id' => $externalId,
                ],
                [
                    'tenant_id' => $tenantId,
                    'license_id' => $licenseId,
                    'status' => (string) ($data['status'] ?? 'active'),
                    'amount_minor' => (int) ($data['amount_minor'] ?? 0),
                    'currency' => (string) ($data['currency'] ?? 'USD'),
                    'billing_cycle' => (string) ($data['billing_cycle'] ?? 'monthly'),
                    'meta' => is_array($data['meta'] ?? null) ? $data['meta'] : null,
                ]
            );
            $this->subscriptionService->reconcileLicenseStatus($sub);
        }

        return response()->json(['received' => true]);
    }
}

