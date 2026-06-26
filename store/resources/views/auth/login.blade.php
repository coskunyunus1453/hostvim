@extends('layouts.app')

@section('title', 'Giriş Yap')

@section('content')
<section class="auth-page">
    <div class="auth-card">
        <div class="auth-card-header">
            <h1 class="text-xl font-bold text-hv-text">Giriş Yap</h1>
            <p class="mt-1 text-sm text-hv-muted">Alan adı, hosting ve faturalarınızı yönetin</p>
        </div>

        @if (session('success'))
            <div class="auth-alert auth-alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf
            <div>
                <label for="email" class="auth-label">E-posta</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="auth-input">
                @error('email')<p class="auth-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="auth-label">Şifre</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="auth-input">
                @error('password')<p class="auth-error">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center justify-between gap-4 text-sm">
                <label class="flex items-center gap-2 text-hv-muted">
                    <input type="checkbox" name="remember" class="rounded border-hv-border"> Beni hatırla
                </label>
                <a href="{{ route('password.request') }}" class="font-semibold text-hv-primary hover:underline">Şifremi unuttum</a>
            </div>
            <button type="submit" class="btn-primary w-full">Giriş</button>
        </form>

        <p class="auth-footer-text">
            Hesabınız yok mu? <a href="{{ route('register') }}" class="font-semibold text-hv-primary hover:underline">Kayıt olun</a>
        </p>
    </div>
</section>
@endsection
