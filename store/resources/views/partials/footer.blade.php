@php
    $footerStyle = $themeFooterStyle ?? 'default';
    $footerClass = 'border-t border-hv-border hv-footer-' . $footerStyle;
    $footerColumns = $footerMenu ? $footerMenu->activeRootItems : collect();
    $footerBottomItems = $footerBottomMenu ? $footerBottomMenu->activeItems : collect();
@endphp
<footer class="{{ $footerClass }}">
    @if($footerStyle === 'minimal')
        <div class="mx-auto flex max-w-7xl flex-col items-center gap-4 px-4 py-8 text-center text-sm text-hv-muted lg:px-8">
            <div class="flex items-center gap-2">
                @include('partials.site-logo', ['height' => $siteLogoFooterHeight ?? 32, 'nameClass' => 'text-sm font-bold'])
            </div>
            @if($footerBottomItems->isNotEmpty())
                <div class="flex flex-wrap justify-center gap-x-5 gap-y-2">
                    @foreach($footerBottomItems as $l)
                        <a href="{{ $l->href }}" class="hover:text-hv-primary" target="{{ $l->safe_target }}" @if($l->safe_target === '_blank') rel="noopener noreferrer" @endif>{{ $l->label }}</a>
                    @endforeach
                </div>
            @endif
            <p>&copy; {{ date('Y') }} {{ $siteName }}. Tüm hakları saklıdır.</p>
        </div>
    @else
        <div class="mx-auto max-w-7xl px-4 py-16 lg:px-8">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-12">
                {{-- Marka --}}
                <div class="lg:col-span-4">
                    <div class="flex items-center gap-2">
                        @include('partials.site-logo', ['height' => $siteLogoFooterHeight ?? 32, 'nameClass' => 'text-lg font-bold'])
                    </div>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-hv-muted">
                        {{ $siteSettings['footer_text'] ?? 'Türkiye\'nin güvenilir hosting, VPS, VDS ve sunucu çözüm ortağı. NVMe SSD altyapısı, 7/24 Türkçe teknik destek ve %99.9 uptime garantisi.' }}
                    </p>
                    <div class="mt-5 space-y-2 text-sm text-hv-muted">
                        @if($phone = $siteSettings['contact_phone'] ?? null)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="flex items-center gap-2 hover:text-hv-primary">
                                <svg class="h-4 w-4 text-hv-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $phone }}
                            </a>
                        @endif
                        @if($email = $siteSettings['contact_email'] ?? null)
                            <a href="mailto:{{ $email }}" class="flex items-center gap-2 hover:text-hv-primary">
                                <svg class="h-4 w-4 text-hv-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                {{ $email }}
                            </a>
                        @endif
                        @if($address = $siteSettings['contact_address'] ?? null)
                            <p class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-hv-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $address }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Admin'den yönetilen footer sütunları --}}
                @if($footerColumns->isNotEmpty())
                    <div class="lg:col-span-8">
                        <div class="grid grid-cols-2 gap-8 sm:grid-cols-3">
                            @foreach($footerColumns as $column)
                                <div>
                                    <h4 class="font-semibold text-hv-text">
                                        @if($column->href !== '#')
                                            <a href="{{ $column->href }}" class="hover:text-hv-primary" target="{{ $column->safe_target }}" @if($column->safe_target === '_blank') rel="noopener noreferrer" @endif>{{ $column->label }}</a>
                                        @else
                                            {{ $column->label }}
                                        @endif
                                    </h4>
                                    @if($column->activeChildren->isNotEmpty())
                                        <ul class="mt-4 space-y-2.5 text-sm text-hv-muted">
                                            @foreach($column->activeChildren as $link)
                                                <li><a href="{{ $link->href }}" class="hover:text-hv-primary" target="{{ $link->safe_target }}" @if($link->safe_target === '_blank') rel="noopener noreferrer" @endif>{{ $link->label }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-12 flex flex-col gap-4 border-t border-hv-border pt-8 text-sm text-hv-muted md:flex-row md:items-center md:justify-between">
                <p>&copy; {{ date('Y') }} {{ $siteName }}. Tüm hakları saklıdır.</p>
                @if($footerBottomItems->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        @foreach($footerBottomItems as $l)
                            <a href="{{ $l->href }}" class="hover:text-hv-primary" target="{{ $l->safe_target }}" @if($l->safe_target === '_blank') rel="noopener noreferrer" @endif>{{ $l->label }}</a>
                        @endforeach
                    </div>
                @endif
                @if($themeFooterShowStats ?? true)
                    <div class="flex gap-4">
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-hv-secondary"></span> 7/24 Destek</span>
                        <span>%99.9 Uptime SLA</span>
                    </div>
                @endif
            </div>
        </div>
    @endif
</footer>
