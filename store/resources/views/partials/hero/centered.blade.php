<section class="relative overflow-hidden bg-hv-bg">
    <div class="absolute inset-0 bg-gradient-to-b from-hv-primary/10 via-transparent to-hv-secondary/10"></div>
    <div class="absolute left-1/2 top-0 h-[500px] w-[800px] -translate-x-1/2 rounded-full bg-hv-primary/20 blur-3xl"></div>
    <div class="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-hv-secondary/20 blur-3xl"></div>

    <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 sm:py-20 lg:grid-cols-2 lg:gap-14 lg:px-8 lg:py-28">
        <div class="animate-fade-up text-center lg:text-left">
            @if($hero?->subtitle)
                <span class="inline-flex items-center gap-2 rounded-full border border-hv-border bg-hv-elevated/80 px-5 py-2 text-sm font-semibold text-hv-secondary backdrop-blur">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-hv-secondary"></span>
                    {{ $hero->subtitle }}
                </span>
            @endif
            <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-hv-text md:text-5xl lg:text-6xl">
                {!! safe_html($hero?->title ?? 'İşinizi <span class="text-hv-primary">güçlü altyapı</span> ile büyütün') !!}
            </h1>
            <p class="mx-auto mt-6 max-w-xl text-lg leading-relaxed text-hv-muted md:text-xl lg:mx-0">
                {{ $hero?->description ?? 'NVMe SSD hosting, yüksek performanslı VPS/VDS, dedicated sunucu ve domain hizmetleri.' }}
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4 lg:justify-start">
                <a href="{{ $hero?->cta_url ?? route('products.index') }}" class="btn-primary text-base px-8 py-4">
                    {{ $hero?->cta_text ?? 'Paketleri Keşfet' }}
                </a>
                <a href="{{ $hero?->secondary_cta_url ?? route('contact.index') }}" class="btn-secondary text-base px-8 py-4">
                    {{ $hero?->secondary_cta_text ?? 'Uzmanla Konuş' }}
                </a>
            </div>
            <div class="flex justify-center lg:justify-start">
                @include('partials.hero.stats')
            </div>
        </div>

        <div class="hv-hero-slide-in">
            <div class="hv-hero-visual-panel rounded-3xl border border-hv-border bg-hv-elevated/70 p-4 shadow-2xl backdrop-blur-md sm:p-6">
                @include('partials.hero.visual', ['hero' => $hero, 'illustrationClass' => 'max-w-none'])
            </div>
        </div>
    </div>
</section>
