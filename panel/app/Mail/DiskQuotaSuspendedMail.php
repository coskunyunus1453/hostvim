<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Disk kotası grace süresince aşımda kalınca siteler askıya alındığında müşteriye bildirim.
 */
class DiskQuotaSuspendedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param list<string> $domains */
    public function __construct(
        public string $customerName,
        public float $usedMb,
        public float $limitMb,
        public array $domains,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Siteleriniz disk kotası nedeniyle askıya alındı');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.quota.suspended', with: [
            'customerName' => $this->customerName,
            'usedMb' => $this->usedMb,
            'limitMb' => $this->limitMb,
            'domains' => $this->domains,
        ]);
    }
}
