{{-- HostVim uyumlu footer (panelze.com / klasik tema) --}}
@php
    $hvFooterNote = \App\Services\LandingAppearance::footerExtraNote();
    $hvContact = \App\Services\LandingAppearance::contactEmail();
@endphp
<footer class="hv-footer-gradient mt-auto">
    <div class="mx-auto max-w-7xl px-4 py-16 lg:px-8">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-12">
            {{-- Marka --}}
            <div class="lg:col-span-4">
                <x-landing.header-brand variant="classic" context="drawer" />
                @if ($sub = landing_p('brand.subtitle'))
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-hv-muted">{{ $sub }}</p>
                @endif
                <div class="mt-5 space-y-2 text-sm text-hv-muted">
                    @if ($hvContact)
                        <a href="mailto:{{ $hvContact }}" class="flex items-center gap-2 hover:text-hv-primary">
                            <svg class="h-4 w-4 text-hv-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ $hvContact }}
                        </a>
                    @endif
                </div>
                <div class="mt-5">
                    <x-landing.footer-extras />
                </div>
            </div>

            {{-- Bağlantılar --}}
            @if (! empty($landingFooterNav) && count($landingFooterNav))
                <div class="lg:col-span-8">
                    <nav class="grid grid-cols-2 gap-8 sm:grid-cols-3" aria-label="Footer">
                        @foreach ($landingFooterNav as $footerItem)
                            <div>
                                <a href="{{ $footerItem->resolvedHref() }}"
                                   class="font-semibold text-hv-text hover:text-hv-primary"
                                   @if ($footerItem->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>
                                    {{ $footerItem->displayLabel() }}
                                </a>
                            </div>
                        @endforeach
                    </nav>
                </div>
            @endif
        </div>

        <div class="mt-12 flex flex-col gap-4 border-t border-hv-border pt-8 text-sm text-hv-muted md:flex-row md:items-center md:justify-between">
            <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span>&copy; {{ date('Y') }} {{ landing_p('brand.name') }}.</span>
                <span>{{ landing_p('footer.rights') }}</span>
            </p>
            @if ($hvFooterNote)
                <p>{{ $hvFooterNote }}</p>
            @else
                <div class="flex flex-wrap gap-4 text-xs">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-hv-secondary"></span> 7/24 Destek</span>
                    <span>%99.9 Uptime SLA</span>
                </div>
            @endif
        </div>
    </div>
</footer>
