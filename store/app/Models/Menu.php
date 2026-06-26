<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = ['name', 'location'];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }

    public function activeRootItems(): HasMany
    {
        return $this->activeItems()
            ->whereNull('parent_id')
            ->with(['activeChildren' => fn ($q) => $q->with('page')]);
    }
}
