@component('mail::message')
# Ödemeniz Alındı

Merhaba {{ $invoice->user->name ?? '' }},

**{{ $invoice->number }}** numaralı faturanızın ödemesi başarıyla alındı. Teşekkür ederiz.

@component('mail::panel')
**Tutar:** {{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}<br>
**Ödeme tarihi:** {{ optional($invoice->paid_at)->format('d.m.Y H:i') }}<br>
@if($invoice->payment_method)**Yöntem:** {{ $invoice->payment_method }}@endif
@endcomponent

Hizmetiniz otomatik olarak etkinleştirildi/yenilendi.

@component('mail::button', ['url' => rtrim(config('app.url'), '/').'/billing'])
Panele Git
@endcomponent

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
