@extends('layouts.app')

@section('content')
@php
    $phone = $siteSettings['contact_phone'] ?? null;
    $email = $siteSettings['contact_email'] ?? null;
    $address = $siteSettings['contact_address'] ?? null;
    $whatsapp = $siteSettings['contact_whatsapp'] ?? null;
    $hours = $siteSettings['contact_hours'] ?? '7/24 Türkçe destek';
    $sent = session('contact_sent') || session('success');
@endphp

{{-- Hero --}}
<section class="relative overflow-hidden bg-hv-gradient">
    <div class="pointer-events-none absolute inset-0 opacity-20"
         style="background-image:radial-gradient(circle at 20% 20%, #fff 1px, transparent 1px);background-size:32px 32px;"></div>
    <div class="pointer-events-none absolute -left-16 top-10 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-10 bottom-0 h-56 w-56 rounded-full bg-white/10 blur-3xl"></div>
    <div class="relative mx-auto max-w-5xl px-4 py-14 text-center lg:px-8 lg:py-18">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold text-white backdrop-blur">
            📬 İletişim & Destek
        </span>
        <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
            {{ $pageTitle ?? 'Bize Ulaşın' }}
        </h1>
        <p class="mx-auto mt-4 max-w-2xl text-base text-white/85 sm:text-lg">
            {{ $pageSubtitle ?? 'Sorularınız için formu doldurun; ekibimiz en kısa sürede size döner.' }}
        </p>
    </div>
</section>

