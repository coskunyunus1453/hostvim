@component('mail::message')
# Hizmetiniz Askıya Alındı

Merhaba {{ $subscription->user->name ?? '' }},

Ödenmemiş fatura nedeniyle aşağıdaki hosting hizmetiniz askıya alınmıştır.

@component('mail::panel')
**Paket:** {{ $subscription->hostingPackage->name ?? '-' }}<br>
@if($subscription->domain)**Alan adı:** {{ $subscription->domain->name }}@endif
@endcomponent

Hizmetinizi yeniden etkinleştirmek için lütfen ödemenizi tamamlayın. Ödeme sonrası hizmetiniz otomatik olarak açılacaktır.

@component('mail::button', ['url' => rtrim(config('app.url'), '/').'/billing'])
Faturalarımı Görüntüle
@endcomponent

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
