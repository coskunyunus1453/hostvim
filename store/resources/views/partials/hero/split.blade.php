<section class="relative overflow-hidden bg-gradient-to-br from-hv-surface via-hv-bg to-hv-surface">
    <div class="absolute inset-0 opacity-60 hv-hero-pattern"></div>
    <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 sm:gap-12 sm:py-20 lg:grid-cols-2 lg:gap-14 lg:px-8 lg:py-28">
        <div class="animate-fade-up order-2 lg:order-1">
            @if($hero?->subtitle)
                <span class="inline-flex items-center gap-2 rounded-full border border-hv-secondary/20 bg-hv-secondary/10 px-4 py-1.5 text-sm font-semibold text-hv-secondary">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-hv-secondary"></span>
                    {{ $hero->subtitle }}
                </span>
            @endif
            <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-hv-text md:text-5xl lg:text-6xl">
                {!! safe_html($hero?->title ?? 'İşinizi <span class="text-hv-primary">güçlü altyapı</span> ile büyütün') !!}
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-hv-muted">
                {{ $hero?->description ?? 'NVMe SSD hosting, yüksek performanslı VPS/VDS, dedicated sunucu ve domain hizmetleri.' }}
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ $hero?->cta_url ?? route('products.index') }}" class="btn-primary">
                    {{ $hero?->cta_text ?? 'Paketleri Keşfet' }}
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                <a href="{{ $hero?->secondary_cta_url ?? route('contact.index') }}" class="btn-secondary">
                    {{ $hero?->secondary_cta_text ?? 'Uzmanla Konuş' }}
                </a>
            </div>
            @include('partials.hero.stats')
        </div>

        <div class="order-1 lg:order-2">
            <div class="hv-hero-visual-panel hv-hero-slide-in rounded-3xl border border-hv-border bg-hv-elevated/80 p-4 shadow-2xl shadow-black/10 backdrop-blur-sm dark:shadow-black/40 sm:p-6">
                @include('partials.hero.visual', ['hero' => $hero, 'illustrationClass' => 'max-w-none'])
            </div>
        </div>
    </div>
</section>
