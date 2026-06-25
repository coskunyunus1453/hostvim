@extends('layouts.app')

@section('title', 'Oturum süresi doldu')

@section('content')
<section class="auth-page">
    <div class="auth-card">
        <div class="auth-alert auth-alert-error" role="alert">
            <p class="font-semibold">Form gönderilemedi</p>
            <p class="mt-1 text-sm">Güvenlik doğrulaması başarısız oldu (oturum süresi dolmuş veya sayfa önbellekten yüklendi). Lütfen sayfayı yenileyip tekrar deneyin.</p>
        </div>
        <p class="mt-4 text-center">
            <a href="{{ route('password.request') }}" class="btn-primary inline-flex">Şifremi unuttum sayfasını yenile</a>
        </p>
        <p class="auth-footer-text mt-4">
            <a href="{{ route('login') }}" class="font-semibold text-hv-primary hover:underline">← Giriş sayfasına dön</a>
        </p>
    </div>
</section>
@endsection
