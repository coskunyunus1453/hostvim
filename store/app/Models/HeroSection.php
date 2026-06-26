<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSection extends Model
{
    protected $fillable = [
        'page', 'layout_variant', 'title', 'subtitle', 'description',
        'cta_text', 'cta_url', 'secondary_cta_text', 'secondary_cta_url',
        'image', 'stat_1_value', 'stat_1_label', 'stat_2_value', 'stat_2_label',
        'stat_3_value', 'stat_3_label',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
