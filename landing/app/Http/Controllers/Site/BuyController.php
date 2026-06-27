<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\LandingSiteSetting;
use App\Models\SaasLicenseProduct;
use App\Services\Licensing\SalesCurrencyService;
use Illuminate\View\View;

class BuyController extends Controller
{
    public function index(SalesCurrencyService $salesCurrency): View
    {
        $locale = app()->getLocale();
        $displayCurrency = $salesCurrency->displayCurrency();

        $products = SaasLicenseProduct::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (SaasLicenseProduct $product) use ($salesCurrency, $displayCurrency) {
                $price = $salesCurrency->displayPrice($product);
                if ($price === null) {
                    return null;
                }

                $limits = is_array($product->default_limits) ? $product->default_limits : [];
                $maxSites = $limits['max_sites'] ?? null;

                return [
                    'code' => $product->code,
                    'name' => $product->name,
                    'description' => $product->description,
                    'max_sites' => $maxSites,
                    'amount_minor' => $price['minor'],
                    'currency' => $price['currency'] ?? $displayCurrency,
                    'price_label' => $this->formatMoney($price['minor'], $price['currency'] ?? $displayCurrency),
                    'recurring' => $product->isRecurring(),
                    'interval' => $product->billing_interval,
                ];
            })
            ->filter()
            ->values();

        $bankEnabled = trim((string) (LandingSiteSetting::getValue('billing.methods.bank_transfer.enabled', '0') ?? '0')) === '1';

        return view('site.buy', [
            'products' => $products,
            'bankEnabled' => $bankEnabled,
            'displayCurrency' => $displayCurrency,
            'seoCanonical' => landing_url_with_lang(route('site.buy', absolute: true), $locale),
        ]);
    }

    private function formatMoney(int $minor, string $currency): string
    {
        $amount = $minor / 100;
        $symbols = ['TRY' => '₺', 'USD' => '$', 'EUR' => '€'];
        $symbol = $symbols[$currency] ?? ($currency.' ');
        $formatted = number_format($amount, 2, ',', '.');

        if ($currency === 'USD' || $currency === 'EUR') {
            return $symbol.number_format($amount, 2, '.', ',');
        }

        return $symbol.$formatted;
    }
}
