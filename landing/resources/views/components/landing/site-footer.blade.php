{{-- Birleşik modern footer (non-neon) — tüm landing sayfalarında aynıdır. --}}
@php
    $hvFooterNote = \App\Services\LandingAppearance::footerExtraNote();
@endphp
<footer class="relative z-10 mt-auto border-t border-slate-200/80 bg-white/85 dark:border-slate-800/70 dark:bg-slate-950/90">
    <div class="hv-container py-12 lg:py-14">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,2fr)] lg:gap-16">
            {{-- Marka --}}
            <div class="max-w-sm">
                <x-landing.header-brand variant="classic" context="drawer" />
                @if ($sub = landing_p('brand.subtitle'))
                    <p class="mt-4 text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $sub }}</p>
                @endif
                <div class="mt-5">
                    <x-landing.footer-extras />
                </div>
            </div>

            {{-- Bağlantılar --}}
            @if (! empty($landingFooterNav) && count($landingFooterNav))
                <nav class="grid grid-cols-2 gap-x-8 gap-y-2.5 text-sm sm:grid-cols-3 lg:justify-items-start">
                    <x-landing.nav-menu :items="$landingFooterNav" link-class="hv-muted-nav inline-block py-0.5 transition hover:text-[rgb(var(--hv-brand-600)/1)] dark:hover:text-[rgb(var(--hv-brand-400)/1)]" />
                </nav>
            @endif
        </div>

        <div class="mt-10 flex flex-col gap-3 border-t border-slate-200/70 pt-6 text-xs text-slate-500 dark:border-slate-800/60 dark:text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span>&copy; {{ date('Y') }} {{ landing_p('brand.name') }}.</span>
                <span>{{ landing_p('footer.rights') }}</span>
            </p>
            @if ($hvFooterNote)
                <p class="text-slate-400 dark:text-slate-500">{{ $hvFooterNote }}</p>
            @endif
        </div>
    </div>
</footer>
