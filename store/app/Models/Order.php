<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'panel_order_id', 'panel_order_number', 'user_id', 'payment_method_id', 'status', 'payment_status',
        'payment_reference', 'subtotal', 'discount_amount', 'coupon_code', 'campaign_id',
        'total', 'currency', 'billing_cycle',
        'customer_name', 'customer_email', 'customer_phone', 'customer_company',
        'customer_address', 'notes', 'payment_data',
        'panel_provision_status', 'panel_provision_error', 'panel_provisioned_at',
        'cloud_provision_status', 'cloud_provision_error', 'cloud_provisioned_at',
    ];

    protected $hidden = [
        'payment_data',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'payment_data' => 'encrypted:array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function cloudServers(): HasMany
    {
        return $this->hasMany(CloudServer::class);
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'HV-' . strtoupper(bin2hex(random_bytes(4))) . '-' . random_int(1000, 9999);
        } while (static::where('order_number', $number)->exists());

        return $number;
    }
}
