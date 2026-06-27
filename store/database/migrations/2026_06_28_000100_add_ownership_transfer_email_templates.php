<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Hesaplar arası domain/hosting devri için e-posta şablonları:
 *  - talep alındı (müşteri)
 *  - devir onaylandı (eski + yeni sahip)
 *  - devir reddedildi (müşteri)
 */
return new class extends Migration
{
    public function up(): void
    {
        $vars = [
            'customer_name', 'subject_domain', 'transfer_type_label', 'target_email',
            'source_email', 'reason', 'site_name', 'account_url', 'support_email',
        ];

        EmailTemplate::query()->updateOrCreate(
            ['slug' => 'ownership-transfer-requested'],
            [
                'name' => 'Devir Talebi Alındı',
                'subject' => '{site_name} — Devir talebiniz alındı ({subject_domain})',
                'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Merhaba {customer_name},</p>'
                    .'<p style="margin:0 0 16px;"><strong>{subject_domain}</strong> için {transfer_type_label} devir talebiniz alındı. Talep, <strong>{target_email}</strong> adresine devredilmek üzere ekibimizin onayına gönderildi.</p>'
                    .'<p style="margin:0 0 16px;">Onaylandığında hem siz hem de yeni sahip e-posta ile bilgilendirileceksiniz. Bu süreçte talebi hesabınızdan iptal edebilirsiniz.</p>'
                    .'<p style="margin:16px 0 0;font-size:14px;color:#64748b;">Bu işlemi siz başlatmadıysanız lütfen {support_email} ile iletişime geçin.</p>',
                'variables' => $vars,
                'is_active' => true,
            ],
        );

        EmailTemplate::query()->updateOrCreate(
            ['slug' => 'ownership-transfer-approved'],
            [
                'name' => 'Devir Onaylandı',
                'subject' => '{site_name} — Devir tamamlandı ({subject_domain})',
                'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Merhaba {customer_name},</p>'
                    .'<p style="margin:0 0 16px;"><strong>{subject_domain}</strong> için {transfer_type_label} devri tamamlandı. Varlık artık <strong>{target_email}</strong> hesabına aittir.</p>'
                    .'<p style="margin:0 0 16px;">Yeni sahip, hesabına giriş yaparak yönetebilir.</p>'
                    .'<p style="margin:16px 0 0;font-size:14px;color:#64748b;">Sorularınız için {support_email} ile iletişime geçebilirsiniz.</p>',
                'variables' => $vars,
                'is_active' => true,
            ],
        );

        EmailTemplate::query()->updateOrCreate(
            ['slug' => 'ownership-transfer-rejected'],
            [
                'name' => 'Devir Reddedildi',
                'subject' => '{site_name} — Devir talebiniz reddedildi ({subject_domain})',
                'body' => '<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">Merhaba {customer_name},</p>'
                    .'<p style="margin:0 0 16px;"><strong>{subject_domain}</strong> için {transfer_type_label} devir talebiniz onaylanmadı.</p>'
                    .'<p style="margin:0 0 16px;">Gerekçe: {reason}</p>'
                    .'<p style="margin:16px 0 0;font-size:14px;color:#64748b;">Sorularınız için {support_email} ile iletişime geçebilirsiniz.</p>',
                'variables' => $vars,
                'is_active' => true,
            ],
        );
    }

    public function down(): void
    {
        EmailTemplate::query()->whereIn('slug', [
            'ownership-transfer-requested',
            'ownership-transfer-approved',
            'ownership-transfer-rejected',
        ])->delete();
    }
};
