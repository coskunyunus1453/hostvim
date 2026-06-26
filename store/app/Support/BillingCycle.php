<?php

namespace App\Support;

use App\Models\Product;

class BillingCycle
{
    public const MONTHLY = 'monthly';

    public const QUARTERLY = 'quarterly';

    public const SEMIANNUAL = 'semiannual';

    public const YEARLY = 'yearly';

    public const BIENNIAL = 'biennial';

    public const TRIENNIAL = 'triennial';

    public const ONETIME = 'onetime';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::MONTHLY,
            self::QUARTERLY,
            self::SEMIANNUAL,
            self::YEARLY,
            self::BIENNIAL,
            self::TRIENNIAL,
            self::ONETIME,
        ];
    }

    public static function label(string $cycle): string
    {
        return match ($cycle) {
            self::MONTHLY => 'Aylık',
            self::QUARTERLY => '3 Aylık',
            self::SEMIANNUAL => '6 Aylık',
            self::YEARLY => 'Yıllık',
            self::BIENNIAL => '2 Yıllık',
            self::TRIENNIAL => '3 Yıllık',
            self::ONETIME => 'Tek Seferlik',
            default => $cycle,
        };
    }

    /** Panel provizyonu için monthly / yearly eşlemesi */
    public static function panelCycle(string $cycle): string
    {
        return in_array($cycle, [self::YEARLY, self::BIENNIAL, self::TRIENNIAL], true)
            ? self::YEARLY
            : self::MONTHLY;
    }

    public static function isValid(string $cycle): bool
    {
        return in_array($cycle, self::all(), true);
    }

    /** @return list<string> */
    public static function availableForProduct(Product $product): array
    {
        $cycles = [];
        foreach (self::all() as $cycle) {
            $price = $product->getPriceForCycle($cycle);
            if ($price !== null && (float) $price > 0) {
                $cycles[] = $cycle;
            }
        }

        return $cycles;
    }

    public static function savingsPercent(Product $product, string $cycle): ?float
    {
        $monthly = (float) ($product->price_monthly ?? 0);
        if ($monthly <= 0) {
            return null;
        }

        $price = (float) ($product->getPriceForCycle($cycle) ?? 0);
        if ($price <= 0) {
            return null;
        }

        $months = match ($cycle) {
            self::QUARTERLY => 3,
            self::SEMIANNUAL => 6,
            self::YEARLY => 12,
            self::BIENNIAL => 24,
            self::TRIENNIAL => 36,
            default => null,
        };

        if ($months === null) {
            return null;
        }

        $baseline = $monthly * $months;
        if ($baseline <= $price) {
            return null;
        }

        return round((($baseline - $price) / $baseline) * 100);
    }
}
