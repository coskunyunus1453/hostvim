@php
    $sceneClass = $class ?? 'max-w-xl';
@endphp

<div class="hv-hero-scene hv-speed-scene {{ $sceneClass }} mx-auto" aria-hidden="true">
    <div class="hv-hero-glow hv-hero-glow-a"></div>
    <div class="hv-hero-glow hv-hero-glow-b"></div>

    {{-- Arka plan hız çizgileri --}}
    <div class="hv-speed-streaks">
        <span style="--i:0; top:16%"></span>
        <span style="--i:1; top:30%"></span>
        <span style="--i:2; top:48%"></span>
        <span style="--i:3; top:66%"></span>
        <span style="--i:4; top:82%"></span>
    </div>

    {{-- Speedometer (çerçevesiz, sadece animasyon) --}}
    <div class="hv-speed-gauge">
        <svg viewBox="0 0 200 118" class="hv-speed-gauge-svg" fill="none">
            <defs>
                <linearGradient id="hvSpeedArc" x1="18" y1="0" x2="182" y2="0" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stop-color="var(--hv-primary)"/>
                    <stop offset="0.55" stop-color="var(--hv-primary)"/>
                    <stop offset="1" stop-color="var(--hv-secondary)"/>
                </linearGradient>
            </defs>

            {{-- skala tikleri --}}
            <g class="hv-speed-tick" stroke-width="3" stroke-linecap="round">
                <line x1="30" y1="100"   x2="16"  y2="100"/>
                <line x1="35.3" y1="73.2" x2="22.4" y2="67.9"/>
                <line x1="50.5" y1="50.5" x2="40.6" y2="40.6"/>
                <line x1="73.2" y1="35.3" x2="67.9" y2="22.4"/>
                <line x1="100" y1="30"   x2="100" y2="16"/>
                <line x1="126.8" y1="35.3" x2="132.1" y2="22.4"/>
                <line x1="149.5" y1="50.5" x2="159.4" y2="40.6"/>
                <line x1="164.7" y1="73.2" x2="177.6" y2="67.9"/>
                <line x1="170" y1="100"  x2="184" y2="100"/>
            </g>

            {{-- arka yay + dolan yay --}}
            <path class="hv-speed-track" d="M18 100 A82 82 0 0 1 182 100" stroke-width="12" stroke-linecap="round"/>
            <path class="hv-speed-arc" d="M18 100 A82 82 0 0 1 182 100" stroke="url(#hvSpeedArc)" stroke-width="12" stroke-linecap="round" pathLength="100"/>
        </svg>
        <div class="hv-speed-needle"></div>
        <div class="hv-speed-hub"></div>
    </div>

    {{-- Uçan roket --}}
    <div class="hv-speed-rocket-fly">🚀</div>
</div>
