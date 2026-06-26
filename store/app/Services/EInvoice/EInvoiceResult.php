<?php

namespace App\Services\EInvoice;

/**
 * E-fatura sağlayıcı işlemlerinin tek tip sonucu.
 */
class EInvoiceResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public bool $ok,
        public string $message = '',
        public ?string $uuid = null,
        public ?string $providerInvoiceId = null,
        public ?string $status = null,
        public ?string $pdf = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function success(string $message = 'Başarılı', array $raw = []): self
    {
        return new self(ok: true, message: $message, raw: $raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function failure(string $message, array $raw = []): self
    {
        return new self(ok: false, message: $message, raw: $raw);
    }
}
