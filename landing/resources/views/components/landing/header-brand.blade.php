@props([
    'variant' => 'classic',
    'context' => 'header',
])

@php
    use App\Services\LandingAppearance;

    $showLogo = LandingAppearance::showHeaderLogo();
    $showText = LandingAppearance::showHeaderBrandText();
    $isNeon = $variant === 'neon';
@endphp

@if ($context === 'header')
    <a href="{{ route('landing.home') }}"
       data-hv-header-brand="1"
       data-hv-show-logo="{{ $showLogo ? '1' : '0' }}"
       data-hv-show-text="{{ $showText ? '1' : '0' }}"
       @class([
           'group flex min-w-0 items-center gap-2.5 sm:gap-3' => $isNeon,
           'flex items-center gap-3' => ! $isNeon,
       ])>
        @if ($showLogo)
            <x-landing.brand-logo :variant="$isNeon ? 'neon' : 'classic'" />
        @endif
        @if ($showText)
            <div @class([
                'min-w-0 leading-tight' => $isNeon,
                'flex flex-col leading-tight' => ! $isNeon,
            ])>
                <span @class([
                    'block truncate text-sm font-semibold tracking-tight text-slate-900 dark:text-slate-50 sm:text-base' => $isNeon,
                    'text-base font-semibold tracking-tight text-slate-900 dark:text-slate-100' => ! $isNeon,
                ])>{{ landing_p('brand.name') }}</span>
                <span @class([
                    'hidden text-[11px] font-medium text-slate-500 dark:text-slate-400 sm:block' => $isNeon,
                    'text-xs font-medium text-slate-500 dark:text-slate-400' => ! $isNeon,
                ])>{{ landing_p('brand.subtitle') }}</span>
            </div>
        @endif
    </a>
@else
    <div class="flex min-w-0 items-center gap-2">
        @if ($showLogo)
            <x-landing.brand-logo :variant="$isNeon ? 'neon' : 'classic'" class="!h-8" />
        @endif
        @if ($showText)
            <span @class([
                'truncate font-semibold text-slate-900 dark:text-slate-100' => $isNeon,
                'text-base font-semibold text-slate-900 dark:text-slate-100' => ! $isNeon,
            ])>{{ landing_p('brand.name') }}</span>
        @endif
    </div>
@endif
