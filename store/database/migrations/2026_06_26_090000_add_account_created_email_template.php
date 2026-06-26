<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Misafir checkout sonrasi otomatik olusturulan musteri hesabi icin
 * "hesabiniz olusturuldu + sifrenizi belirleyin" e-posta sablonu.
 */
return new class extends Migration
{
    public function up(): void
    {
        $button = static fn (string $url, string $label): string => '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;"><tr><td align="center" style="border-radius:10px;background:{primary_color};"><a href="'.$url.'" target="_blank" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">'.$label.'</a></td></tr></table>';

        EmailTemplate::query()->updateOrCreate(
            ['slug' => 'account-created'],
            [
                'name' => 'Hesap Oluşturuldu (Sipariş)',
                'subject' => '{site_name} — Hesabınız oluşturuldu, şifrenizi belirleyin',
                'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Merhaba {customer_name},</p>'
                    .'<p style="margin:0 0 16px;">Siparişiniz için teşekkürler! Siparişlerinizi, hizmetlerinizi ve alan adlarınızı tek yerden yönetebilmeniz için sizin adınıza bir hesap oluşturduk.</p>'
                    .'<p style="margin:0 0 16px;">Hesabınızı kullanmaya başlamak için aşağıdaki butona tıklayarak şifrenizi belirleyin:</p>'
                    .$button('{set_password_url}', 'Şifremi belirle')
                    .'<p style="margin:0 0 8px;font-size:14px;color:#64748b;">Buton çalışmıyorsa bu adresi tarayıcınıza yapıştırın:</p>'
                    .'<p style="margin:0 0 16px;font-size:13px;word-break:break-all;color:#475569;">{set_password_url}</p>'
                    .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;"><tr><td style="padding:16px 20px;"><p style="margin:0;font-size:14px;color:#64748b;">Giriş e-posta adresiniz</p><p style="margin:4px 0 0;font-size:16px;font-weight:700;color:#0f172a;">{customer_email}</p></td></tr></table>'
                    .'<p style="margin:16px 0 0;font-size:14px;color:#64748b;">Bu bağlantı güvenliğiniz için <strong>{expire_minutes} dakika</strong> geçerlidir. Süre dolarsa giriş sayfasındaki "Şifremi unuttum" adımıyla yeni bağlantı alabilirsiniz.</p>',
                'variables' => ['customer_name', 'customer_email', 'set_password_url', 'login_url', 'account_url', 'expire_minutes', 'site_name', 'primary_color', 'support_email'],
                'is_active' => true,
            ],
        );
    }

    public function down(): void
    {
        EmailTemplate::query()->where('slug', 'account-created')->delete();
    }
};
