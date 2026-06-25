<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'name', 'slug', 'title', 'description', 'badge_text', 'code',
        'discount_type', 'discount_value', 'min_order', 'applies_to', 'target_ids',
        'billing_cycles', 'display_modes', 'requires_code', 'show_countdown',
        'bar_color', 'cta_text', 'cta_url', 'popup_image',
        'starts_at', 'ends_at', 'max_uses', 'used_count', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_ids' => 'array',
            'billing_cycles' => 'array',
            'display_modes' => 'array',
            'requires_code' => 'boolean',
            'show_countdown' => 'boolean',
            'is_active' => 'boolean',
            'discount_value' => 'decimal:2',
            'min_order' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
            });
    }

    public function hasDisplayMode(string $mode): bool
    {
        return in_array($mode, $this->display_modes ?? [], true);
    }

    public function discountLabel(): string
    {
        if ($this->discount_type === 'percent') {
            return '%' . rtrim(rtrim(number_format((float) $this->discount_value, 2, ',', '.'), '0'), ',');
        }

        return '₺' . number_format((float) $this->discount_value, 0, ',', '.');
    }
}
