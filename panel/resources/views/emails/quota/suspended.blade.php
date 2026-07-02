@component('mail::message')
# Siteleriniz Askıya Alındı

Merhaba {{ $customerName }},

Disk kullanımınız paket sınırını aşmaya devam ettiği için aşağıdaki siteleriniz **askıya alınmıştır**.

@component('mail::panel')
**Kullanılan:** {{ number_format($usedMb, 1, ',', '.') }} MB<br>
**Paket sınırı:** {{ number_format($limitMb, 1, ',', '.') }} MB
@endcomponent

@if(!empty($domains))
**Askıya alınan siteler:**
@foreach($domains as $d)
- {{ $d }}
@endforeach
@endif

Sitelerinizi yeniden açmak için lütfen disk kullanımınızı paket sınırının altına indirin (gereksiz dosya, log, yedek vb. temizleyin). Kullanım sınır altına indiğinde siteleriniz **otomatik olarak yeniden aktifleşecektir**.

@component('mail::button', ['url' => rtrim(config('app.url'), '/').'/files'])
Dosya Yöneticisini Aç
@endcomponent

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
