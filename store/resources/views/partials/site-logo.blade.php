@php
    $logoH = $height ?? ($siteLogoHeight ?? 40);
    $logoUrl = $siteLogoUrl ?? null;
    $logoDarkUrl = $siteLogoDarkUrl ?? null;
    $hasDarkVariant = filled($logoDarkUrl) && $logoDarkUrl !== $logoUrl;
    $showName = $siteLogoShowName ?? true;
    $logoSlotW = max($logoH * 2, $logoH);
@endphp
<a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 {{ $class ?? '' }}">
    <span class="inline-flex shrink-0 items-center justify-center" style="width: {{ $logoSlotW }}px; min-width: {{ $logoSlotW }}px; height: {{ $logoH }}px;">
        @if($logoUrl)
            @if($hasDarkVariant)
                <img
                    src="{{ $logoUrl }}"
                    alt="{{ $siteName }}"
                    class="h-full w-auto max-w-full object-contain dark:hidden"
                    width="{{ $logoSlotW }}"
                    height="{{ $logoH }}"
                    decoding="async"
                >
                <img
                    src="{{ $logoDarkUrl }}"
                    alt="{{ $siteName }}"
                    class="hidden h-full w-auto max-w-full object-contain dark:block"
                    width="{{ $logoSlotW }}"
                    height="{{ $logoH }}"
                    decoding="async"
                >
            @else
                <img
                    src="{{ $logoUrl }}"
                    alt="{{ $siteName }}"
                    class="h-full w-auto max-w-full object-contain"
                    width="{{ $logoSlotW }}"
                    height="{{ $logoH }}"
                    decoding="async"
                >
            @endif
        @else
            <span class="inline-flex items-center justify-center rounded-xl bg-gradient-to-br from-hv-primary to-hv-secondary font-extrabold text-white shadow-md" style="width: {{ $logoH }}px; height: {{ $logoH }}px; font-size: {{ max(12, (int) ($logoH * 0.4)) }}px;">
                {{ mb_substr($siteName, 0, 1) }}
            </span>
        @endif
    </span>
    @if($showName)
        <span class="font-bold tracking-tight text-hv-text {{ $nameClass ?? 'text-xl' }}">{{ $siteName }}</span>
    @endif
</a>
