@extends('layouts.app')

@section('title', $code.' — '.$title)
@section('meta_description', $description)

@push('head')
    <meta name="robots" content="noindex,nofollow">
    <style>
        .hv-error-wrap {
            --error-accent: {{ $accent ?? '#f97316' }};
            --error-accent-dark: {{ $accentDark ?? '#c2410c' }};
            position: relative;
            display: grid;
            min-height: clamp(520px, 68vh, 760px);
            place-items: center;
            overflow: hidden;
            padding: 64px 16px 88px;
            background:
                radial-gradient(circle at 15% 15%, color-mix(in srgb, var(--error-accent) 16%, transparent), transparent 32rem),
                radial-gradient(circle at 90% 85%, color-mix(in srgb, var(--hv-secondary) 10%, transparent), transparent 30rem);
        }
        .hv-error-card {
            position: relative;
            width: min(840px, 100%);
            overflow: hidden;
            padding: clamp(32px, 7vw, 76px);
            border: 1px solid var(--hv-border);
            border-radius: 32px;
            background: color-mix(in srgb, var(--hv-surface) 88%, transparent);
            box-shadow: 0 30px 80px color-mix(in srgb, var(--hv-text) 14%, transparent);
            backdrop-filter: blur(18px);
        }
        .hv-error-card::after {
            content: "{{ $code }}";
            position: absolute;
            right: -18px;
            bottom: -50px;
            color: color-mix(in srgb, var(--error-accent) 8%, transparent);
            font-size: clamp(140px, 30vw, 290px);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -.1em;
            pointer-events: none;
        }
        .hv-error-content { position: relative; z-index: 1; max-width: 590px; }
        .hv-error-icon {
            display: grid;
            width: 58px;
            height: 58px;
            margin-bottom: 28px;
            place-items: center;
            border-radius: 18px;
            color: #fff;
            background: linear-gradient(135deg, var(--error-accent), var(--error-accent-dark));
            box-shadow: 0 14px 36px color-mix(in srgb, var(--error-accent) 28%, transparent);
        }
        .hv-error-code {
            margin: 0 0 10px;
            color: var(--error-accent);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        .hv-error-title {
            margin: 0;
            max-width: 650px;
            color: var(--hv-text);
            font-size: clamp(34px, 6vw, 62px);
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -.055em;
        }
        .hv-error-lead {
            margin: 22px 0 0;
            max-width: 560px;
            color: var(--hv-text-muted);
            font-size: clamp(16px, 2vw, 19px);
            line-height: 1.75;
        }
        .hv-error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 34px;
        }
        .hv-error-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 20px;
            border: 1px solid var(--hv-border);
            border-radius: 14px;
            font-size: 14px;
            font-weight: 750;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .hv-error-btn:hover { transform: translateY(-2px); }
        .hv-error-btn-primary {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(135deg, var(--error-accent), var(--error-accent-dark));
            box-shadow: 0 12px 28px color-mix(in srgb, var(--error-accent) 24%, transparent);
        }
        .hv-error-btn-secondary {
            color: var(--hv-text);
            background: color-mix(in srgb, var(--hv-surface) 92%, transparent);
        }
        @media (max-width: 640px) {
            .hv-error-wrap { min-height: 560px; padding: 40px 16px 64px; }
            .hv-error-card { border-radius: 24px; }
            .hv-error-actions { flex-direction: column; }
            .hv-error-btn { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) {
            .hv-error-btn { transition: none; }
        }
    </style>
@endpush

@section('content')
    <section class="hv-error-wrap" aria-labelledby="error-title">
        <div class="hv-error-card">
            <div class="hv-error-content">
                <div class="hv-error-icon" aria-hidden="true">{!! $icon !!}</div>
                <p class="hv-error-code">Hata kodu {{ $code }}</p>
                <h1 id="error-title" class="hv-error-title">{{ $title }}</h1>
                <p class="hv-error-lead">{{ $message }}</p>
                <div class="hv-error-actions">
                    <a class="hv-error-btn hv-error-btn-primary" href="{{ route('home') }}">
                        Ana sayfaya dön&nbsp; →
                    </a>
                    @if(($showBack ?? true))
                        <a class="hv-error-btn hv-error-btn-secondary" href="javascript:history.back()">Önceki sayfaya dön</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
