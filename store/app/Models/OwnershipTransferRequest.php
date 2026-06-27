<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnershipTransferRequest extends Model
{
    public const TYPE_DOMAIN = 'domain';
    public const TYPE_HOSTING = 'hosting';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'number',
        'user_id',
        'type',
        'domain_name_id',
        'order_id',
        'subject_domain',
        'target_email',
        'target_user_id',
        'status',
        'customer_note',
        'admin_note',
        'panel_synced',
        'panel_sync_error',
        'processed_at',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'panel_synced' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function domainName(): BelongsTo
    {
        return $this->belongsTo(DomainName::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function typeLabel(): string
    {
        return $this->type === self::TYPE_HOSTING ? 'hosting' : 'alan adı';
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Beklemede',
            self::STATUS_APPROVED => 'Onaylandı',
            self::STATUS_REJECTED => 'Reddedildi',
            self::STATUS_CANCELLED => 'İptal edildi',
            default => $status,
        };
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'DVR-'.strtoupper(bin2hex(random_bytes(3))).'-'.random_int(1000, 9999);
        } while (static::where('number', $number)->exists());

        return $number;
    }
}
