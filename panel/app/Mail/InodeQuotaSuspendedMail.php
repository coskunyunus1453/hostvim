<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * inode kotası grace süresince aşımda kalınca siteler askıya alındığında müşteriye bildirim.
 */
class InodeQuotaSuspendedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param list<string> $domains */
    public function __construct(
        public string $customerName,
        public int $used,
        public int $limit,
        public array $domains,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Siteleriniz dosya (inode) sınırı nedeniyle askıya alındı');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.quota.inode-suspended', with: [
            'customerName' => $this->customerName,
            'used' => $this->used,
            'limit' => $this->limit,
            'domains' => $this->domains,
        ]);
    }
}
