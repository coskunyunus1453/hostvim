@php
    $panelTitle = $siteSettings['nav_services_mega_title'] ?? 'Doğru paketi seçin';
    $panelText = $siteSettings['nav_services_mega_text'] ?? '';
    $panelCtaLabel = $siteSettings['nav_services_mega_cta_label'] ?? 'Tüm paketleri gör';
    $panelCtaUrl = $siteSettings['nav_services_mega_cta_url'] ?? route('products.index');
@endphp
<div class="hv-nav-dropdown hv-nav-dropdown-mega hv-nav-dropdown-wide" data-nav-dropdown>
    <button type="button" class="nav-link hv-nav-trigger flex items-center gap-1" data-nav-dropdown-trigger aria-expanded="false" aria-haspopup="true">
        Hizmetler
        <svg class="hv-nav-chevron h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="hv-nav-dropdown-panel" data-nav-dropdown-panel role="menu">
        <div class="hv-mega-grid">
            <div class="hv-mega-links">
                <p class="hv-mega-section-title">Kategoriler</p>
                <div class="hv-mega-items">
                    @foreach($navCategories as $cat)
                        <a href="{{ route('products.category', $cat->slug) }}" class="hv-mega-link" role="menuitem">
                            <span class="hv-mega-link-icon" style="--mega-accent: {{ $cat->color ?? 'var(--hv-primary)' }}">
                                @include('partials.nav-icon', ['icon' => 'server', 'class' => 'h-5 w-5'])
                            </span>
                            <span class="hv-mega-link-body">
                                <span class="hv-mega-link-label">{{ $cat->name }}</span>
                                @if($cat->description)
                                    <span class="hv-mega-link-desc">{{ \Illuminate\Support\Str::limit($cat->description, 72) }}</span>
                                @endif
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
