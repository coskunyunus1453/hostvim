<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminNotification extends Model
{
    public const TYPE_CONTACT_MESSAGE = 'contact_message';

    public const TYPE_SUPPORT_TICKET_NEW = 'support_ticket_new';

    public const TYPE_SUPPORT_TICKET_REPLY = 'support_ticket_reply';

    public const TYPE_ORDER_NEW = 'order_new';

    public const TYPE_ORDER_PAID = 'order_paid';

    public const TYPE_ORDER_AWAITING_TRANSFER = 'order_awaiting_transfer';

    public const TYPE_ORDER_CANCELLED = 'order_cancelled';

    public const TYPE_ORDER_PAYMENT_FAILED = 'order_payment_failed';

    public const TYPE_ORDER_REFUNDED = 'order_refunded';

    public const TYPE_PROVISION_PANEL_FAILED = 'provision_panel_failed';

    public const TYPE_PROVISION_CLOUD_FAILED = 'provision_cloud_failed';

    public const TYPE_PROVISION_DOMAIN_FAILED = 'provision_domain_failed';

    public const TYPE_CLOUD_SERVER_FAILED = 'cloud_server_failed';

    public const TYPE_PAYMENT_EXPIRING = 'payment_expiring';

    protected $fillable = [
        'type',
        'title',
        'body',
        'icon',
        'color',
        'action_url',
        'notifiable_type',
        'notifiable_id',
        'dedupe_key',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at !== null) {
            return;
        }

        $this->forceFill(['read_at' => now()])->save();
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }
}
