{{-- Birleşik mobil drawer (non-neon) — tüm landing sayfalarında aynıdır. --}}
<aside x-data="{ open: false }"
       x-on:hv-toggle-drawer.window="open = !open"
       x-on:resize.window="if (window.innerWidth >= 1024) open = false"
       class="fixed inset-0 z-30 lg:hidden"
       x-cloak
       x-show="open"
       x-transition.opacity.duration.200>
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm dark:bg-slate-950/70" @click="open = false"></div>

    <div class="absolute inset-y-0 right-0 flex w-full max-w-xs flex-col border-l border-slate-200/90 bg-white/98 shadow-2xl dark:border-slate-800/90 dark:bg-slate-950/98"
         x-show="open"
         x-transition.duration.220.origin-right>
        <div class="flex h-14 shrink-0 items-center justify-between border-b border-slate-200/90 px-4 dark:border-slate-800/80">
            <x-landing.header-brand variant="classic" context="drawer" />
            <button type="button" @click="open = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300/90 text-slate-700 dark:border-slate-700 dark:text-slate-200">
                <span class="sr-only">{{ landing_p('nav.close') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M6 6l12 12M18 6L6 18" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-4 text-base">
            @foreach ($landingHeaderNav as $drawerItem)
                <a
                    href="{{ $drawerItem->resolvedHref() }}"
                    class="hv-drawer-link block rounded-xl px-3 py-2.5 font-medium text-slate-700 dark:text-slate-200 dark:hover:bg-slate-900/90"
                    @if ($drawerItem->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                >{{ $drawerItem->displayLabel() }}</a>
            @endforeach

            @if (! empty($landingEnabledLocales) && count($landingEnabledLocales) > 1)
                <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-700">
                    <label for="hv-lang-drawer" class="block px-3 pb-1 text-xs font-semibold text-slate-500">{{ landing_t('nav.language') }}</label>
                    <select id="hv-lang-drawer"
                            class="mx-3 w-[calc(100%-1.5rem)] rounded-xl border border-slate-300/90 bg-white/90 py-2 pl-3 pr-8 text-sm font-semibold text-slate-700 dark:border-slate-600 dark:bg-slate-900/80 dark:text-slate-200"
                            onchange="(function(v){if(!v)return;var p=new window.URLSearchParams(window.location.search);p.set('lang',v);var q=p.toString();window.location=window.location.pathname+(q?'?'+q:'')+window.location.hash;})(this.value)">
                        @foreach ($landingEnabledLocales as $code)
                            <option value="{{ $code }}" @selected(($landingLocale ?? app()->getLocale()) === $code)>{{ landing_locale_tag($code) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-700">
                @auth
                    <p class="px-3 text-xs text-slate-500">{{ auth()->user()->name }}</p>
                    <form method="post" action="{{ route('logout') }}" class="mt-2 px-3">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-slate-300 py-2 text-sm font-semibold text-slate-800 dark:border-slate-600 dark:text-slate-200">{{ landing_t('auth.header_sign_out') }}</button>
                    </form>
                @else
                    <div class="space-y-2 px-3">
                        <a href="{{ route('login', ['lang' => $landingLocale ?? app()->getLocale()]) }}" class="block rounded-xl border border-slate-300 px-3 py-2.5 text-center font-semibold text-slate-700 dark:border-slate-600 dark:text-slate-200">{{ landing_t('auth.header_sign_in') }}</a>
                        <a href="{{ route('register', ['lang' => $landingLocale ?? app()->getLocale()]) }}" class="block rounded-xl bg-[rgb(var(--hv-brand-600)/1)] px-3 py-2.5 text-center text-sm font-semibold text-white">{{ landing_t('auth.header_sign_up') }}</a>
                    </div>
                @endauth
            </div>
        </nav>
    </div>
</aside>
