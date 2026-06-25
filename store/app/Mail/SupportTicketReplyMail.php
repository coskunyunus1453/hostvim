<?php

namespace App\Mail;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\MailBrandingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

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
        $ticketUrl = $this->forStaff
            ? rtrim(config('app.url'), '/').'/admin/support-tickets/'.$this->ticket->id.'/edit'
            : route('account.support.show', $this->ticket);

        $branding = app(MailBrandingService::class)->context();

        $body = view('emails.support.reply', [
            'ticket' => $this->ticket,
            'message' => $this->message,
            'forStaff' => $this->forStaff,
            'ticketUrl' => $ticketUrl,
            'primaryColor' => $branding['primary_color'],
        ])->render();

        $html = app(MailBrandingService::class)->wrap($body);

        return new Content(htmlString: $html);
    }
}
