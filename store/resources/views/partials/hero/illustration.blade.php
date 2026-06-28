@php
    $sceneClass = $class ?? 'max-w-xl';
@endphp

<div class="hv-hero-scene hv-speed-scene {{ $sceneClass }} mx-auto" aria-hidden="true">
    <div class="hv-hero-glow hv-hero-glow-a"></div>
    <div class="hv-hero-glow hv-hero-glow-b"></div>

    {{-- Arka plan hız çizgileri (hareket / hız hissi) --}}
    <div class="hv-speed-streaks">
        <span style="--i:0; top:16%"></span>
        <span style="--i:1; top:30%"></span>
        <span style="--i:2; top:48%"></span>
        <span style="--i:3; top:66%"></span>
        <span style="--i:4; top:82%"></span>
    </div>

    {{-- Merkez: sayfa hızı göstergesi --}}
    <div class="hv-speed-card hv-hero-float-center">
        <div class="hv-speed-card-head">
            <span class="hv-hero-led hv-hero-led-green"></span>
            <span class="hv-speed-card-title">SAYFA HIZI</span>
            <span class="hv-speed-rocket">🚀</span>
        </div>

        <div class="hv-speed-gauge">
            <svg viewBox="0 0 200 118" class="hv-speed-gauge-svg" fill="none">
                <defs>
                    <linearGradient id="hvSpeedArc" x1="18" y1="0" x2="182" y2="0" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="var(--hv-primary)"/>
                        <stop offset="1" stop-color="var(--hv-secondary)"/>
                    </linearGradient>
                </defs>
                <path d="M18 100 A82 82 0 0 1 182 100" stroke="currentColor" stroke-width="12" stroke-linecap="round" opacity="0.18"/>
                <path class="hv-speed-arc" d="M18 100 A82 82 0 0 1 182 100" stroke="url(#hvSpeedArc)" stroke-width="12" stroke-linecap="round" pathLength="100"/>
            </svg>
            <div class="hv-speed-needle"></div>
            <div class="hv-speed-hub"></div>
            <div class="hv-speed-readout">
                <span class="hv-speed-value">0.3<small>s</small></span>
                <span class="hv-speed-label">yükleme süresi</span>
            </div>
        </div>

        <div class="hv-speed-bar"><div class="hv-speed-bar-fill"></div></div>
        <div class="hv-speed-card-foot">
            <span>NVMe · LiteSpeed</span>
            <span class="hv-hero-uptime-badge">%99.9 uptime</span>
        </div>
    </div>

    {{-- Uçan performans rozetleri --}}
    <div class="hv-speed-chip hv-speed-chip-a hv-hero-float-slow">
        <span class="hv-speed-chip-num">100</span>
        <span>PageSpeed</span>
    </div>
    <div class="hv-speed-chip hv-speed-chip-b hv-hero-float-delay">
        <span class="hv-speed-chip-num">28<small>ms</small></span>
        <span>TTFB</span>
    </div>
    <div class="hv-speed-chip hv-speed-chip-c hv-hero-float-slow-2">
        <span class="hv-speed-chip-num">⚡</span>
        <span>LiteSpeed</span>
    </div>
</div>
