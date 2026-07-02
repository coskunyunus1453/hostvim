<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Dosya sayısı (inode) paket sınırına yaklaştığında ya da aştığında müşteriye uyarı.
 * $over=true ise limit aşılmış ve grace süresi işliyordur (askı öncesi son uyarı).
 */
class InodeQuotaWarningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $customerName,
        public int $used,
        public int $limit,
        public float $percent,
        public bool $over,
        public int $remainingGraceDays,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->over
            ? 'Dosya (inode) sınırınız aşıldı — lütfen dosya azaltın'
            : 'Dosya (inode) sayınız paket sınırına yaklaştı';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.quota.inode-warning', with: [
            'customerName' => $this->customerName,
            'used' => $this->used,
            'limit' => $this->limit,
            'percent' => $this->percent,
            'over' => $this->over,
            'remainingGraceDays' => $this->remainingGraceDays,
        ]);
    }
}
