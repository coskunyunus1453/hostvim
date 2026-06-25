@php
    $url = $url ?? '#';
    $label = $label ?? 'Devam';
    $color = $color ?? ($branding['primary_color'] ?? '#C2410C');
@endphp
<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;">
    <tr>
        <td align="center" style="border-radius:10px;background:{{ $color }};">
            <a href="{{ $url }}" target="_blank" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;line-height:1.2;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
