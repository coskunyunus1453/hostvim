@component('mail::message')
# Siteleriniz Askıya Alındı

Merhaba {{ $customerName }},

Hesabınızdaki dosya/dizin (inode) sayısı paket sınırını aşmaya devam ettiği için aşağıdaki siteleriniz **askıya alınmıştır**.

@component('mail::panel')
**Kullanılan:** {{ number_format($used, 0, ',', '.') }} dosya<br>
**Paket sınırı:** {{ number_format($limit, 0, ',', '.') }} dosya
@endcomponent

@if(!empty($domains))
**Askıya alınan siteler:**
@foreach($domains as $d)
- {{ $d }}
@endforeach
@endif

Sitelerinizi yeniden açmak için lütfen dosya sayınızı paket sınırının altına indirin (gereksiz dosya, log, önbellek, yedek vb. temizleyin). Sayı sınır altına indiğinde siteleriniz **otomatik olarak yeniden aktifleşecektir**.

@component('mail::button', ['url' => rtrim(config('app.url'), '/').'/files'])
Dosya Yöneticisini Aç
@endcomponent

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
