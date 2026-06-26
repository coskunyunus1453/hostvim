<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Models\HostingPackage;
use App\Services\Billing\BillingSettings;
use App\Services\Domain\DomainAvailabilityService;
use App\Services\Integrations\StoreFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StoreIntegrationController extends Controller
{
    public function __construct(
        private StoreFulfillmentService $fulfillment,
        private DomainAvailabilityService $domains,
        private BillingSettings $settings,
    ) {}

    public function test(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'panel' => 'panelze',
            'integration' => 'store',
            'version' => config('panelze.version', '0.1.0'),
        ]);
    }

    public function packages(): JsonResponse
    {
        $packages = HostingPackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'price_monthly', 'price_yearly']);

        return response()->json(['packages' => $packages]);
    }

    public function domainTlds(): JsonResponse
    {
        if (! (bool) $this->settings->get('domain_register_enabled', true)) {
            return response()->json(['enabled' => false, 'tlds' => []]);
        }

        return response()->json([
            'enabled' => true,
            'currency' => $this->settings->currency(),
            'tlds' => $this->domains->listTlds(),
        ]);
    }

    public function domainCheck(Request $request): JsonResponse
    {
        $validated = $request->validate(['domain' => ['required', 'string', 'max:253']]);

        if (! (bool) $this->settings->get('domain_register_enabled', true)) {
            return response()->json(['available' => false, 'reason' => 'disabled'], 503);
        }

        return response()->json($this->domains->check($validated['domain']));
    }

    public function fulfillStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_order_number' => ['required', 'string', 'max:64'],
        ]);

        $result = $this->fulfillment->status($validated['store_order_number']);

        if ($result === null) {
            return response()->json(['found' => false]);
        }

        return response()->json($result);
    }

    public function fulfill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_order_number' => ['required', 'string', 'max:64'],
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:30'],
            'customer.locale' => ['nullable', 'string', 'max:10'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.item_type' => ['nullable', 'string', 'in:hosting,domain_register,manual'],
            'items.*.package_id' => ['nullable', 'integer', 'min:1'],
            'items.*.billing_cycle' => ['nullable', 'string', 'in:monthly,yearly,onetime'],
            'items.*.domain' => ['nullable', 'string', 'max:253'],
            'items.*.domain_years' => ['nullable', 'integer', 'min:1', 'max:10'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.registrar_api' => ['nullable', 'string', 'max:40'],
            'payment' => ['nullable', 'array'],
            'payment.method' => ['nullable', 'string', 'max:40'],
            'payment.reference' => ['nullable', 'string', 'max:120'],
        ]);

        foreach ($validated['items'] as $index => $item) {
            $type = strtolower(trim((string) ($item['item_type'] ?? 'hosting')));

            if ($type === 'hosting') {
                $packageId = (int) ($item['package_id'] ?? 0);
                if ($packageId < 1 || ! HostingPackage::query()->where('is_active', true)->whereKey($packageId)->exists()) {
                    throw ValidationException::withMessages([
                        "items.{$index}.package_id" => 'Geçerli hosting paketi gerekli.',
                    ]);
                }
                if (isset($item['domain']) && $item['domain'] !== '' && ! preg_match('/^([a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $item['domain'])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.domain" => 'Geçersiz alan adı.',
                    ]);
                }
            } elseif ($type === 'domain_register') {
                if (empty($item['domain'])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.domain" => 'Alan adı gerekli.',
                    ]);
                }
            } elseif ($type === 'manual') {
                if (empty($item['product_name'])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_name" => 'Ürün adı gerekli.',
                    ]);
                }
                if (! isset($item['unit_price']) || (float) $item['unit_price'] <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.unit_price" => 'Geçerli fiyat gerekli.',
                    ]);
                }
            }
        }

        try {
            $result = $this->fulfillment->fulfill($validated);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Doğrulama hatası.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json($result);
    }
}
