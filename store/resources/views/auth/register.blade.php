@extends('layouts.app')

@section('title', 'Kayıt Ol')

@section('content')
<section class="auth-page">
    <div class="auth-card">
        <div class="auth-card-header">
            <h1 class="text-xl font-bold text-hv-text">Hesap Oluştur</h1>
            <p class="mt-1 text-sm text-hv-muted">Sipariş ve faturalarınız için ücretsiz müşteri hesabı</p>
        </div>

        @if($errors->any())
            <div class="auth-alert auth-alert-error">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="auth-form">
            @csrf
            <div>
                <label for="name" class="auth-label">Ad Soyad</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name" class="auth-input">
            </div>
            <div>
                <label for="email" class="auth-label">E-posta</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="auth-input">
                @error('email')<p class="auth-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="auth-label">Telefon <span class="font-normal text-hv-muted">(opsiyonel)</span></label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" autocomplete="tel" class="auth-input">
            </div>
            <div>
                <label for="password" class="auth-label">Şifre</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="auth-input">
            </div>
            <div>
                <label for="password_confirmation" class="auth-label">Şifre (tekrar)</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="auth-input">
            </div>
            <button type="submit" class="btn-primary w-full">Kayıt ol</button>
        </form>

        <p class="auth-footer-text">
            Zaten hesabınız var mı? <a href="{{ route('login') }}" class="font-semibold text-hv-primary hover:underline">Giriş yapın</a>
        </p>
    </div>
</section>
@endsection
