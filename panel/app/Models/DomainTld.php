<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainTld extends Model
{
    protected $fillable = [
        'tld',
        'register_price',
        'renew_price',
        'enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'register_price' => 'decimal:2',
            'renew_price' => 'decimal:2',
            'enabled' => 'boolean',
        ];
    }
}
