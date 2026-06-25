<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class CampaignService
{
    public const CACHE_KEY = 'campaigns:active';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return Collection<int, Campaign> */
    public function active(): Collection
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            return Campaign::query()
                ->active()
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get();
        });
    }

    /** @return Collection<int, Campaign> */
    public function forDisplay(string $mode): Collection
    {
        return $this->active()->filter(fn (Campaign $c) => $c->hasDisplayMode($mode));
    }

    public function flashBar(): ?Campaign
    {
        return $this->forDisplay('flash_bar')->first();
    }

    public function popup(): ?Campaign
    {
        return $this->forDisplay('popup')->first();
    }

    /**
     * @return array{original: float, final: float, discount: float, campaign: ?Campaign, badge: ?string}
     */
    public function pricingFor(Product $product, string $cycle): array
    {
        $original = (float) ($product->getPriceForCycle($cycle) ?? 0);

        if ($original <= 0) {
            return [
                'original' => 0.0,
                'final' => 0.0,
                'discount' => 0.0,
                'campaign' => null,
                'badge' => null,
            ];
        }

        $campaign = $this->bestAutoCampaignFor($product, $cycle);

        if (! $campaign) {
            return [
                'original' => $original,
                'final' => $original,
                'discount' => 0.0,
                'campaign' => null,
                'badge' => null,
            ];
        }

        $final = $this->applyDiscount($original, $campaign);

        return [
            'original' => $original,
            'final' => $final,
            'discount' => max(0, $original - $final),
            'campaign' => $campaign,
            'badge' => $campaign->badge_text ?: $campaign->discountLabel(),
        ];
    }

    public function bestAutoCampaignFor(Product $product, string $cycle): ?Campaign
    {
        $candidates = $this->active()->filter(function (Campaign $campaign) {
            if ($campaign->requires_code) {
                return false;
            }

            return $campaign->hasDisplayMode('pricing')
                || $campaign->hasDisplayMode('flash_bar')
                || $campaign->hasDisplayMode('popup');
        });

        foreach ($candidates as $campaign) {
            if ($this->matchesProduct($campaign, $product, $cycle)) {
                return $campaign;
            }
        }

        return null;
    }

    public function matchesProduct(Campaign $campaign, Product $product, string $cycle): bool
    {
        if ($campaign->billing_cycles && ! in_array($cycle, $campaign->billing_cycles, true)) {
            return false;
        }

        return match ($campaign->applies_to) {
            'product' => in_array($product->id, $campaign->target_ids ?? [], true),
            'category' => in_array($product->product_category_id, $campaign->target_ids ?? [], true),
            default => true,
        };
    }

    public function applyDiscount(float $amount, Campaign $campaign): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $discount = $campaign->discount_type === 'percent'
            ? $amount * ((float) $campaign->discount_value / 100)
            : (float) $campaign->discount_value;

        return max(0, round($amount - min($discount, $amount), 2));
    }

    public function appliedCouponCode(): ?string
    {
        $code = session('hostvim_coupon_code');

        return $code ? (string) $code : null;
    }

    public function appliedCoupon(): ?Campaign
    {
        $code = $this->appliedCouponCode();
        if (! $code) {
            return null;
        }

        return $this->findCoupon($code);
    }

    public function findCoupon(string $code): ?Campaign
    {
        return $this->active()
            ->first(fn (Campaign $c) => $c->code && strcasecmp($c->code, $code) === 0);
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    public function validateCouponForCart(string $code, array $items): Campaign
    {
        $campaign = $this->findCoupon($code);

        if (! $campaign) {
            throw new InvalidArgumentException('Geçersiz veya süresi dolmuş kupon kodu.');
        }

        if (! $campaign->hasDisplayMode('checkout') && $campaign->requires_code) {
            // Kupon kodu olan kampanyalar checkout'ta kullanılabilir
        }

        $subtotal = collect($items)->sum(fn ($i) => $i['unit_price'] * $i['quantity']);

        if ($campaign->min_order && $subtotal < (float) $campaign->min_order) {
            throw new InvalidArgumentException('Bu kupon için minimum sipariş tutarı: ₺' . number_format((float) $campaign->min_order, 2, ',', '.'));
        }

        return $campaign;
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    public function couponDiscount(Campaign $campaign, array $items): float
    {
        $eligible = collect($items)->filter(function ($item) use ($campaign) {
            $product = Product::find($item['product_id'] ?? 0);
            if (! $product) {
                return false;
            }

            return $this->matchesProduct($campaign, $product, $item['billing_cycle'] ?? 'monthly');
        });

        $base = (float) $eligible->sum(fn ($i) => $i['unit_price'] * $i['quantity']);

        if ($base <= 0) {
            return 0.0;
        }

        $discount = $campaign->discount_type === 'percent'
            ? $base * ((float) $campaign->discount_value / 100)
            : (float) $campaign->discount_value;

        return round(min($discount, $base), 2);
    }

    public function applyCoupon(string $code): void
    {
        session(['hostvim_coupon_code' => strtoupper(trim($code))]);
    }

    public function removeCoupon(): void
    {
        session()->forget('hostvim_coupon_code');
    }

    public function incrementUsage(Campaign $campaign): void
    {
        $campaign->increment('used_count');
        self::clearCache();
    }
}
