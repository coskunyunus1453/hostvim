<!DOCTYPE html>
<html lang="tr" data-hv-preset="{{ $themePresetId ?? 'hostvim-main' }}" data-hv-shell="{{ $themeShell ?? 'classic' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-default" content="{{ $themeDefaultMode ?? 'system' }}">
    @include('partials.seo-head')
    @if(!empty($siteFaviconUrl))
        <link rel="icon" href="{{ $siteFaviconUrl }}" type="image/png">
        <link rel="shortcut icon" href="{{ $siteFaviconUrl }}">
    @endif
    @include('partials.theme-styles')
    @unless($accountLayoutShell ?? false)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        @php $fontHref = $themeFontUrl ?? 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap'; @endphp
        <link rel="preload" as="style" href="{{ $fontHref }}" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="{{ $fontHref }}"></noscript>
    @else
        <style>html,body{font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}</style>
    @endunless
    @include('partials.schema-jsonld')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-hv-bg text-hv-text antialiased">
    @unless($minimalLayoutShell ?? false)
        @include('partials.campaign-flash-bar')
    @endunless
    @if($accountLayoutShell ?? false)
        @include('partials.header-account-shell')
    @else
        @include('partials.header')
        @include('partials.breadcrumbs')
    @endif

    @if(session('success'))
        <div id="flash-success" role="alert" class="flash-message flash-success fixed top-20 right-4 z-50 max-w-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div id="flash-error" role="alert" class="flash-message flash-error fixed top-20 right-4 z-50 max-w-sm">
            {{ session('error') }}
        </div>
    @endif

    <main>@yield('content')</main>

    @unless($accountLayoutShell ?? false)
        @include('partials.footer')
    @endunless
    @unless($minimalLayoutShell ?? false)
        @include('partials.campaign-popup')
    @endunless
    @stack('scripts')
</body>
</html>
