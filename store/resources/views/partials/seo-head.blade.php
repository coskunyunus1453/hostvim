@if(!empty($seo))
    <title>{{ $seo['title'] ?? $siteName }}</title>
    <meta name="description" content="{{ $seo['description'] ?? '' }}">
    @if(!empty($seo['keywords']))
        <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif
    <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}">
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:locale" content="{{ $seo['locale'] ?? 'tr_TR' }}">
    <meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">
    <meta property="og:title" content="{{ $seo['title'] ?? $siteName }}">
    <meta property="og:description" content="{{ $seo['description'] ?? '' }}">
    <meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
    <meta property="og:site_name" content="{{ $seo['site_name'] ?? $siteName }}">
    @if(!empty($seo['og_image']))
        <meta property="og:image" content="{{ $seo['og_image'] }}">
    @endif
    @if(!empty($seo['published_at']))
        <meta property="article:published_time" content="{{ $seo['published_at'] }}">
    @endif
    @if(!empty($seo['modified_at']))
        <meta property="article:modified_time" content="{{ $seo['modified_at'] }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ $seo['twitter_card'] ?? 'summary_large_image' }}">
    <meta name="twitter:title" content="{{ $seo['title'] ?? $siteName }}">
    <meta name="twitter:description" content="{{ $seo['description'] ?? '' }}">
    @if(!empty($seo['og_image']))
        <meta name="twitter:image" content="{{ $seo['og_image'] }}">
    @endif

    @if(!empty($siteSettings['seo_google_verification']))
        <meta name="google-site-verification" content="{{ $siteSettings['seo_google_verification'] }}">
    @endif
    @if(!empty($siteSettings['seo_bing_verification']))
        <meta name="msvalidate.01" content="{{ $siteSettings['seo_bing_verification'] }}">
    @endif
@else
    @php
        $isPrivatePage = request()->routeIs(['cart.*', 'checkout.*', 'payment.*', 'account.*', 'login', 'register']);
    @endphp
    <title>@yield('title', $siteName) — Güvenilir Hosting & Sunucu Çözümleri</title>
    <meta name="description" content="@yield('meta_description', $siteSettings['meta_description'] ?? '')">
    @if($isPrivatePage)
        <meta name="robots" content="noindex,nofollow">
        <link rel="canonical" href="{{ url()->current() }}">
    @endif
@endif
