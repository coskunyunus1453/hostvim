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

    {{-- Bağlantı ağı: hizmetlerden merkeze akan veri (hosting/domain/bulut/db) --}}
    <svg class="hv-speed-net" viewBox="0 0 400 400" fill="none" preserveAspectRatio="none">
        <line class="hv-speed-link" x1="60" y1="80" x2="200" y2="200"/>
        <line class="hv-speed-link" x1="340" y1="80" x2="200" y2="200"/>
        <line class="hv-speed-link" x1="60" y1="320" x2="200" y2="200"/>
        <line class="hv-speed-link" x1="340" y1="320" x2="200" y2="200"/>
        <circle class="hv-speed-packet" cx="60" cy="80" r="4" style="--px:140px; --py:120px"/>
        <circle class="hv-speed-packet" cx="340" cy="80" r="4" style="--px:-140px; --py:120px; animation-delay:.6s"/>
        <circle class="hv-speed-packet" cx="60" cy="320" r="4" style="--px:140px; --py:-120px; animation-delay:1.2s"/>
        <circle class="hv-speed-packet" cx="340" cy="320" r="4" style="--px:-140px; --py:-120px; animation-delay:1.8s"/>
    </svg>

    {{-- Hizmet ikonları (yazısız) --}}
    {{-- Sunucu / Hosting --}}
    <div class="hv-speed-icon" style="--x:15%; --y:20%; --d:0s">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <rect x="3" y="4" width="18" height="7" rx="1.5"/>
            <rect x="3" y="13" width="18" height="7" rx="1.5"/>
            <circle cx="7" cy="7.5" r="1" fill="currentColor" stroke="none"/>
            <circle cx="7" cy="16.5" r="1" fill="currentColor" stroke="none"/>
        </svg>
        <span class="hv-speed-ping" style="--d:0s"></span>
    </div>
    {{-- Domain / Dünya --}}
    <div class="hv-speed-icon" style="--x:85%; --y:20%; --d:.8s">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <circle cx="12" cy="12" r="9"/>
            <path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18" stroke-linecap="round"/>
        </svg>
    </div>
    {{-- Bulut --}}
    <div class="hv-speed-icon" style="--x:15%; --y:80%; --d:1.4s">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M7 18a4 4 0 01-.88-7.9A5.5 5.5 0 0117.5 8.5 4.5 4.5 0 0119 17H7z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    {{-- Veritabanı / Depolama --}}
    <div class="hv-speed-icon" style="--x:85%; --y:80%; --d:.4s">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <ellipse cx="12" cy="5" rx="8" ry="3"/>
            <path d="M4 5v6c0 1.66 3.58 3 8 3s8-1.34 8-3V5"/>
            <path d="M4 11v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/>
        </svg>
        <span class="hv-speed-ping" style="--d:1s"></span>
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
