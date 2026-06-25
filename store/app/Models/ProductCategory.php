<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'color',
        'sort_order', 'is_active', 'meta_title', 'meta_description', 'og_image', 'meta_keywords', 'no_index',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'no_index' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort_order');
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->where('is_active', true);
    }
}
