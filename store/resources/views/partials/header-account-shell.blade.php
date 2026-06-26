<header class="border-b border-hv-border bg-hv-surface/90">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            @include('partials.site-logo', ['height' => $siteLogoMobileHeight ?? 36, 'nameClass' => 'text-base font-bold'])
        </a>
        <div class="flex items-center gap-2">
            @if($themeToggleEnabled ?? true)
                <button type="button" id="theme-toggle" class="theme-toggle" aria-label="Tema değiştir">
                    <svg class="h-5 w-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg class="h-5 w-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
            @endif
            <span class="hidden text-sm text-hv-muted sm:inline">{{ auth()->user()->name }}</span>
        </div>
    </div>
</header>
