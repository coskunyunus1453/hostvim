@props([
    'title' => null,
    'description' => null,
    'canonicalUrl' => null,
    'ogImage' => null,
    'schemaJsonLd' => null,
])
@php
    $title = $title ?? landing_p('home.meta_title');
    $description = $description ?? landing_p('home.meta_description');
    $canonicalEffective = $canonicalUrl ?? landing_localized_url(app()->getLocale());
    $ogPageUrl = $canonicalEffective;
    $twitterCard = $ogImage ? 'summary_large_image' : 'summary';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth {{ $landingThemeClass ?? 'hv-theme-orange' }}" @if(! empty($landingThemeInlineStyle)) style="{{ $landingThemeInlineStyle }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonicalEffective }}">

    <meta property="og:locale" content="{{ landing_og_locale_tag(app()->getLocale()) }}">
    <meta property="og:site_name" content="{{ landing_p('brand.name') }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $ogPageUrl }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    <meta name="twitter:card" content="{{ $twitterCard }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
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

    <style>
        [x-cloak] { display: none !important; }
    </style>
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
            <div class="hv-bg-blob absolute -top-32 left-1/2 h-[22rem] w-[40rem] -translate-x-1/2 rounded-full blur-3xl"></div>
            <div class="hv-decor-blob-sm absolute -top-10 right-0 h-40 w-56 rounded-full blur-3xl"></div>
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

    @if (($landingThemeClass ?? '') === 'hv-theme-neon')
        <x-landing.neon-drawer />
    @else
        <x-landing.site-drawer />
    @endif

    <main class="relative z-10 flex-1">
        {{ $slot }}
    </main>

    @if (($landingThemeClass ?? '') === 'hv-theme-neon')
        <x-landing.neon-footer />
    @else
        <x-landing.site-footer />
    @endif

    <x-landing.body-extras />
</body>
</html>
