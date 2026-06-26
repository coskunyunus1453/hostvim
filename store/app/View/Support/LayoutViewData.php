<?php

namespace App\View\Support;

use Illuminate\Support\Facades\View;

/**
 * Request başına tek sefer layout verisi üretir (partial composer tekrarlarını önler).
 */
class LayoutViewData
{
    /** @var array<string, mixed>|null */
    protected static ?array $payload = null;

    /**
     * @param  callable(): array<string, mixed>  $builder
     * @return array<string, mixed>
     */
    public static function resolve(callable $builder): array
    {
        if (self::$payload === null) {
            self::$payload = $builder();
            View::share(self::$payload);
        }

        return self::$payload;
    }

    public static function reset(): void
    {
        self::$payload = null;
    }
}
