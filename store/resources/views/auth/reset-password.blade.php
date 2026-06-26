@extends('layouts.app')

@section('title', 'Yeni Şifre Belirle')

@section('content')
<section class="auth-page">
    <div class="auth-card">
        <div class="auth-card-header">
            <h1 class="text-xl font-bold text-hv-text">Yeni Şifre Belirle</h1>
            <p class="mt-1 text-sm text-hv-muted">Hesabınız için güçlü bir şifre oluşturun</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="auth-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="auth-label">E-posta</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username"
                    @if($email !== '') readonly @endif
                    class="auth-input @if($email !== '') opacity-90 @endif">
                @error('email')<p class="auth-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="auth-label">Yeni şifre</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="auth-input">
                @error('password')<p class="auth-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="auth-label">Yeni şifre (tekrar)</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="auth-input">
            </div>
            <button type="submit" class="btn-primary w-full">Şifreyi Kaydet</button>
        </form>

        <p class="auth-footer-text">
            <a href="{{ route('login') }}" class="font-semibold text-hv-primary hover:underline">← Giriş sayfasına dön</a>
        </p>
    </div>
</section>
@endsection
