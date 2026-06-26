<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'product_category_id', 'panel_package_id', 'cloud_provider_api', 'cloud_region', 'cloud_plan', 'cloud_image', 'provision_type', 'name', 'slug', 'short_description', 'description',
        'price_monthly', 'price_quarterly', 'price_semiannual', 'price_yearly', 'price_biennial', 'price_triennial', 'price_onetime', 'currency',
        'cost_monthly', 'cost_quarterly', 'cost_semiannual', 'cost_yearly', 'cost_biennial', 'cost_triennial', 'cost_onetime',
        'features', 'specs', 'is_popular', 'is_active', 'sort_order',
        'meta_title', 'meta_description', 'og_image', 'meta_keywords', 'no_index',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'specs' => 'array',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'no_index' => 'boolean',
            'price_monthly' => 'decimal:2',
            'price_quarterly' => 'decimal:2',
            'price_semiannual' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'price_biennial' => 'decimal:2',
            'price_triennial' => 'decimal:2',
            'price_onetime' => 'decimal:2',
            'cost_monthly' => 'decimal:2',
            'cost_quarterly' => 'decimal:2',
            'cost_semiannual' => 'decimal:2',
            'cost_yearly' => 'decimal:2',
            'cost_biennial' => 'decimal:2',
            'cost_triennial' => 'decimal:2',
            'cost_onetime' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ProductAddon::class)->orderBy('sort_order');
    }

    public function activeAddons(): HasMany
    {
        return $this->addons()->where('is_active', true);
    }

    public function getPriceForCycle(string $cycle): ?float
    {
        return match ($cycle) {
            'monthly' => $this->price_monthly,
            'quarterly' => $this->price_quarterly,
            'semiannual' => $this->price_semiannual,
            'yearly' => $this->price_yearly,
            'biennial' => $this->price_biennial,
            'triennial' => $this->price_triennial,
            'onetime' => $this->price_onetime,
            default => $this->price_monthly,
        };
    }

    public function getCostForCycle(string $cycle): ?float
    {
        $cost = match ($cycle) {
            'monthly' => $this->cost_monthly,
            'quarterly' => $this->cost_quarterly,
            'semiannual' => $this->cost_semiannual,
            'yearly' => $this->cost_yearly,
            'biennial' => $this->cost_biennial,
            'triennial' => $this->cost_triennial,
            'onetime' => $this->cost_onetime,
            default => $this->cost_monthly,
        };

        return $cost !== null ? (float) $cost : null;
    }

    public function estimatedMarginPercent(string $cycle = 'monthly'): ?float
    {
        $price = $this->getPriceForCycle($cycle);
        $cost = $this->getCostForCycle($cycle);

        if ($price === null || $cost === null || (float) $price <= 0) {
            return null;
        }

        return round((((float) $price - (float) $cost) / (float) $price) * 100, 1);
    }

    public function isHosting(): bool
    {
        return $this->resolvedProvisionType() === 'hosting';
    }

    public function isDomain(): bool
    {
        return $this->resolvedProvisionType() === 'domain';
    }

    public function isManualProvision(): bool
    {
        return $this->resolvedProvisionType() === 'manual';
    }

    public function isCloudProvision(): bool
    {
        return $this->resolvedProvisionType() === 'cloud';
    }

    public function resolvedProvisionType(): string
    {
        $type = (string) ($this->provision_type ?? '');
        if (in_array($type, ['hosting', 'domain', 'manual', 'cloud'], true)) {
            return $type;
        }

        $slug = $this->category?->slug;

        return match ($slug) {
            'domain' => 'domain',
            'vps', 'vds' => 'cloud',
            'dedicated' => 'manual',
            default => 'hosting',
        };
    }
}
