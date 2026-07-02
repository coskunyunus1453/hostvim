<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Disk kullanımı paket sınırına yaklaştığında ya da aştığında müşteriye uyarı.
 * $over=true ise limit fiilen aşılmış ve grace süresi işliyordur (askı öncesi son uyarı).
 */
class DiskQuotaWarningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $customerName,
        public float $usedMb,
        public float $limitMb,
        public float $percent,
        public bool $over,
        public int $remainingGraceDays,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->over
            ? 'Disk kotanız aşıldı — lütfen yer açın'
            : 'Disk kullanımınız paket sınırına yaklaştı';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.quota.warning', with: [
            'customerName' => $this->customerName,
            'usedMb' => $this->usedMb,
            'limitMb' => $this->limitMb,
            'percent' => $this->percent,
            'over' => $this->over,
            'remainingGraceDays' => $this->remainingGraceDays,
        ]);
    }
}
