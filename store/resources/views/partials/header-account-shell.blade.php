<header class="border-b border-hv-border bg-hv-surface/90">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            @include('partials.site-logo', ['height' => $siteLogoMobileHeight ?? 36, 'nameClass' => 'text-base font-bold'])
        </a>
        <div class="flex items-center gap-2">
            <span class="hidden text-sm text-hv-muted sm:inline">{{ auth()->user()->name }}</span>
        </div>
    </div>
</header>
