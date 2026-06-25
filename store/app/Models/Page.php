<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'excerpt',
        'meta_title', 'meta_description', 'og_image', 'meta_keywords', 'no_index',
        'show_in_menu', 'menu_label', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_menu' => 'boolean',
            'no_index' => 'boolean',
        ];
    }
}
