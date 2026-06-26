@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.').' '.$invoice->currency;
    $isDraft = $invoice->status === \App\Models\Invoice::STATUS_DRAFT;
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 12px; margin: 0; }
        .wrap { padding: 32px 36px; }
        .head { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; }
        .brand { font-size: 20px; font-weight: bold; color: #4f46e5; }
        .muted { color: #6b7280; }
        .doc-title { font-size: 18px; font-weight: bold; text-align: right; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-draft { background: #fef3c7; color: #92400e; }
        .badge-issued { background: #dcfce7; color: #166534; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 14px; }
        h3 { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; margin: 0 0 6px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.items th { background: #f3f4f6; text-align: left; padding: 8px 10px; font-size: 11px; border-bottom: 2px solid #e5e7eb; }
        table.items td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
        .right { text-align: right; }
        .totals { width: 45%; margin-left: 55%; margin-top: 14px; border-collapse: collapse; }
        .totals td { padding: 6px 10px; }
        .totals .grand { font-size: 14px; font-weight: bold; border-top: 2px solid #4f46e5; }
        .foot { margin-top: 30px; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 10px; }
        .uuid { font-size: 10px; color: #6b7280; word-break: break-all; }
    </style>
</head>
<body>
<div class="wrap">
    <table class="head">
        <tr>
            <td style="width: 55%;">
                <div class="brand">{{ $company['title'] ?: 'HostVim' }}</div>
                @if($company['address'])<div class="muted">{{ $company['address'] }}</div>@endif
                @if($company['tax_office'] || $company['tax_number'])
                    <div class="muted">VD: {{ $company['tax_office'] }} — VKN: {{ $company['tax_number'] }}</div>
                @endif
                @if($company['phone'])<div class="muted">Tel: {{ $company['phone'] }}</div>@endif
                @if($company['email'])<div class="muted">{{ $company['email'] }}</div>@endif
            </td>
            <td style="width: 45%;">
                <div class="doc-title">{{ \App\Models\Invoice::typeLabel($invoice->type) }}</div>
                <div class="right" style="margin-top:6px;">
                    @if($isDraft)
                        <span class="badge badge-draft">PROFORMA / TASLAK</span>
                    @else
                        <span class="badge badge-issued">{{ \App\Models\Invoice::statusLabel($invoice->status) }}</span>
                    @endif
                </div>
                <div class="right muted" style="margin-top:10px;">
                    <strong>Fatura No:</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Tarih:</strong> {{ ($invoice->issued_at ?? $invoice->created_at ?? now())->format('d.m.Y') }}<br>
                    @if($invoice->order)<strong>Sipariş:</strong> {{ $invoice->order->order_number }}@endif
                </div>
                @if($invoice->provider_uuid)
                    <div class="right uuid" style="margin-top:8px;">ETTN: {{ $invoice->provider_uuid }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div style="margin-top: 22px;" class="box">
        <h3>Sayın Müşterimiz</h3>
        <strong>{{ $invoice->customer_name ?: '—' }}</strong><br>
        @if($invoice->customer_address)<span class="muted">{{ $invoice->customer_address }}</span><br>@endif
        @if($invoice->customer_tax_number)<span class="muted">VD: {{ $invoice->customer_tax_office }} — VKN/TCKN: {{ $invoice->customer_tax_number }}</span><br>@endif
        @if($invoice->customer_email)<span class="muted">{{ $invoice->customer_email }}</span>@endif
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Açıklama</th>
                <th class="right">Miktar</th>
                <th class="right">Birim Fiyat</th>
                <th class="right">KDV</th>
                <th class="right">Tutar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $i => $line)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line['name'] }}</td>
                    <td class="right">{{ $line['quantity'] }}</td>
                    <td class="right">{{ $fmt($line['unit_price']) }}</td>
                    <td class="right">%{{ rtrim(rtrim(number_format((float)($line['tax_rate'] ?? $invoice->tax_rate), 2, ',', '.'), '0'), ',') }}</td>
                    <td class="right">{{ $fmt($line['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Ara Toplam (Matrah)</td><td class="right">{{ $fmt($invoice->subtotal) }}</td></tr>
        <tr><td class="muted">KDV (%{{ rtrim(rtrim(number_format((float)$invoice->tax_rate, 2, ',', '.'), '0'), ',') }})</td><td class="right">{{ $fmt($invoice->tax_total) }}</td></tr>
        <tr class="grand"><td>Genel Toplam</td><td class="right">{{ $fmt($invoice->total) }}</td></tr>
    </table>

    <div class="foot">
        @if($isDraft)
            Bu belge bir proforma/taslaktır ve resmi mali belge yerine geçmez. Resmi e-Fatura/e-Arşiv belgeniz düzenlendiğinde tarafınıza iletilecektir.
        @else
            Bu belge {{ \App\Models\Invoice::typeLabel($invoice->type) }} olarak elektronik ortamda düzenlenmiştir.
        @endif
        <br>{{ $company['title'] ?: 'HostVim' }} — Otomatik oluşturulmuştur.
    </div>
</div>
</body>
</html>
