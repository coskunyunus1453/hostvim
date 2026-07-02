@component('mail::message')
# Yedekleme Uyarısı

Son 24 saatte **{{ $totalCount }}** yedekleme işlemi **başarısız** oldu.

@component('mail::table')
| Site | Tür | Zaman |
| :--- | :-- | :---- |
@foreach($failures as $f)
| {{ $f['domain'] }} | {{ $f['type'] }} | {{ $f['when'] }} |
@endforeach
@endcomponent

@if($totalCount > count($failures))
_...ve {{ $totalCount - count($failures) }} tane daha. Tüm liste için panele bakın._
@endif

@component('mail::button', ['url' => rtrim(config('app.url'), '/').'/backups'])
Yedekleme Panelini Aç
@endcomponent

Bu bildirim otomatik gönderildi.<br>
{{ $panelName }}
@endcomponent
