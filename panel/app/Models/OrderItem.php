<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'hosting_package_id',
        'billing_cycle',
        'domain',
        'unit_price',
        'setup_fee',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'setup_fee' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function hostingPackage(): BelongsTo
    {
        return $this->belongsTo(HostingPackage::class);
    }
}
