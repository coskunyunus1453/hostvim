@props([
    'variant' => 'classic',
    'context' => 'header',
])

@php
    use App\Services\LandingAppearance;

    $showLogo = LandingAppearance::showHeaderLogo();
    $showText = LandingAppearance::showHeaderBrandText();
    $isNeon = $variant === 'neon';
    $name = landing_p('brand.name');
    $logoH = $context === 'footer'
        ? LandingAppearance::siteLogoFooterMaxHeightPx()
        : LandingAppearance::siteLogoHeaderMaxHeightPx();
@endphp

@if ($context === 'header')
    <a href="{{ route('landing.home') }}"
       data-hv-header-brand="1"
       class="group flex min-w-0 shrink-0 items-center gap-2.5 sm:gap-3">
        @if ($showLogo)
            <x-landing.brand-logo :variant="$isNeon ? 'neon' : 'classic'" />
        @endif
        @if ($showText)
            <span class="truncate font-bold tracking-tight text-hv-text {{ $isNeon ? 'text-sm sm:text-base' : 'text-lg' }}">{{ $name }}</span>
        @endif
    </a>
@else
    <div class="flex min-w-0 items-center gap-2.5">
        @if ($showLogo)
            <x-landing.brand-logo :variant="$isNeon ? 'neon-footer' : 'classic'" class="{{ $context === 'drawer' ? '' : '' }}" />
        @endif
        @if ($showText)
            <span class="truncate font-bold text-hv-text {{ $context === 'drawer' ? 'text-base' : 'text-lg' }}">{{ $name }}</span>
        @endif
    </div>
@endif
