@component('mail::message')
# {{ $forStaff ? 'Destek Talebi Güncellendi' : 'Destek Talebinize Yanıt' }}

@if($forStaff)
**{{ $ticket->number }}** numaralı talepte yeni müşteri mesajı var.
@else
Merhaba {{ $ticket->user->name ?? '' }}, **{{ $ticket->number }}** numaralı destek talebinize yanıt verildi.
@endif

**Konu:** {{ $ticket->subject }}

@if($message)
@component('mail::panel')
{{ \Illuminate\Support\Str::limit($message->body, 1000) }}
@endcomponent
@endif

@component('mail::button', ['url' => rtrim(config('app.url'), '/').'/support/'.$ticket->id])
Talebi Görüntüle
@endcomponent

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
