@component('mail::message')
# Siteleriniz Yeniden Aktif

Merhaba {{ $customerName }},

Hesabınızdaki dosya/dizin (inode) sayısı paket sınırının altına indiği için askıya alınan siteleriniz **yeniden aktif** edilmiştir.

@if(!empty($domains))
**Yeniden açılan siteler:**
@foreach($domains as $d)
- {{ $d }}
@endforeach
@endif

Bilginize sunar, iyi çalışmalar dileriz.

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
