@extends('layouts.app')

@section('content')
@php
    $hero = $content['hero'] ?? [];
    $platform = $content['platform'] ?? [];
    $intro = $content['intro'] ?? [];
    $features = $content['features'] ?? [];
    $tech = $content['tech'] ?? [];
    $details = $content['details'] ?? [];
    $faqs = $content['faqs'] ?? [];
@endphp

{{-- ===== HERO ===== --}}
<section class="relative overflow-hidden bg-hv-gradient">
    <div class="pointer-events-none absolute inset-0 opacity-20"
         style="background-image:radial-gradient(circle at 20% 20%, #fff 1px, transparent 1px);background-size:32px 32px;"></div>
    <div class="pointer-events-none absolute -right-24 top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -left-16 bottom-0 h-48 w-48 rounded-full bg-white/5 blur-2xl"></div>
    <div class="relative mx-auto max-w-4xl px-4 py-16 text-center lg:px-8 lg:py-24">
        @if(!empty($hero['badge']))
            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold text-white backdrop-blur">
                {{ $hero['badge'] }}
            </span>
        @endif
        <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
            {{ $hero['title'] ?? $pageLabel }}
        </h1>
        @if(!empty($hero['subtitle']))
            <p class="mx-auto mt-4 max-w-2xl text-base text-white/85 lg:text-lg">{{ $hero['subtitle'] }}</p>
        @endif
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            @if(!empty($hero['primary_label']))
                <a href="{{ $hero['primary_url'] ?? '#paketler' }}"
                   class="rounded-2xl bg-hv-text px-8 py-3.5 text-base font-bold text-white shadow-lg transition hover:opacity-90">
                    {{ $hero['primary_label'] }}
                </a>
            @endif
            @if(!empty($hero['secondary_label']))
                <a href="{{ $hero['secondary_url'] ?? '#' }}"
                   class="rounded-2xl border-2 border-white/40 bg-white/10 px-8 py-3.5 text-base font-bold text-white backdrop-blur transition hover:bg-white/20">
                    {{ $hero['secondary_label'] }}
                </a>
            @endif
        </div>
    </div>
</section>

