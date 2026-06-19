<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice, public bool $overdue = false) {}

    public function envelope(): Envelope
    {
        $prefix = $this->overdue ? 'Gecikmiş ödeme hatırlatması' : 'Ödeme hatırlatması';

        return new Envelope(subject: $prefix.': '.$this->invoice->number);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.invoices.reminder', with: [
            'invoice' => $this->invoice,
            'overdue' => $this->overdue,
        ]);
    }
}
