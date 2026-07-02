<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Disk kullanımı limit altına inip askıya alınan siteler yeniden açıldığında müşteriye bildirim.
 */
class DiskQuotaRestoredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param list<string> $domains */
    public function __construct(
        public string $customerName,
        public array $domains,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Siteleriniz yeniden aktif');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.quota.restored', with: [
            'customerName' => $this->customerName,
            'domains' => $this->domains,
        ]);
    }
}
