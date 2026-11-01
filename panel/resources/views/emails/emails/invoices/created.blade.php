@component('mail::message')
# Yeni Faturanız

Merhaba {{ $invoice->user->name ?? '' }},

**{{ $invoice->number }}** numaralı faturanız oluşturuldu.

@component('mail::table')
| Açıklama | Tutar |
|:---------|------:|
@foreach($invoice->items as $item)
| {{ $item->description }} | {{ number_format((float) $item->amount, 2) }} {{ $invoice->currency }} |
@endforeach
| **Ara toplam** | {{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }} |
@if((float) $invoice->tax_amount > 0)
| **KDV (%{{ rtrim(rtrim(number_format((float) $invoice->tax_rate, 2), '0'), '.') }})** | {{ number_format((float) $invoice->tax_amount, 2) }} {{ $invoice->currency }} |
@endif
| **Genel Toplam** | **{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}** |
@endcomponent

@if($invoice->due_at)
**Son ödeme tarihi:** {{ $invoice->due_at->format('d.m.Y') }}
@endif

@component('mail::button', ['url' => rtrim(config('app.url'), '/').'/billing/invoices/'.$invoice->id])
Faturayı Görüntüle ve Öde
@endcomponent

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
