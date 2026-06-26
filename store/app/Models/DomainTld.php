<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainTld extends Model
{
    protected $fillable = [
        'tld',
        'register_price',
        'renew_price',
        'transfer_price',
        'wholesale_register',
        'wholesale_renew',
        'wholesale_registrar_api',
        'registrar_api_name',
        'markup_percent',
        'is_active',
        'sort_order',
        'prices_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'register_price' => 'decimal:2',
            'renew_price' => 'decimal:2',
            'transfer_price' => 'decimal:2',
            'wholesale_register' => 'decimal:2',
            'wholesale_renew' => 'decimal:2',
            'markup_percent' => 'decimal:2',
            'is_active' => 'boolean',
            'prices_synced_at' => 'datetime',
        ];
    }

    public function normalizedTld(): string
    {
        $tld = strtolower(trim($this->tld));

        return str_starts_with($tld, '.') ? $tld : '.'.$tld;
    }
}
