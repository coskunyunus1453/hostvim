@component('mail::message')
# Hosting Hizmetiniz Aktif

Merhaba {{ $subscription->user->name ?? '' }},

Hosting hizmetiniz başarıyla kuruldu ve kullanıma hazır.

@component('mail::panel')
**Paket:** {{ $subscription->hostingPackage->name ?? '-' }}<br>
@if($subscription->domain)**Alan adı:** {{ $subscription->domain->name }}<br>@endif
**Sonraki ödeme:** {{ optional($subscription->next_due_at)->format('d.m.Y') }}
@endcomponent

@component('mail::button', ['url' => rtrim(config('app.url'), '/')])
Kontrol Paneline Git
@endcomponent

İyi günler dileriz,<br>
{{ config('app.name') }}
@endcomponent
