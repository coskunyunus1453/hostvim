<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_CUSTOMER_REPLY = 'customer_reply';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'number',
        'user_id',
        'order_id',
        'department',
        'subject',
        'status',
        'priority',
        'last_reply_at',
        'last_reply_by',
    ];

    protected function casts(): array
    {
        return [
            'last_reply_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    public function isOpen(): bool
    {
        return $this->status !== self::STATUS_CLOSED;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_OPEN => 'Açık',
            self::STATUS_ANSWERED => 'Yanıtlandı',
            self::STATUS_CUSTOMER_REPLY => 'Müşteri yanıtı',
            self::STATUS_ON_HOLD => 'Beklemede',
            self::STATUS_CLOSED => 'Kapalı',
            default => $status,
        };
    }

    public static function departmentLabel(string $department): string
    {
        return match ($department) {
            'general' => 'Genel',
            'technical' => 'Teknik',
            'billing' => 'Fatura',
            'sales' => 'Satış',
            default => $department,
        };
    }

    public static function priorityLabel(string $priority): string
    {
        return match ($priority) {
            'low' => 'Düşük',
            'medium' => 'Orta',
            'high' => 'Yüksek',
            default => $priority,
        };
    }
}