{{-- ===== PLATFORM / ALTYAPI ===== --}}
@if(!empty($platform))
<section class="border-b border-hv-border bg-hv-surface py-10">
    <div class="mx-auto max-w-6xl px-4 lg:px-8">
        <p class="text-center text-xs font-bold uppercase tracking-widest text-hv-muted">Kendi altyapımız</p>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($platform as $p)
                <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5 text-center transition hover:border-hv-primary/40 hover:shadow-md">
                    <div class="text-2xl">{{ $p['icon'] ?? '✓' }}</div>
                    <h3 class="mt-2 text-sm font-bold text-hv-text">{{ $p['title'] ?? '' }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-hv-muted">{{ $p['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===== PAKETLER ===== --}}
<section id="paketler" class="bg-hv-bg py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <div class="text-center">
            <h2 class="section-title">{{ $intro['title'] ?? ($pageLabel . ' Paketleri') }}</h2>
            @if(!empty($intro['text']))
                <p class="section-subtitle mx-auto">{{ $intro['text'] }}</p>
            @endif
        </div>

        @if($products->isNotEmpty())
            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($products as $product)
                    @include('partials.pricing-card', ['product' => $product, 'category' => $product->category])
                @endforeach
            </div>
        @else
            <div class="mt-10 rounded-2xl border border-hv-border bg-hv-elevated p-10 text-center">
                <p class="text-hv-muted">Paketler çok yakında burada listelenecek.</p>
                <a href="{{ route('contact.index') }}" class="mt-4 inline-block rounded-xl bg-hv-primary px-6 py-3 text-sm font-bold text-white transition hover:opacity-90">Teklif Alın</a>
            </div>
        @endif
    </div>
</section>

{{-- ===== NEDEN HOSTVIM ===== --}}
@if(!empty($features))
<section class="border-t border-hv-border bg-hv-surface py-16 lg:py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <h2 class="section-title text-center">Neden {{ $siteName }} {{ $pageLabel }}?</h2>
        <p class="section-subtitle mx-auto text-center">İhtiyacınız olan her şey tek panelde; şeffaf fiyat, kurumsal altyapı ve gerçek insan desteği.</p>
        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($features as $f)
                <div class="group rounded-2xl border border-hv-border bg-hv-elevated p-6 transition hover:-translate-y-0.5 hover:border-hv-primary/30 hover:shadow-lg">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-hv-primary/10 text-xl transition group-hover:bg-hv-primary/20">{{ $f['icon'] ?? '✅' }}</div>
                    <h3 class="mt-4 font-bold text-hv-text">{{ $f['title'] ?? '' }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-hv-muted">{{ $f['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===== TEKNOLOJİ ===== --}}
@if(!empty($tech))
<section class="border-t border-hv-border bg-hv-bg py-16 lg:py-20">
    <div class="mx-auto max-w-5xl px-4 lg:px-8">
        <div class="text-center">
            <h2 class="section-title">Altyapı & Teknoloji</h2>
            <p class="section-subtitle mx-auto">{{ $pageLabel }} hizmetlerimizde kullandığımız kurumsal teknolojiler.</p>
        </div>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($tech as $t)
                <div class="rounded-2xl border border-hv-border border-l-4 border-l-hv-primary bg-hv-elevated p-6">
                    <h3 class="font-bold text-hv-text">{{ $t['title'] ?? '' }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-hv-muted">{{ $t['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===== HİZMET DETAYLARI (SEO) ===== --}}
@if(!empty($details))
<section class="border-t border-hv-border bg-hv-surface py-16 lg:py-20">
    <div class="mx-auto max-w-5xl px-4 lg:px-8">
        <h2 class="section-title text-center">Hizmet detayları</h2>
        <div class="mt-10 space-y-5">
            @foreach($details as $i => $d)
                <article class="overflow-hidden rounded-2xl border border-hv-border bg-hv-elevated p-6 lg:p-8">
                    <h3 class="text-lg font-bold text-hv-text lg:text-xl">{{ $d['title'] ?? '' }}</h3>
                    <div class="mt-3 space-y-3">
                        @foreach(preg_split('/\n\s*\n/', (string) ($d['body'] ?? '')) as $para)
                            @if(trim($para) !== '')
                                <p class="text-sm leading-relaxed text-hv-muted lg:text-base">{{ trim($para) }}</p>
                            @endif
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===== SIKÇA SORULAN SORULAR ===== --}}
@if(!empty($faqs))
<section class="border-t border-hv-border bg-hv-bg py-16 lg:py-20">
    <div class="mx-auto max-w-3xl px-4 lg:px-8">
        <div class="text-center">
            <h2 class="section-title">Sıkça sorulan sorular</h2>
            <p class="section-subtitle mx-auto">{{ $pageLabel }} hizmetimiz hakkında merak edilenler.</p>
        </div>
        <div class="mt-10 space-y-3">
            @foreach($faqs as $faq)
                <details class="group rounded-2xl border border-hv-border bg-hv-elevated p-5 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold text-hv-text">
                        <span>{{ $faq['q'] ?? '' }}</span>
                        <span class="shrink-0 text-hv-primary transition group-open:rotate-45">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-hv-muted">{{ $faq['a'] ?? '' }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===== CTA ===== --}}
<section class="mx-4 mb-20 mt-4 lg:mx-8">
    <div class="mx-auto max-w-7xl overflow-hidden rounded-3xl bg-gradient-to-r from-hv-primary to-hv-secondary px-8 py-14 text-center text-white shadow-xl lg:px-16">
        <h2 class="text-2xl font-bold md:text-3xl">{{ $pageLabel }} için doğru paket sizi bekliyor</h2>
        <p class="mx-auto mt-3 max-w-xl text-white/85">Dakikalar içinde başlayın. Taşıma ve kurulum desteği ücretsiz, memnun kalmazsanız iade garantili.</p>
        <div class="mt-7 flex flex-wrap justify-center gap-4">
            <a href="#paketler" class="rounded-xl bg-white px-8 py-3 font-semibold text-hv-primary shadow-lg hover:bg-white/90">Paket Seç</a>
            <a href="{{ route('contact.index') }}" class="rounded-xl border-2 border-white/40 px-8 py-3 font-semibold hover:bg-white/10">Destek Al</a>
        </div>
    </div>
</section>
@endsection
