@if($item->isDropdown() && $item->activeChildren->isNotEmpty())
    @php
        $megaClass = $item->isMegaWide() ? 'hv-nav-dropdown-wide' : ($item->isMega() ? 'hv-nav-dropdown-mega' : '');
    @endphp
    <div class="hv-nav-dropdown {{ $megaClass }}" data-nav-dropdown>
        <button type="button" class="nav-link hv-nav-trigger flex items-center gap-1" data-nav-dropdown-trigger aria-expanded="false" aria-haspopup="true">
            @if($item->icon)
                @include('partials.nav-icon', ['icon' => $item->icon, 'class' => 'h-4 w-4'])
            @endif
            {{ $item->label }}
            @if($item->badge)
                <span class="hv-nav-badge">{{ $item->badge }}</span>
            @endif
            <svg class="hv-nav-chevron h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="hv-nav-dropdown-panel" data-nav-dropdown-panel role="menu">
            @if($item->isMega())
                <div class="hv-mega-grid {{ $item->hasPanel() ? '' : 'hv-mega-grid-single' }}">
                    <div class="hv-mega-links">
                        <div class="hv-mega-items {{ $item->isMegaWide() ? 'hv-mega-items-cols' : '' }}">
                            @foreach($item->activeChildren as $child)
                                <a href="{{ $child->href }}" class="hv-mega-link" role="menuitem" target="{{ $child->safe_target }}" @if($child->safe_target === '_blank') rel="noopener noreferrer" @endif>
                                    @if($child->icon)
                                        <span class="hv-mega-link-icon">
                                            @include('partials.nav-icon', ['icon' => $child->icon, 'class' => 'h-5 w-5'])
                                        </span>
                                    @endif
                                    <span class="hv-mega-link-body">
                                        <span class="hv-mega-link-label">
                                            {{ $child->label }}
                                            @if($child->badge)
                                                <span class="hv-nav-badge hv-nav-badge-sm">{{ $child->badge }}</span>
                                            @endif
                                        </span>
                                        @if($child->description)
                                            <span class="hv-mega-link-desc">{{ $child->description }}</span>
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @if($item->hasPanel())
                        <aside class="hv-mega-panel" aria-label="Bilgilendirme">
                            <div class="hv-mega-panel-inner">
                                @if($item->icon)
                                    @include('partials.nav-icon', ['icon' => $item->icon, 'class' => 'h-8 w-8 text-hv-primary'])
                                @endif
                                @if($item->panel_title)
                                    <h3 class="hv-mega-panel-title">{{ $item->panel_title }}</h3>
                                @endif
                                @if($item->panel_text)
                                    <p class="hv-mega-panel-text">{{ $item->panel_text }}</p>
                                @endif
                                @if($item->panel_cta_label && $item->panel_cta_url)
                                    <a href="{{ $item->panel_cta_url }}" class="btn-primary mt-4 inline-flex text-sm">{{ $item->panel_cta_label }}</a>
                                @endif
                            </div>
                        </aside>
                    @endif
                </div>
            @else
                <div class="hv-nav-simple-menu">
                    @foreach($item->activeChildren as $child)
                        <a href="{{ $child->href }}" class="hv-nav-simple-link" role="menuitem" target="{{ $child->safe_target }}" @if($child->safe_target === '_blank') rel="noopener noreferrer" @endif>
                            @if($child->icon)
                                @include('partials.nav-icon', ['icon' => $child->icon, 'class' => 'h-4 w-4 shrink-0'])
                            @endif
                            <span>
                                <span class="block font-medium">{{ $child->label }}</span>
                                @if($child->description)
                                    <span class="block text-xs text-hv-muted">{{ $child->description }}</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@else
    <a href="{{ $item->href }}" class="nav-link inline-flex items-center gap-1.5 {{ request()->fullUrlIs(url($item->href)) ? 'nav-link-active' : '' }}" target="{{ $item->safe_target }}" @if($item->safe_target === '_blank') rel="noopener noreferrer" @endif>
        @if($item->icon)
            @include('partials.nav-icon', ['icon' => $item->icon, 'class' => 'h-4 w-4'])
        @endif
        {{ $item->label }}
        @if($item->badge)
            <span class="hv-nav-badge">{{ $item->badge }}</span>
        @endif
    </a>
@endif
