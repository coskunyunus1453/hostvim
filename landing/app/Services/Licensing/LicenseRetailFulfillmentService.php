<?php

namespace App\Services\Licensing;

use App\Mail\LicenseKeyDelivered;
use App\Models\SaasCheckoutOrder;
use App\Models\SaasCustomer;
use App\Models\SaasLicense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class LicenseRetailFulfillmentService
{
    public function fulfillIfPending(SaasCheckoutOrder $order, string $billingReference, ?string $subscriptionId = null): ?SaasLicense
    {
        if ($order->status === 'completed' && $order->saas_license_id) {
            return $order->license;
        }

        return DB::transaction(function () use ($order, $billingReference, $subscriptionId): ?SaasLicense {
            /** @var SaasCheckoutOrder $locked */
            $locked = SaasCheckoutOrder::query()->whereKey($order->id)->lockForUpdate()->first();
            if (! $locked) {
                return null;
            }
            if ($locked->status === 'completed' && $locked->saas_license_id) {
                return $locked->license;
            }

            $product = $locked->product;
            if (! $product || ! $product->is_active) {
                $locked->update([
                    'status' => 'failed',
                    'failure_note' => 'product_inactive',
                ]);

                return null;
            }

            $email = strtolower(trim($locked->email));
            $name = trim((string) ($locked->name ?? '')) ?: $email;

            $customer = SaasCustomer::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'status' => 'active',
                ]
            );

            if ($customer->name === '' || $customer->name === $customer->email) {
                $customer->forceFill(['name' => $name])->save();
            }

            $licenseKey = 'hv_'.Str::lower(Str::random(32));

            $isRecurring = $product->isRecurring();
            $isStripeSub = $isRecurring && $locked->provider === 'stripe' && $subscriptionId !== null;

            $expiresAt = $isRecurring ? $this->addInterval(now(), (string) $product->billing_interval) : null;

            $license = SaasLicense::query()->create([
                'license_key' => $licenseKey,
                'saas_customer_id' => $customer->id,
                'saas_license_product_id' => $product->id,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'limits_override' => null,
                'modules_override' => null,
                // Stripe aboneliği otomatik yenilenir; diğer sağlayıcılarda dönem sonunda manuel yenileme gerekir.
                'subscription_status' => $isRecurring ? ($isStripeSub ? 'active' : 'manual') : 'active',
                'subscription_renews_at' => $isStripeSub ? $expiresAt : null,
                'billing_provider' => $locked->provider,
                'billing_reference' => $billingReference,
                'subscription_provider_id' => $isStripeSub ? $subscriptionId : null,
                'notes' => 'Retail checkout '.$locked->order_ref,
            ]);

            $locked->update([
                'status' => 'completed',
                'saas_license_id' => $license->id,
                'paid_at' => now(),
            ]);

            try {
                Mail::to($locked->email)->send(new LicenseKeyDelivered($locked->fresh()->load('product'), $license));
            } catch (Throwable $e) {
                Log::error('License email send failed: '.$e->getMessage(), [
                    'order_ref' => $locked->order_ref,
                    'email' => $locked->email,
                ]);
            }

            return $license;
        });
    }

    /**
     * Yinelenen abonelik faturası ödendiğinde lisansın bitiş/yenilenme tarihini Stripe'ın
     * dönem sonuna (mutlak değer) eşitler — bu yüzden çift sayım olmaz, idempotenttir.
     */
    public function renewSubscription(string $subscriptionId, \DateTimeInterface $periodEnd): ?SaasLicense
    {
        $license = SaasLicense::query()
            ->where('subscription_provider_id', $subscriptionId)
            ->first();

        if (! $license) {
            return null;
        }

        $license->update([
            'expires_at' => $periodEnd,
            'subscription_renews_at' => $periodEnd,
            'subscription_status' => 'active',
            'status' => 'active',
        ]);

        return $license;
    }

    public function markSubscriptionStatus(string $subscriptionId, string $status): ?SaasLicense
    {
        $license = SaasLicense::query()
            ->where('subscription_provider_id', $subscriptionId)
            ->first();

        if (! $license) {
            return null;
        }

        $update = ['subscription_status' => $status];
        if ($status === 'canceled') {
            // Yenileme durur; erişim mevcut dönem sonuna (expires_at) kadar devam eder.
            $update['subscription_renews_at'] = null;
        }

        $license->update($update);

        return $license;
    }

    private function addInterval(\Illuminate\Support\Carbon $from, string $interval): \Illuminate\Support\Carbon
    {
        return $interval === 'year'
            ? $from->copy()->addYearNoOverflow()
            : $from->copy()->addMonthNoOverflow();
    }
}
