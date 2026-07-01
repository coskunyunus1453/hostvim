{{-- HostVim uyumlu mobil menü --}}
<aside x-data="{ open: false }"
       x-on:hv-toggle-drawer.window="open = !open"
       x-on:resize.window="if (window.innerWidth >= 1024) open = false"
       class="fixed inset-0 z-50 lg:hidden"
       x-cloak
       x-show="open"
       x-transition.opacity.duration.200>
    <div class="hv-sidebar-backdrop absolute inset-0" @click="open = false"></div>

    <div class="hv-sidebar-panel absolute inset-y-0 right-0 flex w-full max-w-sm flex-col border-l shadow-2xl"
         x-show="open"
         x-transition.duration.220.origin-right>
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-hv-border px-4">
            <x-landing.header-brand variant="classic" context="drawer" />
            <button type="button" @click="open = false" class="theme-toggle" aria-label="{{ landing_p('nav.close') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-4">
            @foreach ($landingHeaderNav as $drawerItem)
                <a href="{{ $drawerItem->resolvedHref() }}"
                   class="hv-sidebar-link"
                   @if ($drawerItem->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>
                    {{ $drawerItem->displayLabel() }}
                </a>
            @endforeach

            @if (! empty($landingEnabledLocales) && count($landingEnabledLocales) > 1)
                <div class="mt-4 border-t border-hv-border pt-4">
                    <label for="hv-lang-drawer" class="block px-1 pb-2 text-xs font-semibold text-hv-muted">{{ landing_t('nav.language') }}</label>
                    <select id="hv-lang-drawer"
                            class="w-full rounded-xl border border-hv-border bg-hv-elevated py-2.5 pl-3 pr-8 text-sm font-semibold text-hv-text"
                            onchange="(function(v){if(!v)return;var p=new window.URLSearchParams(window.location.search);p.set('lang',v);var q=p.toString();window.location=window.location.pathname+(q?'?'+q:'')+window.location.hash;})(this.value)">
                        @foreach ($landingEnabledLocales as $code)
                            <option value="{{ $code }}" @selected(($landingLocale ?? app()->getLocale()) === $code)>{{ landing_locale_tag($code) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="mt-4 border-t border-hv-border pt-4">
                <div class="mb-3 flex items-center justify-between px-1">
                    <span class="text-xs font-semibold text-hv-muted">{{ landing_t('nav.theme') }}</span>
                    <x-theme-toggle class="theme-toggle" />
                </div>
                @auth
                    <p class="px-1 text-xs text-hv-muted">{{ auth()->user()->name }}</p>
                    <form method="post" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn-ghost w-full text-sm">{{ landing_t('auth.header_sign_out') }}</button>
                    </form>
                @else
                    <div class="space-y-2">
                        <a href="{{ route('login', ['lang' => $landingLocale ?? app()->getLocale()]) }}" class="btn-ghost block w-full text-center text-sm">{{ landing_t('auth.header_sign_in') }}</a>
                        <a href="{{ route('register', ['lang' => $landingLocale ?? app()->getLocale()]) }}" class="btn-primary block w-full text-center text-sm">{{ landing_t('auth.header_sign_up') }}</a>
                    </div>
                @endauth
            </div>
        </nav>
    </div>
</aside>
