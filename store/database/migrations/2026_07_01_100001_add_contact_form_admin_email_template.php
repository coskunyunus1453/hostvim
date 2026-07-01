<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        EmailTemplate::query()->updateOrCreate(
            ['slug' => 'contact-form-admin'],
            [
                'name' => 'İletişim Formu — Admin Bildirimi',
                'subject' => 'Yeni iletişim mesajı: {subject}',
                'body' => '<p>Yeni bir iletişim formu mesajı alındı.</p>'
                    .'<p><strong>Gönderen:</strong> {sender_name} ({sender_email})</p>'
                    .'<p><strong>Telefon:</strong> {sender_phone}</p>'
                    .'<p><strong>Konu:</strong> {subject}</p>'
                    .'<p><strong>Mesaj:</strong></p><p>{message_body}</p>'
                    .'<p><a href="{admin_url}">Admin panelde görüntüle</a></p>',
                'is_active' => true,
            ],
        );
    }

    public function down(): void
    {
        EmailTemplate::query()->where('slug', 'contact-form-admin')->delete();
    }
};
