<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Mağaza e-posta şablonlarını modern, markalı layout ile uyumlu içerikle günceller.
 */
return new class extends Migration
{
    public function up(): void
    {
        $button = static fn (string $url, string $label): string => '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;"><tr><td align="center" style="border-radius:10px;background:{primary_color};"><a href="'.$url.'" target="_blank" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">'.$label.'</a></td></tr></table>';

        $templates = [
            'password-reset' => [
                'name' => 'Şifre Sıfırlama',
                'subject' => '{site_name} — Şifre sıfırlama',
                'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Merhaba {customer_name},</p>'
                    .'<p style="margin:0 0 16px;">Hesabınız için şifre sıfırlama talebi aldık. Güvenliğiniz için bağlantı <strong>{expire_minutes} dakika</strong> geçerlidir.</p>'
                    .$button('{reset_url}', 'Şifremi sıfırla')
                    .'<p style="margin:0 0 8px;font-size:14px;color:#64748b;">Buton çalışmıyorsa bu adresi tarayıcınıza yapıştırın:</p>'
                    .'<p style="margin:0;font-size:13px;word-break:break-all;color:#475569;">{reset_url}</p>'
                    .'<p style="margin:24px 0 0;font-size:14px;color:#64748b;">Bu talebi siz yapmadıysanız bu e-postayı yok sayabilirsiniz; hesabınız güvende kalır.</p>',
                'variables' => ['customer_name', 'reset_url', 'expire_minutes', 'site_name', 'primary_color'],
            ],
            'welcome' => [
                'name' => 'Hoş Geldiniz',
                'subject' => '{site_name} — Hesabınız hazır',
                'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Hoş geldiniz, {customer_name}!</p>'
                    .'<p style="margin:0 0 16px;">{site_name} ailesine katıldığınız için teşekkürler. Hesabınız başarıyla oluşturuldu; siparişlerinizi ve hizmetlerinizi tek yerden yönetebilirsiniz.</p>'
                    .$button('{account_url}', 'Hesabıma git')
                    .'<p style="margin:16px 0 0;font-size:14px;color:#64748b;">Sorularınız için <a href="mailto:{support_email}" style="color:{primary_color};text-decoration:none;">{support_email}</a> adresinden bize ulaşabilirsiniz.</p>',
                'variables' => ['customer_name', 'site_name', 'login_url', 'account_url', 'support_email', 'primary_color'],
            ],
            'order-confirmation' => [
                'name' => 'Sipariş Onayı',
                'subject' => 'Siparişiniz alındı — {order_number}',
                'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Sayın {customer_name},</p>'
                    .'<p style="margin:0 0 16px;"><strong>{order_number}</strong> numaralı siparişiniz başarıyla alındı.</p>'
                    .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;"><tr><td style="padding:16px 20px;"><p style="margin:0;font-size:14px;color:#64748b;">Toplam tutar</p><p style="margin:4px 0 0;font-size:22px;font-weight:800;color:#0f172a;">{total} TL</p></td></tr></table>'
                    .$button('{panel_login_url}', 'Panele giriş yap')
                    .'{temporary_password_line}'
                    .'<p style="margin:16px 0 0;font-size:14px;color:#64748b;">Kurulum tamamlandığında ayrıca bilgilendirileceksiniz.</p>',
                'variables' => ['customer_name', 'order_number', 'total', 'panel_login_url', 'temporary_password_line', 'panel_order_number', 'primary_color'],
            ],
            'bank-transfer-pending' => [
                'name' => 'Havale Talimatları',
                'subject' => 'Havale/EFT talimatları — {order_number}',
                'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Sayın {customer_name},</p>'
                    .'<p style="margin:0 0 16px;"><strong>{order_number}</strong> numaralı siparişiniz için ödeme bekleniyor.</p>'
                    .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;"><tr><td style="padding:16px 20px;"><p style="margin:0 0 8px;font-size:14px;color:#64748b;"><strong>Tutar:</strong> {total} {currency}</p><p style="margin:0;font-size:14px;color:#64748b;"><strong>Açıklama / referans:</strong> {payment_reference}</p></td></tr></table>'
                    .'<div style="margin:16px 0;padding:16px 20px;background:#fffbeb;border-radius:12px;border:1px solid #fde68a;font-size:14px;line-height:1.6;color:#78350f;">{bank_instructions}</div>'
                    .'<p style="margin:0;font-size:14px;color:#64748b;">Ödemeniz onaylandığında kurulum otomatik başlayacaktır.</p>',
                'variables' => ['customer_name', 'order_number', 'total', 'currency', 'bank_instructions', 'payment_reference'],
            ],
            'payment-received' => [
                'name' => 'Ödeme Alındı',
                'subject' => 'Ödemeniz alındı — {order_number}',
                'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Sayın {customer_name},</p>'
                    .'<p style="margin:0 0 16px;"><strong>{order_number}</strong> numaralı siparişiniz için <strong>{total} TL</strong> tutarındaki ödemeniz onaylandı.</p>'
                    .'<p style="margin:0;font-size:14px;color:#64748b;">Kurulum işlemi başlatıldı; tamamlandığında ayrıca bilgilendirileceksiniz.</p>',
                'variables' => ['customer_name', 'order_number', 'total'],
            ],
        ];

        foreach ($templates as $slug => $data) {
            EmailTemplate::query()->updateOrCreate(
                ['slug' => $slug],
                array_merge($data, ['is_active' => true]),
            );
        }
    }

    public function down(): void
    {
        //
    }
};
