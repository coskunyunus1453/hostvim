@props([
    'title' => null,
    'description' => null,
    'canonicalUrl' => null,
    'ogTitle' => null,
    'ogDescription' => null,
    'ogImage' => null,
    'ogType' => 'website',
    'robotsContent' => null,
    'schemaJsonLd' => null,
])
@php
    $pageTitle = $title ?? landing_p('brand.name');
    $metaDescription = $description;
    $ogPageTitle = $ogTitle ?? $pageTitle;
    $ogPageDescription = $ogDescription ?? $metaDescription;
    $canonicalEffective = $canonicalUrl ?? landing_localized_url(app()->getLocale());
    $ogPageUrl = $canonicalEffective;
    $twitterCard = $ogImage ? 'summary_large_image' : 'summary';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth {{ $landingThemeClass ?? 'hv-theme-orange' }} {{ $landingShellClass ?? '' }}" data-hv-preset="hostvim-main" data-hv-shell="classic" @if(! empty($landingThemeInlineStyle)) style="{{ $landingThemeInlineStyle }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $pageTitle }}</title>
    @if ($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    <link rel="canonical" href="{{ $canonicalEffective }}">
    @if ($robotsContent)
        <meta name="robots" content="{{ $robotsContent }}">
    @endif

    <meta property="og:locale" content="{{ landing_og_locale_tag(app()->getLocale()) }}">
    <meta property="og:site_name" content="{{ landing_p('brand.name') }}">
    <meta property="og:title" content="{{ $ogPageTitle }}">
    @if ($ogPageDescription)
        <meta property="og:description" content="{{ $ogPageDescription }}">
    @endif
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $ogPageUrl }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    <meta name="twitter:card" content="{{ $twitterCard }}">
    <meta name="twitter:title" content="{{ $ogPageTitle }}">
    @if ($ogPageDescription)
        <meta name="twitter:description" content="{{ $ogPageDescription }}">
    @endif
    @if ($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    <x-landing.head-extras />

    @if ($schemaJsonLd)
        <script type="application/ld+json">{!! $schemaJsonLd !!}</script>
    @endif

    <x-landing.seo-locale />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function () {
            var t = localStorage.getItem('hv-theme');
            if (t === 'dark') document.documentElement.classList.add('dark');
            else if (t === 'light') document.documentElement.classList.remove('dark');
            else if (window.matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.classList.add('dark');
        })();
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="hv-body min-h-full flex flex-col text-base">

    @if (($landingThemeClass ?? '') === 'hv-theme-neon')
        <div class="hv-neon-backdrop pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <div class="hv-neon-backdrop-grid absolute inset-0 opacity-[0.45] dark:opacity-[0.35]"></div>
            <div class="hv-neon-backdrop-orb hv-neon-backdrop-orb-a absolute -left-32 top-0 h-[28rem] w-[28rem] rounded-full blur-3xl"></div>
            <div class="hv-neon-backdrop-orb hv-neon-backdrop-orb-b absolute -right-24 top-1/3 h-[22rem] w-[22rem] rounded-full blur-3xl"></div>
        </div>
    @else
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden {{ $landingGraphicMotifClass ?? '' }}">
            <div class="hv-bg-blob absolute -top-28 left-1/2 h-[20rem] w-[36rem] -translate-x-1/2 rounded-full blur-3xl"></div>
        </div>
    @endif

    @if (($landingThemeClass ?? '') === 'hv-theme-neon')
        <x-landing.neon-header />
    @else
        <x-landing.site-header />
    @endif

    @if (session('error'))
        <div class="relative z-25 border-b border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/50 dark:text-rose-200">
            <div class="hv-container">{{ session('error') }}</div>
        </div>
    @endif

    @if (session('status'))
        <div class="relative z-25 border-b border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-100">
            <div class="hv-container">{{ session('status') }}</div>
        </div>
    @endif

    <main class="relative z-10 flex-1 py-10 sm:py-12">
        {{ $slot }}
    </main>

    @if (($landingThemeClass ?? '') === 'hv-theme-neon')
        <x-landing.neon-footer />
    @else
        <x-landing.site-footer />
    @endif

    @if (($landingThemeClass ?? '') === 'hv-theme-neon')
        <x-landing.neon-drawer />
    @else
        <x-landing.site-drawer />
    @endif

    <x-landing.body-extras />
</body>
</html>
