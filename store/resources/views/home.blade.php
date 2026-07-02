@extends('layouts.app')

@section('content')
@include('partials.hero.index', ['hero' => $hero])

{{-- Domain arama bandı --}}
<section class="relative z-10 -mt-px border-y border-hv-border bg-hv-gradient-r">
    <div class="mx-auto max-w-5xl px-4 py-8 lg:px-8 lg:py-10">
        <div class="flex flex-col items-center gap-4 text-center lg:flex-row lg:gap-8 lg:text-left">
            <div class="shrink-0">
                <h2 class="text-xl font-extrabold text-white lg:text-2xl">Alan adını hemen bul</h2>
                <p class="mt-1 text-sm text-white/80">Yeni kayıt, transfer & yenileme fiyatlarıyla.</p>
            </div>
            <form action="{{ route('domain.index') }}" method="GET" class="flex w-full flex-1 flex-col gap-2 sm:flex-row">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-hv-muted">🔍</span>
                    <input type="text" name="q" placeholder="markanizi-yazin.com" required autocomplete="off"
                        class="w-full rounded-2xl border-0 bg-white py-3.5 pl-11 pr-4 text-base text-hv-text shadow-lg outline-none ring-2 ring-transparent focus:ring-white/60">
                </div>
                <button type="submit" class="rounded-2xl bg-hv-text px-7 py-3.5 text-base font-bold text-white shadow-lg transition hover:opacity-90">
                    Sorgula
                </button>
            </form>
        </div>
    </div>
</section>

{{-- Categories / Pricing preview --}}
@foreach($categories as $category)
<section class="{{ $loop->even ? 'bg-hv-surface' : 'bg-hv-bg' }} py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-end">
            <div>
                <span class="text-sm font-bold uppercase tracking-wider text-hv-primary">{{ $category->name }}</span>
                <h2 class="section-title mt-2">{{ $category->description ? Str::limit($category->description, 60) : $category->name . ' Paketleri' }}</h2>
            </div>
            <a href="{{ route('products.category', $category->slug) }}" class="btn-ghost font-semibold text-hv-secondary">
                Tümünü Gör →
            </a>
        </div>
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($category->activeProducts as $product)
                @include('partials.pricing-card', ['product' => $product, 'category' => $category])
            @endforeach
        </div>
    </div>
</section>
@endforeach

{{-- Features --}}
@if($features->isNotEmpty())
<section class="bg-gradient-to-b from-hv-bg to-hv-surface py-20">
    <div class="mx-auto max-w-7xl px-4 text-center lg:px-8">
        <h2 class="section-title">Neden {{ $siteName }}?</h2>
        <p class="section-subtitle mx-auto">Kurumsal altyapı, şeffaf fiyatlandırma ve gerçek insan desteği.</p>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($features as $feature)
                <div class="card text-left">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-hv-primary/10 text-2xl">⚡</div>
                    <h3 class="text-lg font-bold text-hv-text">{{ $feature->title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-hv-muted">{{ $feature->description }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Testimonials --}}
@if($testimonials->isNotEmpty())
<section class="py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <h2 class="section-title text-center">Müşterilerimiz Ne Diyor?</h2>
        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($testimonials as $t)
                <blockquote class="card">
                    <div class="mb-3 flex text-hv-primary">
                        @for($i = 0; $i < $t->rating; $i++) ★ @endfor
                    </div>
                    <p class="text-sm leading-relaxed text-hv-muted">"{{ $t->content }}"</p>
                    <footer class="mt-4 border-t border-hv-border pt-4">
                        <cite class="not-italic font-semibold text-hv-text">{{ $t->name }}</cite>
                        @if($t->company)<span class="block text-xs text-hv-muted">{{ $t->role }} — {{ $t->company }}</span>@endif
                    </footer>
                </blockquote>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- FAQ --}}
@if($faqs->isNotEmpty())
<section class="bg-hv-secondary/5 py-20">
    <div class="mx-auto max-w-3xl px-4 lg:px-8">
        <h2 class="section-title text-center">Sık Sorulan Sorular</h2>
        <div class="mt-10 space-y-4">
            @foreach($faqs as $faq)
                <details class="group rounded-xl border border-hv-border bg-hv-elevated">
                    <summary class="cursor-pointer list-none px-6 py-4 font-semibold text-hv-text marker:content-none flex justify-between items-center">
                        {{ $faq->question }}
                        <span class="text-hv-primary transition group-open:rotate-45">+</span>
                    </summary>
                    <div class="border-t border-hv-border px-6 pb-4 pt-2 text-sm leading-relaxed text-hv-muted">{!! nl2br(e($faq->answer)) !!}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Blog --}}
@if($posts->isNotEmpty())
<section class="py-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <div class="flex items-end justify-between">
            <h2 class="section-title">Blog & Rehberler</h2>
            <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-hv-primary hover:underline">Tüm Yazılar →</a>
        </div>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach($posts as $post)
                <a href="{{ route('blog.show', $post->slug) }}" class="card group block overflow-hidden p-0">
                    @if($post->featured_image)
                        <div class="aspect-video overflow-hidden">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image) }}"
                                 alt="{{ $post->title }}" loading="lazy"
                                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        </div>
                    @else
                        <div class="aspect-video bg-gradient-to-br from-hv-primary/20 to-hv-secondary/20"></div>
                    @endif
                    <div class="p-6">
                        @if($post->category)<span class="text-xs font-bold uppercase text-hv-secondary">{{ $post->category->name }}</span>@endif
                        <h3 class="mt-2 font-bold text-hv-text group-hover:text-hv-primary">{{ $post->title }}</h3>
                        <p class="mt-2 text-sm text-hv-muted">{{ Str::limit($post->excerpt ?? strip_tags($post->content), 100) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CTA --}}
<section class="mx-4 mb-20 lg:mx-8">
    <div class="mx-auto max-w-7xl overflow-hidden rounded-3xl bg-hv-gradient-r px-8 py-16 text-center text-white shadow-xl lg:px-16">
        <h2 class="text-3xl font-bold md:text-4xl">Projenize bugün başlayın</h2>
        <p class="mx-auto mt-4 max-w-xl text-white/80">Doğru paketi seçin, dakikalar içinde online olun. Taşıma ve kurulum desteği ücretsiz.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            <a href="{{ route('products.index') }}" class="rounded-xl bg-white px-8 py-3 font-semibold text-hv-primary shadow-lg hover:bg-white/90">Paket Seç</a>
            <a href="{{ route('contact.index') }}" class="rounded-xl border-2 border-white/40 px-8 py-3 font-semibold text-white hover:bg-white/10">Destek Al</a>
        </div>
    </div>
</section>
@endsection
