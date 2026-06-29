{{-- Birleşik klasik (non-neon) header — tüm landing sayfalarında aynıdır. --}}
<header class="relative z-20 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/80">
    <div class="hv-container">
        <div class="flex h-[4.25rem] items-center justify-between gap-4">
            <x-landing.header-brand variant="classic" />

            <nav class="hidden items-center gap-7 text-[0.95rem] font-medium lg:flex">
                <x-landing.nav-menu :items="$landingHeaderNav" link-class="hv-muted-nav font-medium" />
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                @if (! empty($landingEnabledLocales) && count($landingEnabledLocales) > 1)
                    <div class="relative">
                        <label for="hv-lang-site" class="sr-only">{{ landing_t('nav.language') }}</label>
                        <select id="hv-lang-site"
                                class="rounded-full border border-slate-300/90 bg-white/90 py-1.5 pl-3 pr-8 text-xs font-semibold text-slate-700 dark:border-slate-600 dark:bg-slate-900/80 dark:text-slate-200"
                                onchange="(function(v){if(!v)return;var p=new window.URLSearchParams(window.location.search);p.set('lang',v);var q=p.toString();window.location=window.location.pathname+(q?'?'+q:'')+window.location.hash;})(this.value)">
                            @foreach ($landingEnabledLocales as $code)
                                <option value="{{ $code }}" @selected(($landingLocale ?? app()->getLocale()) === $code)>
                                    {{ landing_locale_tag($code) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <x-theme-toggle class="inline-flex" />

                <div class="flex items-center gap-2">
                    @auth
                        <span class="max-w-[10rem] truncate text-xs font-medium text-slate-600 dark:text-slate-300" title="{{ auth()->user()->email }}">{{ auth()->user()->name }}</span>
                        <form method="post" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="rounded-full border border-slate-300/90 px-3.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ landing_t('auth.header_sign_out') }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login', ['lang' => $landingLocale ?? app()->getLocale()]) }}" class="rounded-full px-3.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                            {{ landing_t('auth.header_sign_in') }}
                        </a>
                        <a href="{{ route('register', ['lang' => $landingLocale ?? app()->getLocale()]) }}" class="rounded-full bg-[rgb(var(--hv-brand-600)/1)] px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:opacity-95">
                            {{ landing_t('auth.header_sign_up') }}
                        </a>
                    @endauth
                </div>
            </div>

            <button type="button"
                    x-data
                    @click="$dispatch('hv-toggle-drawer')"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300/90 bg-white/90 text-slate-800 lg:hidden dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100">
                <span class="sr-only">{{ landing_p('nav.menu') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M4 6h16M4 12h16M4 18h16" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>
</header>
