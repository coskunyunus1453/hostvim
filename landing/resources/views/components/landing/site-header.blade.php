{{-- HostVim uyumlu header (panelze.com / klasik tema) --}}
<header class="hv-header hv-header-glass hv-header-sticky">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 lg:px-8">
        <x-landing.header-brand variant="classic" />

        <nav class="hidden items-center gap-5 xl:gap-6 lg:flex" aria-label="{{ landing_p('nav.menu') }}">
            <x-landing.nav-menu :items="$landingHeaderNav" link-class="nav-link" />
        </nav>

        <div class="flex items-center gap-1 sm:gap-2">
            @if (! empty($landingEnabledLocales) && count($landingEnabledLocales) > 1)
                <div class="relative hidden sm:block">
                    <label for="hv-lang-site" class="sr-only">{{ landing_t('nav.language') }}</label>
                    <select id="hv-lang-site"
                            class="rounded-xl border border-hv-border bg-hv-elevated py-2 pl-3 pr-8 text-xs font-semibold text-hv-text outline-none focus:border-hv-primary"
                            onchange="(function(v){if(!v)return;var p=new window.URLSearchParams(window.location.search);p.set('lang',v);var q=p.toString();window.location=window.location.pathname+(q?'?'+q:'')+window.location.hash;})(this.value)">
                        @foreach ($landingEnabledLocales as $code)
                            <option value="{{ $code }}" @selected(($landingLocale ?? app()->getLocale()) === $code)>
                                {{ landing_locale_tag($code) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <x-theme-toggle class="theme-toggle hidden sm:inline-flex" />

            <div class="hidden items-center gap-2 lg:flex">
                @auth
                    <span class="max-w-[10rem] truncate text-xs font-medium text-hv-muted" title="{{ auth()->user()->email }}">{{ auth()->user()->name }}</span>
                    <form method="post" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-ghost text-xs">{{ landing_t('auth.header_sign_out') }}</button>
                    </form>
                @else
                    <a href="{{ route('login', ['lang' => $landingLocale ?? app()->getLocale()]) }}" class="btn-ghost text-xs">
                        {{ landing_t('auth.header_sign_in') }}
                    </a>
                    <a href="{{ route('register', ['lang' => $landingLocale ?? app()->getLocale()]) }}" class="btn-primary !px-4 !py-2 text-xs">
                        {{ landing_t('auth.header_sign_up') }}
                    </a>
                @endauth
            </div>

            <button type="button"
                    x-data
                    @click="$dispatch('hv-toggle-drawer')"
                    class="theme-toggle lg:hidden"
                    aria-label="{{ landing_p('nav.menu') }}">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>
</header>
