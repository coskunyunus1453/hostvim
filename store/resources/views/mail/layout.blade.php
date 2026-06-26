@php
    /** @var array{site_name:string,site_url:string,support_email:string,primary_color:string,secondary_color:string,logo_url:?string,show_logo:bool,year:string} $branding */
    $primary = $branding['primary_color'] ?? '#C2410C';
    $siteName = $branding['site_name'] ?? config('app.name');
    $siteUrl = $branding['site_url'] ?? config('app.url');
    $supportEmail = $branding['support_email'] ?? '';
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $siteName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#0f172a;-webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f1f5f9;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;">
                {{-- Header --}}
                <tr>
                    <td align="center" style="padding:8px 0 24px;">
                        <a href="{{ $siteUrl }}" style="text-decoration:none;display:inline-block;">
                            @if(!empty($branding['show_logo']) && !empty($branding['logo_url']))
                                <img src="{{ $branding['logo_url'] }}" alt="{{ $siteName }}" width="160" style="display:block;max-width:160px;height:auto;border:0;outline:none;">
                            @else
                                <span style="display:inline-block;font-size:22px;font-weight:800;letter-spacing:-0.02em;color:{{ $primary }};">{{ $siteName }}</span>
                            @endif
                        </a>
                    </td>
                </tr>

                {{-- Card --}}
                <tr>
                    <td style="background:#ffffff;border-radius:16px;border:1px solid #e2e8f0;box-shadow:0 4px 24px rgba(15,23,42,0.06);overflow:hidden;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="height:4px;background:linear-gradient(90deg, {{ $primary }}, {{ $branding['secondary_color'] ?? '#0F766E' }});font-size:0;line-height:0;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="padding:32px 28px 28px;font-size:16px;line-height:1.65;color:#334155;">
                                    {!! $content !!}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding:24px 12px 8px;text-align:center;font-size:13px;line-height:1.6;color:#64748b;">
                        <p style="margin:0 0 8px;font-weight:600;color:#475569;">{{ $siteName }}</p>
                        <p style="margin:0 0 8px;">
                            <a href="{{ $siteUrl }}" style="color:{{ $primary }};text-decoration:none;">{{ parse_url($siteUrl, PHP_URL_HOST) ?: $siteUrl }}</a>
                        </p>
                        @if($supportEmail !== '')
                            <p style="margin:0 0 8px;">
                                Destek:
                                <a href="mailto:{{ $supportEmail }}" style="color:{{ $primary }};text-decoration:none;">{{ $supportEmail }}</a>
                            </p>
                        @endif
                        <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;">
                            Bu e-posta {{ $siteName }} hesabınız veya siparişinizle ilgili bilgilendirme amacıyla gönderilmiştir.
                            @if($supportEmail !== '')
                                Yanlışlıkla aldıysanız lütfen <a href="mailto:{{ $supportEmail }}" style="color:#94a3b8;text-decoration:underline;">bize bildirin</a>.
                            @endif
                        </p>
                        <p style="margin:12px 0 0;font-size:12px;color:#cbd5e1;">© {{ $branding['year'] ?? date('Y') }} {{ $siteName }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
