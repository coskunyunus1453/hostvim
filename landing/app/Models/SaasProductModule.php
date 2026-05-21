<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasProductModule extends Model
{
    protected $fillable = [
        'key', 'label', 'description', 'ui_paths', 'api_route_prefixes', 'is_paid', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'ui_paths' => 'array',
            'api_route_prefixes' => 'array',
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
