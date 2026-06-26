<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_SENT = 'sent';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ERROR = 'error';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_EINVOICE = 'einvoice';

    public const TYPE_EARCHIVE = 'earchive';

    protected $fillable = [
        'order_id',
        'invoice_number',
        'type',
        'status',
        'provider',
        'provider_uuid',
        'provider_invoice_id',
        'customer_name',
        'customer_email',
        'customer_tax_office',
        'customer_tax_number',
        'customer_address',
        'subtotal',
        'tax_total',
        'total',
        'tax_rate',
        'currency',
        'issued_at',
        'pdf_path',
        'error_message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'issued_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isIssued(): bool
    {
        return in_array($this->status, [self::STATUS_ISSUED, self::STATUS_SENT, self::STATUS_ACCEPTED], true);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'Taslak (Proforma)',
            self::STATUS_QUEUED => 'Sıraya alındı',
            self::STATUS_ISSUED => 'E-Fatura kesildi',
            self::STATUS_SENT => 'Gönderildi',
            self::STATUS_ACCEPTED => 'Kabul edildi',
            self::STATUS_REJECTED => 'Reddedildi',
            self::STATUS_ERROR => 'Hata',
            self::STATUS_CANCELLED => 'İptal',
            default => (string) $status,
        };
    }

    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            self::TYPE_EINVOICE => 'e-Fatura',
            self::TYPE_EARCHIVE => 'e-Arşiv',
            default => (string) $type,
        };
    }

    /** @return list<array{name: string, quantity: int, unit_price: float, total: float, tax_rate: float}> */
    public function lines(): array
    {
        $lines = $this->meta['lines'] ?? [];

        return is_array($lines) ? $lines : [];
    }
}
