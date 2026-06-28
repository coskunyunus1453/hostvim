@php
    $megaLinks = [
        ['label' => 'Web Hosting', 'desc' => 'NVMe SSD, kolay yönetim paneli, ücretsiz SSL', 'href' => route('hosting.index'), 'icon' => 'server', 'accent' => 'var(--hv-primary)'],
        ['label' => 'Bulut Sunucu (VPS)', 'desc' => 'Tam yetkili, anında ölçeklenebilir kaynaklar', 'href' => route('cloud.index'), 'icon' => 'cloud', 'accent' => 'var(--hv-secondary)'],
        ['label' => 'VDS Sunucu', 'desc' => 'Garantili işlemci ve RAM performansı', 'href' => route('products.category', 'vds'), 'icon' => 'cpu', 'accent' => 'var(--hv-primary)'],
        ['label' => 'Dedicated Sunucu', 'desc' => 'Size özel, paylaşımsız fiziksel sunucu', 'href' => route('products.category', 'dedicated'), 'icon' => 'shield', 'accent' => 'var(--hv-secondary)'],
        ['label' => 'Domain / Alan Adı', 'desc' => 'Sorgula, transfer et, saniyeler içinde tescil et', 'href' => route('domain.index'), 'icon' => 'globe', 'accent' => 'var(--hv-primary)'],
    ];
    $panelTitle = $siteSettings['nav_services_mega_title'] ?? 'Doğru paketi seçin';
    $panelText = $siteSettings['nav_services_mega_text'] ?? '7/24 Türkçe destek, %99.9 uptime ve ücretsiz taşıma ile projeniz güvende.';
    $panelCtaLabel = $siteSettings['nav_services_mega_cta_label'] ?? 'Web Hosting paketleri';
    $panelCtaUrl = $siteSettings['nav_services_mega_cta_url'] ?? route('hosting.index');
@endphp
<div class="hv-nav-dropdown hv-nav-dropdown-mega hv-nav-dropdown-wide" data-nav-dropdown>
    <button type="button" class="nav-link hv-nav-trigger flex items-center gap-1" data-nav-dropdown-trigger aria-expanded="false" aria-haspopup="true">
        Hizmetler
        <svg class="hv-nav-chevron h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="hv-nav-dropdown-panel" data-nav-dropdown-panel role="menu">
        <div class="hv-mega-grid">
            <div class="hv-mega-links">
                <p class="hv-mega-section-title">Barındırma & Sunucu</p>
                <div class="hv-mega-items">
                    @foreach($megaLinks as $link)
                        <a href="{{ $link['href'] }}" class="hv-mega-link" role="menuitem">
                            <span class="hv-mega-link-icon" style="--mega-accent: {{ $link['accent'] }}">
                                @include('partials.nav-icon', ['icon' => $link['icon'], 'class' => 'h-5 w-5'])
                            </span>
                            <span class="hv-mega-link-body">
                                <span class="hv-mega-link-label">{{ $link['label'] }}</span>
                                <span class="hv-mega-link-desc">{{ $link['desc'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('products.index') }}" class="hv-mega-footer-link" role="menuitem">Tüm paketler →</a>
            </div>
            @if($panelTitle || $panelText)
                <aside class="hv-mega-panel" aria-label="Bilgilendirme">
                    <div class="hv-mega-panel-inner">
                        @include('partials.nav-icon', ['icon' => 'sparkles', 'class' => 'h-8 w-8 text-hv-primary'])
                        @if($panelTitle)
                            <h3 class="hv-mega-panel-title">{{ $panelTitle }}</h3>
                        @endif
                        @if($panelText)
                            <p class="hv-mega-panel-text">{{ $panelText }}</p>
                        @endif
                        @if($panelCtaLabel && $panelCtaUrl)
                            <a href="{{ $panelCtaUrl }}" class="btn-primary mt-4 inline-flex text-sm">{{ $panelCtaLabel }}</a>
                        @endif
                    </div>
                </aside>
            @endif
        </div>
    </div>
</div>
