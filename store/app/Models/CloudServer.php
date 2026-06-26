<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudServer extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROVISIONING = 'provisioning';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DESTROYED = 'destroyed';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'provider_api',
        'external_id',
        'hostname',
        'region',
        'plan',
        'image',
        'ipv4',
        'ipv6',
        'root_password',
        'status',
        'provision_error',
        'meta',
        'provisioned_at',
    ];

    protected $hidden = [
        'root_password',
    ];

    protected function casts(): array
    {
        return [
            'root_password' => 'encrypted',
            'meta' => 'array',
            'provisioned_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
