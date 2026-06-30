<div id="mobile-sidebar" class="hv-sidebar lg:hidden" aria-hidden="true">
    <div id="mobile-sidebar-backdrop" class="hv-sidebar-backdrop" aria-hidden="true"></div>

    <aside id="mobile-sidebar-panel" class="hv-sidebar-panel" role="dialog" aria-modal="true" aria-label="Navigasyon menüsü">
        <div class="flex items-center justify-between border-b border-hv-border px-5 py-4">
            @include('partials.site-logo', ['height' => $siteLogoMobileHeight ?? 36, 'nameClass' => 'text-lg'])
            <button type="button" id="mobile-sidebar-close" class="theme-toggle" aria-label="Menüyü kapat">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="hv-sidebar-nav" aria-label="Mobil menü">
            @if($headerMenu)
                @foreach($headerMenu->activeRootItems as $item)
                    @if($item->isDropdown() && $item->activeChildren->isNotEmpty())
                        <details class="hv-sidebar-group">
                            <summary class="hv-sidebar-link hv-sidebar-summary">
                                <span class="flex items-center gap-3">
                                    @if($item->icon)
                                        @include('partials.nav-icon', ['icon' => $item->icon, 'class' => 'h-5 w-5'])
                                    @else
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                    @endif
                                    {{ $item->label }}
                                </span>
                                <svg class="hv-sidebar-chevron h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="hv-sidebar-sub">
                                @foreach($item->activeChildren as $child)
                                    <a href="{{ $child->href }}" class="hv-sidebar-sublink {{ request()->fullUrlIs(url($child->href)) ? 'hv-sidebar-sublink-active' : '' }}" target="{{ $child->safe_target }}" @if($child->safe_target === '_blank') rel="noopener noreferrer" @endif>
                                        @if($child->icon)@include('partials.nav-icon', ['icon' => $child->icon, 'class' => 'h-4 w-4 inline-block mr-2 align-middle'])@endif
                                        {{ $child->label }}
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @else
                        <a href="{{ $item->href }}" class="hv-sidebar-link {{ ($item->href !== '#' && request()->fullUrlIs(url($item->href))) ? 'hv-sidebar-link-active' : '' }}" target="{{ $item->safe_target }}" @if($item->safe_target === '_blank') rel="noopener noreferrer" @endif>
                            @if($item->icon)
                                @include('partials.nav-icon', ['icon' => $item->icon, 'class' => 'h-5 w-5'])
                            @else
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            @endif
                            {{ $item->label }}
                        </a>
                    @endif
                @endforeach
            @endif

            <div class="my-2 border-t border-hv-border"></div>

            @if(auth()->user()?->is_admin)
                <a href="{{ url('/admin') }}" class="hv-sidebar-link">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Yönetim Paneli
                </a>
            @elseif($isCustomerLoggedIn ?? false)
                <a href="{{ $accountUrl ?? route('account.dashboard') }}" class="hv-sidebar-link {{ request()->routeIs('account.*') ? 'hv-sidebar-link-active' : '' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Hesabım
                </a>
            @else
                <a href="{{ route('login') }}" class="hv-sidebar-link {{ request()->routeIs('login') ? 'hv-sidebar-link-active' : '' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    Giriş
                </a>
                @if(filter_var(app(\App\Services\SettingsService::class)->get('registration_enabled', '0'), FILTER_VALIDATE_BOOLEAN))
                <a href="{{ route('register') }}" class="hv-sidebar-link {{ request()->routeIs('register') ? 'hv-sidebar-link-active' : '' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    Kayıt Ol
                </a>
                @endif
            @endif
        </nav>

        <div class="mt-auto border-t border-hv-border p-5">
            <a href="{{ route('products.index') }}" class="btn-primary w-full justify-center">Paketleri İncele</a>
            <a href="{{ route('cart.index') }}" class="mt-3 flex items-center justify-center gap-2 rounded-xl border border-hv-border px-4 py-3 text-sm font-semibold text-hv-text transition hover:bg-hv-surface">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Sepetim
            </a>
        </div>
    </aside>
</div>
