<p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#0f172a;">
    {{ $forStaff ? 'Destek Talebi Güncellendi' : 'Destek Talebinize Yanıt' }}
</p>

@if($forStaff)
<p style="margin:0 0 12px;color:#334155;"><strong>{{ $ticket->number }}</strong> numaralı talepte yeni mesaj var.</p>
@else
<p style="margin:0 0 12px;color:#334155;">Merhaba {{ $ticket->user->name ?? '' }}, <strong>{{ $ticket->number }}</strong> numaralı destek talebinize yanıt verildi.</p>
@endif

<p style="margin:0 0 8px;color:#334155;"><strong>Konu:</strong> {{ $ticket->subject }}</p>

@if($message)
<div style="margin:16px 0;padding:16px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;color:#334155;white-space:pre-wrap;">{{ Str::limit($message->body, 2000) }}</div>
@endif

<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;">
    <tr>
        <td align="center" style="border-radius:10px;background:{{ $primaryColor }};">
            <a href="{{ $ticketUrl }}" target="_blank" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">Talebi Görüntüle</a>
        </td>
    </tr>
</table>
