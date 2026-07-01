@extends('layouts.app')

@section('content')
<section class="py-16">
    <div class="mx-auto grid max-w-5xl gap-12 px-4 lg:grid-cols-2 lg:px-8">
        <div>
            <h1 class="text-4xl font-extrabold text-hv-text">Bize Ulaşın</h1>
            <p class="mt-4 text-hv-muted">Sorularınız için formu doldurun veya doğrudan arayın.</p>
            <div class="mt-8 space-y-4 text-hv-muted">
                @if($siteSettings['contact_phone'] ?? null)<p>📞 {{ $siteSettings['contact_phone'] }}</p>@endif
                @if($siteSettings['contact_email'] ?? null)<p>✉️ {{ $siteSettings['contact_email'] }}</p>@endif
            </div>
        </div>
        <form action="{{ route('contact.store') }}" method="POST" class="card space-y-4">
            @csrf
            <div class="hidden" aria-hidden="true">
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </div>
            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <input type="text" name="name" placeholder="Ad Soyad *" required class="w-full rounded-xl border border-hv-border px-4 py-3">
            <input type="email" name="email" placeholder="E-posta *" required class="w-full rounded-xl border border-hv-border px-4 py-3">
            <input type="text" name="phone" placeholder="Telefon" class="w-full rounded-xl border border-hv-border px-4 py-3">
            <input type="text" name="subject" placeholder="Konu" value="{{ old('subject', $prefillSubject ?? '') }}" class="w-full rounded-xl border border-hv-border px-4 py-3">
            <textarea name="message" placeholder="Mesajınız *" required rows="5" class="w-full rounded-xl border border-hv-border px-4 py-3">{{ old('message', $prefillMessage ?? '') }}</textarea>
            <x-captcha context="contact" />
            <button type="submit" class="btn-primary w-full">Gönder</button>
        </form>
    </div>
</section>
@endsection
