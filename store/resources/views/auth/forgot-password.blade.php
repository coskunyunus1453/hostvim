@extends('layouts.app')

@section('title', 'Şifremi Unuttum')

@section('content')
<section class="auth-page">
    <div class="auth-card">
        <div class="auth-card-header">
            <h1 class="text-xl font-bold text-hv-text">Şifremi Unuttum</h1>
            <p class="mt-1 text-sm text-hv-muted">Kayıtlı e-posta adresinize sıfırlama bağlantısı gönderelim</p>
        </div>

        @if (session('status'))
            <div class="auth-alert auth-alert-success" role="status" id="password-reset-status">
                <p class="font-semibold">Talebiniz alındı</p>
                <p class="mt-1 text-sm">{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="auth-alert auth-alert-error" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="auth-form" id="forgot-password-form">
            @csrf
            <div>
                <label for="email" class="auth-label">E-posta</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="auth-input">
                @error('email')<p class="auth-error">{{ $message }}</p>@enderror
            </div>
            <x-captcha context="password" />
            <button type="submit" class="btn-primary w-full" id="forgot-password-submit">
                <span data-submit-label>Sıfırlama Bağlantısı Gönder</span>
                <span data-submit-loading class="hidden">Gönderiliyor…</span>
            </button>
        </form>

        <p class="auth-footer-text">
            <a href="{{ route('login') }}" class="font-semibold text-hv-primary hover:underline">← Giriş sayfasına dön</a>
        </p>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('forgot-password-form');
    const btn = document.getElementById('forgot-password-submit');
    if (!form || !btn) return;
    const label = btn.querySelector('[data-submit-label]');
    const loading = btn.querySelector('[data-submit-loading]');
    form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        label?.classList.add('hidden');
        loading?.classList.remove('hidden');
    });
    const status = document.getElementById('password-reset-status');
    if (status) {
        status.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
})();
</script>
@endpush
