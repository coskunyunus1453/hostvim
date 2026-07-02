@component('mail::message')
# Dosya (inode) Kullanım Uyarısı

Merhaba {{ $customerName }},

@if($over)
Hosting hesabınızdaki toplam dosya/dizin (inode) sayısı **paket sınırını aştı**. Lütfen kısa süre içinde gereksiz dosyaları (önbellek, log, eski yedek, geçici dosyalar) temizleyin.
@else
Hosting hesabınızdaki toplam dosya/dizin (inode) sayısı **paket sınırına yaklaştı**. Sınırı aşmamak için gereksiz dosyaları temizlemenizi öneririz.
@endif

@component('mail::panel')
**Kullanılan:** {{ number_format($used, 0, ',', '.') }} dosya<br>
**Paket sınırı:** {{ number_format($limit, 0, ',', '.') }} dosya<br>
**Doluluk:** %{{ number_format($percent, 1, ',', '.') }}
@endcomponent

@if($over)
@if($remainingGraceDays > 0)
Sınır **{{ $remainingGraceDays }} gün** içinde altına indirilmezse siteleriniz otomatik olarak **askıya alınacaktır**. Askıya alınan siteler, sayı sınır altına indiğinde otomatik olarak yeniden aktifleşir.
@else
Sınır bugün itibarıyla altına indirilmezse siteleriniz **askıya alınabilir**. Lütfen en kısa sürede gereksiz dosyaları silin.
@endif
@endif

@component('mail::button', ['url' => rtrim(config('app.url'), '/').'/files'])
Dosya Yöneticisini Aç
@endcomponent

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
