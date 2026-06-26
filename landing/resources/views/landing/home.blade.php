<x-layouts.landing>
    @unless (\App\Services\LandingAppearance::isNeonTheme())
    <section class="relative pt-10 sm:pt-14 lg:pt-20">
        <div class="hv-container">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200/90 bg-white/90 hv-shadow-soft dark:border-slate-800/80 dark:bg-slate-900/60">
                <div class="hv-grid-fade"></div>

                <div class="relative px-5 py-10 sm:px-8 sm:py-12 lg:px-12 lg:py-14">
                    <div class="mx-auto max-w-3xl space-y-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="hv-pill">
                                <span class="hv-accent-dot h-2 w-2 rounded-full shadow-[0_0_0_4px_rgb(var(--hv-brand-500)/0.25)] dark:shadow-[0_0_0_4px_rgb(var(--hv-brand-400)/0.2)]"></span>
                                {{ landing_p('home.hero_badge_engine') }}
                            </span>
                            <span class="hv-badge">
                                <span class="hv-accent-dot h-1.5 w-1.5 rounded-full opacity-90"></span>
                                {{ landing_p('home.hero_badge_model') }}
                            </span>
                        </div>

                        <div class="space-y-4">
                            <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl lg:text-[2.65rem] lg:leading-tight dark:text-slate-50">
                                {!! str_replace(
                                    ':brand',
                                    '<span class="font-semibold hv-text-brand">'.e(landing_p('brand.name')).'</span>',
                                    e(landing_p('home.hero_title'))
                                ) !!}
                            </h1>
                            <p class="max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg dark:text-slate-400">
                                {{ landing_p('home.hero_lead') }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 pt-1 sm:flex-row">
                            <a href="{{ route('site.pricing') }}" class="hv-btn-primary gap-2 px-5 py-3 text-base">
                                {{ landing_p('home.hero_cta_primary') }}
                                <span class="text-sm opacity-90">→</span>
                            </a>
                            <a href="{{ route('site.setup') }}" class="hv-btn-secondary gap-2 px-5 py-3 text-base">
                                {{ landing_p('home.hero_cta_secondary') }}
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
    @endunless

    @if (\App\Services\LandingAppearance::isNeonTheme())
        @include('landing.partials.neon-hero')
        @include('landing.partials.neon-stack')
        @include('landing.partials.neon-grid')
    @endif

    @unless (\App\Services\LandingAppearance::isNeonTheme())
    <section id="features" class="relative mt-16 sm:mt-20 lg:mt-24">
        <div class="hv-container">
            <div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-3xl">
                    <div class="hv-section-eyebrow">{{ landing_p('home.features_badge') }}</div>
                    <h2 class="hv-section-title">{{ landing_p('home.features_title') }}</h2>
                    <p class="hv-section-lead">{{ landing_p('home.features_lead') }}</p>
                </div>
            </div>

            <div class="grid gap-5 text-base sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($landingFeatureCards ?? [] as $card)
                    <article class="hv-glass flex flex-col gap-3 rounded-2xl p-6">
                        <div class="hv-card-icon">
                            <x-landing.feature-icon :name="$card['icon'] ?? 'layers'" />
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $card['title'] }}</h3>
                        <p class="text-base leading-relaxed text-slate-600 dark:text-slate-400">{{ $card['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    @endunless

    <section id="pricing" class="relative mt-20 lg:mt-24 @if (\App\Services\LandingAppearance::isNeonTheme()) hv-neon-page-section @endif">
        <div class="hv-container">
            <div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-3xl">
                    <div class="hv-section-eyebrow">{{ landing_p('home.pricing_badge') }}</div>
                    <h2 class="hv-section-title">{{ landing_p('home.pricing_title') }}</h2>
                    <p class="hv-section-lead">{{ landing_p('home.pricing_lead') }}</p>
                </div>
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ landing_p('home.pricing_period') }}</div>
            </div>

            <div class="grid gap-5 text-base sm:grid-cols-2 lg:grid-cols-3">
                <div class="flex flex-col rounded-2xl border border-slate-200/90 bg-white/90 p-6 dark:border-slate-800 dark:bg-slate-900/60">
                    <h3 class="mb-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ landing_p('home.pricing_freemium_title') }}</h3>
                    <p class="mb-4 text-base text-slate-600 dark:text-slate-400">{{ landing_p('home.pricing_freemium_desc') }}</p>
                    <div class="mb-4 flex items-baseline gap-1 text-slate-900 dark:text-slate-100">
                        <span class="text-3xl font-semibold">{{ landing_p('home.pricing_freemium_amount') }}</span>
                        <span class="text-sm text-slate-500">{{ landing_p('home.pricing_freemium_period') }}</span>
                    </div>
                    <ul class="mb-6 flex-1 space-y-2 text-base text-slate-700 dark:text-slate-300">
                        <li>{{ landing_p('home.pricing_freemium_li_1') }}</li>
                        <li>{{ landing_p('home.pricing_freemium_li_2') }}</li>
                        <li>{{ landing_p('home.pricing_freemium_li_3') }}</li>
                        <li>{{ landing_p('home.pricing_freemium_li_4') }}</li>
                    </ul>
                    <button type="button" class="mt-auto inline-flex w-full items-center justify-center rounded-full border border-slate-300/90 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100">
                        {{ landing_p('home.pricing_freemium_cta') }}
                    </button>
                </div>

                <div class="hv-card-pro">
                    <div class="hv-card-pro-badge">
                        {{ landing_p('home.pricing_pro_badge') }}
                    </div>
                    <h3 class="mb-1 pr-16 text-lg font-semibold text-slate-900 dark:text-slate-50">{{ landing_p('home.pricing_pro_title') }}</h3>
                    <p class="mb-4 text-base text-slate-600 dark:text-slate-300">{{ landing_p('home.pricing_pro_desc') }}</p>
                    <div class="mb-4 flex items-baseline gap-1 hv-text-brand">
                        <span class="text-3xl font-semibold">{{ landing_p('home.pricing_pro_amount') }}</span>
                        <span class="text-sm">{{ landing_p('home.pricing_pro_period') }}</span>
                    </div>
                    <ul class="mb-6 flex-1 space-y-2 text-base text-slate-800 dark:text-slate-200">
                        <li>{{ landing_p('home.pricing_pro_li_1') }}</li>
                        <li>{{ landing_p('home.pricing_pro_li_2') }}</li>
                        <li>{{ landing_p('home.pricing_pro_li_3') }}</li>
                        <li>{{ landing_p('home.pricing_pro_li_4') }}</li>
                    </ul>
                    <button type="button" class="hv-btn-primary-sm mt-auto w-full py-2.5 text-sm font-semibold text-white">
                        {{ landing_p('home.pricing_pro_cta') }}
                    </button>
                </div>

                <div class="flex flex-col rounded-2xl border border-slate-200/90 bg-white/90 p-6 dark:border-slate-800 dark:bg-slate-900/60">
                    <h3 class="mb-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ landing_p('home.pricing_vendor_title') }}</h3>
                    <p class="mb-4 text-base text-slate-600 dark:text-slate-400">{{ landing_p('home.pricing_vendor_desc') }}</p>
                    <p class="mb-6 flex-1 text-base text-slate-700 dark:text-slate-300">{{ landing_p('home.pricing_vendor_lead') }}</p>
                    <button type="button" class="mt-auto inline-flex w-full items-center justify-center rounded-full border border-slate-300/90 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100">
                        {{ landing_p('home.pricing_vendor_cta') }}
                    </button>
                </div>
            </div>
            <p class="mt-8 text-center text-sm text-slate-500 dark:text-slate-500">
                {{ landing_p('home.pricing_page_cta') }}
                <a href="{{ route('site.pricing') }}" class="hv-link-quiet">{{ landing_p('home.pricing_page_link') }}</a>.
            </p>
        </div>
    </section>

    <section id="docs" class="relative mb-20 mt-20 lg:mt-24 @if (\App\Services\LandingAppearance::isNeonTheme()) hv-neon-page-section @endif">
        <div class="hv-container max-w-2xl">
            <div class="space-y-4">
                <div class="hv-section-eyebrow">{{ landing_p('home.docs_badge') }}</div>
                <h2 class="hv-section-title">{{ landing_p('home.docs_title') }}</h2>
                <p class="hv-section-lead">{{ landing_p('home.docs_lead') }}</p>
                <x-landing.install-commands variant="compact" />
            </div>
        </div>
    </section>
</x-layouts.landing>
