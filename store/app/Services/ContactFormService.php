<?php

namespace App\Services;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Mail\TemplatedMail;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactFormService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly TemplatedMailService $mail,
    ) {}

    /** @return list<string, string> */
    public function subjectOptions(): array
    {
        return [
            'genel' => 'Genel Bilgi',
            'destek' => 'Teknik Destek',
            'satis' => 'Satış & Teklif',
            'hosting' => 'Web Hosting',
            'vds' => 'VDS Sunucu',
            'dedicated' => 'Dedicated Sunucu',
            'domain' => 'Domain & Transfer',
            'fatura' => 'Fatura & Ödeme',
            'diger' => 'Diğer',
        ];
    }

    public function store(array $data): ContactMessage
    {
        $message = ContactMessage::create($data);
        $this->notifyAdmin($message);

        return $message;
    }

    private function notifyAdmin(ContactMessage $message): void
    {
        $to = trim((string) $this->settings->get('contact_email', ''));
        if ($to === '') {
            return;
        }

        $adminUrl = url(ContactMessageResource::getUrl('edit', ['record' => $message]));

        $sent = $this->mail->send('contact-form-admin', $to, [
            'sender_name' => $message->name,
            'sender_email' => $message->email,
            'sender_phone' => $message->phone ?: '—',
            'subject' => $message->subject ?: '—',
            'message_body' => nl2br(e($message->message)),
            'admin_url' => $adminUrl,
        ]);

        if ($sent) {
            return;
        }

        try {
            Mail::to($to)->queue(new TemplatedMail(
                'Yeni iletişim mesajı: '.($message->subject ?: $message->name),
                '<p><strong>Gönderen:</strong> '.e($message->name).' ('.e($message->email).')</p>'
                .'<p><strong>Telefon:</strong> '.e($message->phone ?: '—').'</p>'
                .'<p><strong>Konu:</strong> '.e($message->subject ?: '—').'</p>'
                .'<p><strong>Mesaj:</strong></p><p>'.nl2br(e($message->message)).'</p>'
                .'<p><a href="'.e($adminUrl).'">Admin panelde görüntüle</a></p>',
            ));
        } catch (\Throwable $e) {
            Log::warning('Contact form admin mail failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
