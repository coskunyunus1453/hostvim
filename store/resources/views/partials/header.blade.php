@php
    $hClass = 'hv-header hv-header-' . ($themeHeaderStyle ?? 'glass');
    $hClass .= filter_var($themeHeaderSticky ?? true, FILTER_VALIDATE_BOOLEAN) ? ' hv-header-sticky' : ' hv-header-static';
    if (filter_var($themeHeaderBlur ?? true, FILTER_VALIDATE_BOOLEAN)) $hClass .= ' hv-header-blur';
    if (! filter_var($themeHeaderBorder ?? true, FILTER_VALIDATE_BOOLEAN)) $hClass .= ' border-b-0';
@endphp
<header class="{{ $hClass }}">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 lg:px-8">
        @include('partials.site-logo', ['height' => $siteLogoHeight ?? 40])

        @include('partials.nav-desktop')

        <div class="flex items-center gap-1 sm:gap-2">
            @include('partials.header-account')
            <a href="{{ route('cart.index') }}" class="theme-toggle relative" aria-label="Sepet" id="cart-link">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span id="cart-badge" class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-hv-primary text-xs font-bold text-white {{ $cartCount > 0 ? '' : 'hidden' }}">{{ $cartCount }}</span>
            </a>
            <a href="{{ route('products.index') }}" class="btn-primary hidden sm:inline-flex">Paketleri İncele</a>

            <button type="button" id="mobile-menu-open" class="theme-toggle lg:hidden" aria-label="Menüyü aç" aria-controls="mobile-sidebar-panel" aria-expanded="false">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
</header>

@include('partials.mobile-sidebar')