<section class="bg-hv-bg py-12 lg:py-16">
    <div class="mx-auto max-w-6xl px-4 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-5 lg:gap-12">

            {{-- İletişim kartları --}}
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-2xl border border-hv-border bg-hv-elevated p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-hv-text">Doğrudan ulaşın</h2>
                    <p class="mt-1 text-sm text-hv-muted">Acil durumlarda aşağıdaki kanalları kullanabilirsiniz.</p>
                    <ul class="mt-6 space-y-4">
                        @if($phone)
                            <li class="flex items-start gap-4">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-hv-primary/10 text-lg">📞</span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-hv-muted">Telefon</p>
                                    <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="mt-0.5 block font-semibold text-hv-text hover:text-hv-primary">{{ $phone }}</a>
                                </div>
                            </li>
                        @endif
                        @if($email)
                            <li class="flex items-start gap-4">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-hv-primary/10 text-lg">✉️</span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-hv-muted">E-posta</p>
                                    <a href="mailto:{{ $email }}" class="mt-0.5 block font-semibold text-hv-text hover:text-hv-primary">{{ $email }}</a>
                                </div>
                            </li>
                        @endif
                        @if($whatsapp)
                            <li class="flex items-start gap-4">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-hv-primary/10 text-lg">💬</span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-hv-muted">WhatsApp</p>
                                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}" target="_blank" rel="noopener" class="mt-0.5 block font-semibold text-hv-text hover:text-hv-primary">{{ $whatsapp }}</a>
                                </div>
                            </li>
                        @endif
                        @if($address)
                            <li class="flex items-start gap-4">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-hv-primary/10 text-lg">📍</span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-hv-muted">Adres</p>
                                    <p class="mt-0.5 font-medium text-hv-text">{{ $address }}</p>
                                </div>
                            </li>
                        @endif
                        <li class="flex items-start gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-hv-primary/10 text-lg">🕐</span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-hv-muted">Destek saatleri</p>
                                <p class="mt-0.5 font-medium text-hv-text">{{ $hours }}</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-hv-border bg-hv-surface p-5 text-sm text-hv-muted">
                    <p class="font-semibold text-hv-text">Müşteri paneli</p>
                    <p class="mt-1">Mevcut hizmetleriniz için
                        @auth
                            <a href="{{ route('account.support.index') }}" class="font-semibold text-hv-primary hover:underline">destek talebi</a>
                        @else
                            <a href="{{ route('login') }}" class="font-semibold text-hv-primary hover:underline">müşteri paneline giriş</a>
                        @endauth
                        yapabilirsiniz.</p>
                </div>
            </div>

            {{-- Form --}}
            <div class="lg:col-span-3">
                <div class="rounded-2xl border border-hv-border bg-hv-elevated p-6 shadow-sm sm:p-8" id="contact-form-card">
                    <h2 class="text-xl font-bold text-hv-text">Mesaj gönderin</h2>
                    <p class="mt-1 text-sm text-hv-muted">Zorunlu alanlar <span class="text-hv-primary">*</span> ile işaretlidir.</p>

                    @if($sent)
                        <div id="contact-success-banner" role="alert" class="mt-6 flex items-start gap-3 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 dark:border-green-800/40 dark:bg-green-950/30 dark:text-green-200">
                            <span class="text-xl leading-none">✅</span>
                            <div>
                                <p class="font-bold">Mesajınız başarıyla gönderildi!</p>
                                <p class="mt-1">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div role="alert" class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 dark:border-red-800/40 dark:bg-red-950/30 dark:text-red-200">
                            <p class="font-bold">Gönderim tamamlanamadı</p>
                            <ul class="mt-2 list-inside list-disc space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="contact-form" action="{{ route('contact.store') }}" method="POST" class="mt-6 space-y-5" @if($sent) hidden @endif>
                        @csrf
                        <div class="hidden" aria-hidden="true">
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-1">
                                <label for="contact-name" class="mb-1.5 block text-sm font-semibold text-hv-text">Ad Soyad <span class="text-hv-primary">*</span></label>
                                <input type="text" id="contact-name" name="name" value="{{ old('name') }}" required autocomplete="name"
                                    class="w-full rounded-xl border border-hv-border bg-hv-bg px-4 py-3 text-hv-text outline-none transition focus:border-hv-primary focus:ring-2 focus:ring-hv-primary/20">
                            </div>
                            <div class="sm:col-span-1">
                                <label for="contact-email" class="mb-1.5 block text-sm font-semibold text-hv-text">E-posta <span class="text-hv-primary">*</span></label>
                                <input type="email" id="contact-email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                    class="w-full rounded-xl border border-hv-border bg-hv-bg px-4 py-3 text-hv-text outline-none transition focus:border-hv-primary focus:ring-2 focus:ring-hv-primary/20">
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="contact-phone" class="mb-1.5 block text-sm font-semibold text-hv-text">Telefon</label>
                                <input type="tel" id="contact-phone" name="phone" value="{{ old('phone') }}" autocomplete="tel"
                                    placeholder="05xx xxx xx xx"
                                    class="w-full rounded-xl border border-hv-border bg-hv-bg px-4 py-3 text-hv-text outline-none transition focus:border-hv-primary focus:ring-2 focus:ring-hv-primary/20">
                            </div>
                            <div>
                                <label for="contact-topic" class="mb-1.5 block text-sm font-semibold text-hv-text">Konu kategorisi</label>
                                <select id="contact-topic" name="topic"
                                    class="w-full rounded-xl border border-hv-border bg-hv-bg px-4 py-3 text-hv-text outline-none transition focus:border-hv-primary focus:ring-2 focus:ring-hv-primary/20">
                                    <option value="">Seçiniz…</option>
                                    @foreach($subjectOptions ?? [] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('topic', $prefillTopic ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="contact-subject" class="mb-1.5 block text-sm font-semibold text-hv-text">Konu başlığı</label>
                            <input type="text" id="contact-subject" name="subject" value="{{ old('subject', $prefillSubject ?? '') }}"
                                placeholder="Örn: VDS teklif talebi"
                                class="w-full rounded-xl border border-hv-border bg-hv-bg px-4 py-3 text-hv-text outline-none transition focus:border-hv-primary focus:ring-2 focus:ring-hv-primary/20">
                        </div>

                        <div>
                            <label for="contact-message" class="mb-1.5 block text-sm font-semibold text-hv-text">Mesajınız <span class="text-hv-primary">*</span></label>
                            <textarea id="contact-message" name="message" required rows="6" placeholder="Talebinizi veya sorunuzu detaylı yazın…"
                                class="w-full resize-y rounded-xl border border-hv-border bg-hv-bg px-4 py-3 text-hv-text outline-none transition focus:border-hv-primary focus:ring-2 focus:ring-hv-primary/20">{{ old('message', $prefillMessage ?? '') }}</textarea>
                        </div>

                        <x-captcha context="contact" />

                        <button type="submit" id="contact-submit" class="btn-primary flex w-full items-center justify-center gap-2 py-3.5 text-base font-bold sm:w-auto sm:px-10">
                            <span data-label>Gönder</span>
                            <span data-spinner class="hidden h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        </button>
                    </form>

                    @if($sent)
                        <button type="button" id="contact-new-message" class="mt-6 text-sm font-semibold text-hv-primary hover:underline">
                            Yeni mesaj gönder
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('contact-form');
    const submit = document.getElementById('contact-submit');
    const newBtn = document.getElementById('contact-new-message');
    const successBanner = document.getElementById('contact-success-banner');

    if (successBanner) {
        successBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    form?.addEventListener('submit', () => {
        if (!submit) return;
        submit.disabled = true;
        submit.querySelector('[data-label]')?.classList.add('hidden');
        submit.querySelector('[data-spinner]')?.classList.remove('hidden');
    });

    newBtn?.addEventListener('click', () => {
        form?.removeAttribute('hidden');
        successBanner?.remove();
        newBtn.remove();
        form?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.getElementById('contact-name')?.focus();
    });
});
</script>
@endpush
@endsection
