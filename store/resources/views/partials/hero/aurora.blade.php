<section class="relative min-h-[85vh] overflow-hidden bg-hv-bg flex items-center">
    <div class="absolute inset-0 hv-aurora-bg"></div>
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--hv-primary)_0%,_transparent_50%)] opacity-20"></div>
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_left,_var(--hv-secondary)_0%,_transparent_50%)] opacity-15"></div>

    <div class="relative mx-auto grid w-full max-w-7xl items-center gap-10 px-4 py-16 sm:py-20 lg:grid-cols-12 lg:gap-12 lg:px-8 lg:py-28">
        <div class="lg:col-span-6 animate-fade-up">
            @if($hero?->subtitle)
                <div class="mb-6 inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-hv-text backdrop-blur-xl dark:border-white/10 dark:bg-white/5">
                    <span class="flex h-2 w-2 rounded-full bg-hv-primary animate-pulse"></span>
                    {{ $hero->subtitle }}
                </div>
            @endif
            <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight text-hv-text md:text-5xl lg:text-6xl">
                {!! safe_html($hero?->title ?? 'İşinizi <span class="bg-gradient-to-r from-hv-primary to-hv-secondary bg-clip-text text-transparent">güçlü altyapı</span> ile büyütün') !!}
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-hv-muted">
                {{ $hero?->description ?? 'NVMe SSD hosting, yüksek performanslı VPS/VDS, dedicated sunucu ve domain hizmetleri.' }}
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ $hero?->cta_url ?? route('products.index') }}" class="btn-primary shadow-lg shadow-hv-primary/25">
                    {{ $hero?->cta_text ?? 'Paketleri Keşfet' }}
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="{{ $hero?->secondary_cta_url ?? route('contact.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-hv-border bg-hv-elevated/60 px-6 py-3 text-sm font-semibold text-hv-text backdrop-blur transition hover:bg-hv-elevated">
                    {{ $hero?->secondary_cta_text ?? 'Uzmanla Konuş' }}
                </a>
            </div>
            @include('partials.hero.stats')
        </div>

        <div class="lg:col-span-6">
            <div class="hv-hero-visual-panel relative rounded-3xl border border-white/20 bg-hv-elevated/40 p-1 backdrop-blur-2xl shadow-2xl hv-hero-slide-in">
                <div class="rounded-[1.35rem] bg-hv-elevated/80 p-4 backdrop-blur-xl sm:p-6">
                    @include('partials.hero.visual', ['hero' => $hero, 'illustrationClass' => 'max-w-none'])
                </div>
            </div>
        </div>
    </div>
</section>
