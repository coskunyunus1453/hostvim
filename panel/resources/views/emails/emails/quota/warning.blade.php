@component('mail::message')
# Disk Kullanım Uyarısı

Merhaba {{ $customerName }},

@if($over)
Hosting hesabınızın disk kullanımı **paket sınırını aştı**. Lütfen kısa süre içinde gereksiz dosyaları temizleyerek yer açın.
@else
Hosting hesabınızın disk kullanımı **paket sınırına yaklaştı**. Sınırı aşmamak için gereksiz dosyaları temizlemenizi öneririz.
@endif

@component('mail::panel')
**Kullanılan:** {{ number_format($usedMb, 1, ',', '.') }} MB<br>
**Paket sınırı:** {{ number_format($limitMb, 1, ',', '.') }} MB<br>
**Doluluk:** %{{ number_format($percent, 1, ',', '.') }}
@endcomponent

@if($over)
@if($remainingGraceDays > 0)
Sınır **{{ $remainingGraceDays }} gün** içinde altına indirilmezse siteleriniz otomatik olarak **askıya alınacaktır**. Askıya alınan siteler, yer açtığınızda otomatik olarak yeniden aktifleşir.
@else
Sınır bugün itibarıyla altına indirilmezse siteleriniz **askıya alınabilir**. Lütfen en kısa sürede yer açın.
@endif
@endif

@component('mail::button', ['url' => rtrim(config('app.url'), '/').'/files'])
Dosya Yöneticisini Aç
@endcomponent

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
