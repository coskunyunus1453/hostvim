<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'page_id', 'label', 'url', 'sort_order', 'target', 'is_active',
        'dropdown_style', 'icon', 'description', 'badge',
        'panel_title', 'panel_text', 'panel_cta_label', 'panel_cta_url',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    public function isDropdown(): bool
    {
        if (in_array($this->dropdown_style, ['dropdown', 'mega', 'mega_wide'], true)) {
            return true;
        }

        if ($this->relationLoaded('activeChildren')) {
            return $this->activeChildren->isNotEmpty();
        }

        return false;
    }

    public function isMega(): bool
    {
        return in_array($this->dropdown_style, ['mega', 'mega_wide'], true);
    }

    public function isMegaWide(): bool
    {
        return $this->dropdown_style === 'mega_wide';
    }

    public function hasPanel(): bool
    {
        return filled($this->panel_title) || filled($this->panel_text);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function getHrefAttribute(): string
    {
        if ($this->page_id && $this->page) {
            return route('pages.show', $this->page->slug);
        }

        $url = trim($this->url ?? '');

        if ($url === '' || $url === '#') {
            return '#';
        }

        if (str_starts_with($url, '//')) {
            return '#';
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return '#';
    }

    public function getSafeTargetAttribute(): string
    {
        return in_array($this->target, ['_self', '_blank'], true) ? $this->target : '_self';
    }
}
