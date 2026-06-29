<?php

namespace App\Services\Licensing;

use App\Models\SaasLicenseProduct;
use App\Support\PanelFeatureCatalog;

/**
 * Vitrin (ana sayfa + /pricing) için gerçek lisans ürünlerinden dinamik fiyat view-model'i üretir.
 * Community (ücretsiz) + Pro (aylık/yıllık/ömür boyu) — gösterim para birimine göre.
 */
class PricingPresenter
{
    /** code => bucket eşlemesi */
    private const PRO_CODES = [
        'monthly' => 'pro-monthly',
        'yearly' => 'pro-yearly',
        'lifetime' => 'pro-lifetime',
    ];

    public function __construct(private readonly SalesCurrencyService $currency) {}

    /**
     * @return array{
     *   currency: string,
     *   free: array{name: string, price_label: string, period_label: string, max_sites: int, features: list<string>},
     *   pro: array<string, array{code: string, name: string, amount_minor: int, currency: string, price_label: string, period_label: string, interval: string|null, recurring: bool, badge: ?string, monthly_equiv_label: ?string, max_sites: int|null}>,
     *   pro_features: list<string>,
     *   pro_modules: list<array{key: string, label: string, description: string, sort_order: int}>,
     *   core_cards: list<array{title: string, body: string, icon: string}>,
     *   yearly_savings_pct: int|null
     * }
     */
    public function build(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $displayCurrency = $this->currency->displayCurrency();

        $tiers = [];
        foreach (self::PRO_CODES as $bucket => $code) {
            $product = SaasLicenseProduct::query()
                ->where('code', $code)
                ->where('is_active', true)
                ->first();
            if (! $product) {
                continue;
            }
            $price = $this->currency->displayPrice($product);
            if ($price === null) {
                continue;
            }
            $ccy = $price['currency'] ?? $displayCurrency;
            $limits = is_array($product->default_limits) ? $product->default_limits : [];

            $tiers[$bucket] = [
                'code' => $product->code,
                'name' => $product->name,
                'amount_minor' => (int) $price['minor'],
                'currency' => $ccy,
                'price_label' => $this->formatMoney((int) $price['minor'], $ccy),
                'period_label' => $this->periodLabel($product->billing_interval, $locale),
                'interval' => $product->billing_interval,
                'recurring' => $product->isRecurring(),
                'badge' => null,
                'monthly_equiv_label' => null,
                'max_sites' => $limits['max_sites'] ?? null,
            ];
        }

        $savings = null;
        if (isset($tiers['monthly'], $tiers['yearly'])) {
            $fullYear = $tiers['monthly']['amount_minor'] * 12;
            if ($fullYear > 0) {
                $savings = (int) round((1 - ($tiers['yearly']['amount_minor'] / $fullYear)) * 100);
            }
            $tiers['yearly']['monthly_equiv_label'] = $this->formatMoney(
                (int) round($tiers['yearly']['amount_minor'] / 12),
                $tiers['yearly']['currency']
            );
            if ($savings !== null && $savings > 0) {
                $tiers['yearly']['badge'] = $locale === 'tr' ? '%'.$savings.' indirim' : 'Save '.$savings.'%';
            }
        }
        if (isset($tiers['lifetime'])) {
            $tiers['lifetime']['badge'] = $locale === 'tr' ? 'Tek seferlik' : 'One-time';
        }
        if (isset($tiers['monthly'])) {
            $tiers['monthly']['badge'] = $locale === 'tr' ? 'En esnek' : 'Most flexible';
        }

        return [
            'currency' => $displayCurrency,
            'free' => [
                'name' => 'Community',
                'price_label' => $this->zeroLabel($displayCurrency),
                'period_label' => $locale === 'tr' ? 'ücretsiz' : 'free forever',
                'max_sites' => 5,
                'features' => PanelFeatureCatalog::communityPlanFeatures($locale),
            ],
            'pro' => $tiers,
            'pro_features' => PanelFeatureCatalog::proPlanFeatures($locale),
            'pro_modules' => PanelFeatureCatalog::proModuleDefs(),
            'core_cards' => PanelFeatureCatalog::coreFeatureCards($locale),
            'yearly_savings_pct' => $savings,
        ];
    }

    public function formatMoney(int $minor, string $currency): string
    {
        $amount = $minor / 100;
        $symbols = ['TRY' => '₺', 'USD' => '$', 'EUR' => '€'];
        $symbol = $symbols[$currency] ?? ($currency.' ');

        // Tam sayı tutarlarda kuruş gösterme (₺499 yerine ₺499,00 değil).
        $decimals = ($amount == (int) $amount) ? 0 : 2;

        if ($currency === 'USD' || $currency === 'EUR') {
            return $symbol.number_format($amount, $decimals, '.', ',');
        }

        return $symbol.number_format($amount, $decimals, ',', '.');
    }

    private function zeroLabel(string $currency): string
    {
        return match ($currency) {
            'USD' => '$0',
            'EUR' => '€0',
            default => '₺0',
        };
    }

    private function periodLabel(?string $interval, string $locale): string
    {
        return match ($interval) {
            'month' => $locale === 'tr' ? '/ ay' : '/ mo',
            'year' => $locale === 'tr' ? '/ yıl' : '/ yr',
            default => $locale === 'tr' ? 'tek seferlik' : 'one-time',
        };
    }
}
