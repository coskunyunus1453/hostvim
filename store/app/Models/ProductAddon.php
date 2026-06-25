<?php

namespace App\Models;

use App\Support\BillingCycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAddon extends Model
{
    protected $fillable = [
        'product_id', 'name', 'description',
        'price_monthly', 'price_yearly', 'price_onetime',
        'billing_mode', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'price_onetime' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceForParentCycle(string $parentCycle): ?float
    {
        return match ($this->billing_mode) {
            'onetime' => $this->price_onetime !== null ? (float) $this->price_onetime : null,
            'yearly' => $this->price_yearly !== null ? (float) $this->price_yearly : null,
            'monthly' => $this->price_monthly !== null ? (float) $this->price_monthly : null,
            default => match (BillingCycle::panelCycle($parentCycle)) {
                BillingCycle::YEARLY => $this->price_yearly ?? $this->price_monthly,
                default => $this->price_monthly ?? $this->price_yearly,
            },
        };
    }
}
