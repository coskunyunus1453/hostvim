<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\CampaignService;
use App\Services\CartService;
use App\Services\HostingConfigureService;
use App\Services\Domain\DomainAvailabilityService;
use App\Services\Domain\DomainSettings;
use App\Support\BillingCycle;
use Illuminate\Http\Request;
use InvalidArgumentException;

class HostingConfigureController extends Controller
{
    public function start(string $categorySlug, string $slug, HostingConfigureService $configure)
    {
        $category = ProductCategory::where('slug', $categorySlug)->where('is_active', true)->firstOrFail();
        $product = Product::where('slug', $slug)
            ->where('product_category_id', $category->id)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $product->isHosting()) {
            return redirect()->route('products.show', [$categorySlug, $slug]);
        }

        $configure->start($product, $category);

        return redirect()->route('hosting.configure.domain');
    }

    public function domain(HostingConfigureService $configure)
    {
        $product = $configure->resolveProduct();
        if (! $product) {
            return redirect()->route('products.index');
        }

        $data = $configure->get();

        return view('hosting.configure.domain', [
            'product' => $product,
            'config' => $data,
            'step' => 1,
        ]);
    }

    public function storeDomain(Request $request, HostingConfigureService $configure, DomainAvailabilityService $domains, DomainSettings $domainSettings)
    {
        $product = $configure->resolveProduct();
        if (! $product) {
            return redirect()->route('products.index');
        }

        $validated = $request->validate([
            'domain_mode' => 'required|in:register,transfer,own',
            'domain_name' => 'required|string|max:253|regex:/^([a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
            'domain_years' => 'nullable|integer|min:1|max:10',
        ]);

        $domain = strtolower(trim($validated['domain_name']));
        $mode = $validated['domain_mode'];

        if ($mode === 'register' && $domainSettings->registerEnabled()) {
            $check = $domains->check($domain);
            if (! ($check['available'] ?? false)) {
                return back()->withInput()->with('error', 'Bu alan adı kayıt için müsait değil.');
            }
        }

        $configure->update([
            'domain_mode' => $mode,
            'domain_name' => $domain,
            'domain_years' => (int) ($validated['domain_years'] ?? 1),
        ]);

        return redirect()->route('hosting.configure.options');
    }

    public function options(HostingConfigureService $configure, CampaignService $campaigns)
    {
        $product = $configure->resolveProduct();
        $data = $configure->get();

        if (! $product || empty($data['domain_mode']) || empty($data['domain_name'])) {
            return redirect()->route('hosting.configure.domain');
        }

        $cycles = [];
        foreach (BillingCycle::availableForProduct($product) as $cycle) {
            if ($cycle === BillingCycle::ONETIME) {
                continue;
            }
            $pricing = $campaigns->pricingFor($product, $cycle);
            $cycles[$cycle] = [
                'label' => BillingCycle::label($cycle),
                'price' => $pricing['final'],
                'original' => $pricing['original'],
                'discount' => $pricing['discount'],
                'savings' => BillingCycle::savingsPercent($product, $cycle),
            ];
        }

        return view('hosting.configure.options', [
            'product' => $product,
            'config' => $data,
            'cycles' => $cycles,
            'addons' => $product->activeAddons,
            'step' => 2,
        ]);
    }

    public function storeOptions(Request $request, HostingConfigureService $configure)
    {
        $product = $configure->resolveProduct();
        if (! $product) {
            return redirect()->route('products.index');
        }

        $cycles = BillingCycle::availableForProduct($product);
        $cycles = array_filter($cycles, fn ($c) => $c !== BillingCycle::ONETIME);

        $validated = $request->validate([
            'billing_cycle' => 'required|in:'.implode(',', $cycles),
            'addon_ids' => 'nullable|array',
            'addon_ids.*' => 'integer|exists:product_addons,id',
        ]);

        $configure->update([
            'billing_cycle' => $validated['billing_cycle'],
            'addon_ids' => $validated['addon_ids'] ?? [],
        ]);

        return redirect()->route('hosting.configure.review');
    }

    public function review(HostingConfigureService $configure, CampaignService $campaigns)
    {
        $product = $configure->resolveProduct();
        $data = $configure->get();

        if (! $product || empty($data['billing_cycle'])) {
            return redirect()->route('hosting.configure.options');
        }

        $cycle = (string) $data['billing_cycle'];
        $pricing = $campaigns->pricingFor($product, $cycle);
        $addonLines = [];
        $addonTotal = 0.0;

        foreach ($product->activeAddons()->whereIn('id', $data['addon_ids'] ?? [])->get() as $addon) {
            $p = $addon->priceForParentCycle($cycle);
            if ($p === null) {
                continue;
            }
            $addonLines[] = ['name' => $addon->name, 'price' => $p];
            $addonTotal += $p;
        }

        $domainPrice = 0.0;
        if (($data['domain_mode'] ?? '') === 'register') {
            // Tahmini — sepete eklenirken güncellenir
            $domainPrice = 0;
        }

        return view('hosting.configure.review', [
            'product' => $product,
            'config' => $data,
            'pricing' => $pricing,
            'addonLines' => $addonLines,
            'hostingTotal' => $pricing['final'] + $addonTotal,
            'step' => 3,
        ]);
    }

    public function complete(CartService $cart, HostingConfigureService $configure)
    {
        $product = $configure->resolveProduct();
        $data = $configure->get();

        if (! $product || empty($data['billing_cycle']) || empty($data['domain_name'])) {
            return redirect()->route('products.index');
        }

        try {
            $cart->addConfiguredHosting($product, (string) $data['billing_cycle'], [
                'domain_mode' => (string) $data['domain_mode'],
                'domain_name' => (string) $data['domain_name'],
                'domain_years' => (int) ($data['domain_years'] ?? 1),
                'addon_ids' => $data['addon_ids'] ?? [],
            ]);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('hosting.configure.review')->with('error', $e->getMessage());
        }

        $configure->clear();

        return redirect()->route('checkout.index')->with('success', 'Hosting yapılandırması sepete eklendi. Ödemeye geçebilirsiniz.');
    }
}
