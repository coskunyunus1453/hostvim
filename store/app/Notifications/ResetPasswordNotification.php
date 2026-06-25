<?php

namespace App\Notifications;

use App\Mail\TemplatedMail;
use App\Services\TemplatedMailService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ResetPasswordNotification extends Notification
{
    public function __construct(
        public string $token,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): TemplatedMail
    {
        $email = $notifiable->getEmailForPasswordReset();
        $resetUrl = URL::route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ]);

        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        $rendered = app(TemplatedMailService::class)->render('password-reset', [
            'customer_name' => $notifiable->name ?: 'Müşteri',
            'reset_url' => $resetUrl,
            'expire_minutes' => (string) $expire,
            'site_name' => (string) config('app.name', 'HostVim'),
        ]);

        if ($rendered !== null) {
            return (new TemplatedMail($rendered['subject'], $rendered['body']))
                ->to($email);
        }

        $body = '<p>Merhaba '.e($notifiable->name).',</p>'
            .'<p>Şifre sıfırlama talebinizi aldık. Aşağıdaki bağlantı '.$expire.' dakika geçerlidir:</p>'
            .'<p><a href="'.e($resetUrl).'">Şifremi sıfırla</a></p>'
            .'<p>Bu talebi siz yapmadıysanız bu e-postayı yok sayın.</p>';

        return (new TemplatedMail('Şifre sıfırlama — '.config('app.name'), $body))
            ->to($email);
    }
}
