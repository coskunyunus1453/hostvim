<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Son 24 saatte başarısız olan yedeklerin (özellikle merkezi/otomatik) günlük özet
 * bildirimi — HostVim ekibine gider.
 */
class ManagedBackupFailureDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{domain: string, type: string, when: string}>  $failures
     */
    public function __construct(
        public array $failures,
        public int $totalCount,
        public string $panelName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('[%s] Yedekleme uyarısı: %d başarısız yedek (son 24 saat)', $this->panelName, $this->totalCount),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.backups.failure_digest', with: [
            'failures' => $this->failures,
            'totalCount' => $this->totalCount,
            'panelName' => $this->panelName,
        ]);
    }
}
