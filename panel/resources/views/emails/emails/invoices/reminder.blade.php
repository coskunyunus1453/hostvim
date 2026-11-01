@component('mail::message')
# {{ $overdue ? 'Gecikmiş Ödeme Hatırlatması' : 'Ödeme Hatırlatması' }}

Merhaba {{ $invoice->user->name ?? '' }},

@if($overdue)
**{{ $invoice->number }}** numaralı faturanızın ödeme tarihi geçti. Hizmetinizin kesintiye uğramaması için lütfen en kısa sürede ödeme yapın.
@else
**{{ $invoice->number }}** numaralı faturanızın son ödeme tarihi yaklaşıyor.
@endif

@component('mail::panel')
**Tutar:** {{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}<br>
**Son ödeme tarihi:** {{ optional($invoice->due_at)->format('d.m.Y') }}
@endcomponent

@component('mail::button', ['url' => rtrim(config('app.url'), '/').'/billing/invoices/'.$invoice->id])
Şimdi Öde
@endcomponent

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
