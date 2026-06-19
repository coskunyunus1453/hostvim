<?php

namespace App\Mail;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportTicketReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public ?SupportTicketMessage $message,
        public bool $forStaff = false,
    ) {}

    public function envelope(): Envelope
    {
        $prefix = $this->forStaff ? '[Destek] ' : 'Destek talebiniz güncellendi: ';

        return new Envelope(subject: $prefix.$this->ticket->subject.' ('.$this->ticket->number.')');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.support.reply', with: [
            'ticket' => $this->ticket,
            'message' => $this->message,
            'forStaff' => $this->forStaff,
        ]);
    }
}
