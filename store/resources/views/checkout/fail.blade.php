@extends('layouts.app')

@section('title', 'Ödeme Başarısız')

@section('content')
<section class="py-20 text-center">
    <div class="mx-auto max-w-lg px-4">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-red-100 text-4xl text-red-600">✕</div>
        <h1 class="mt-6 text-3xl font-bold text-hv-text">Ödeme Tamamlanamadı</h1>
        <p class="mt-4 text-hv-muted">Sipariş: {{ $order->order_number }}. Lütfen tekrar deneyin veya destek ile iletişime geçin.</p>
        <a href="{{ route('checkout.index') }}" class="btn-primary mt-8 inline-flex">Tekrar Dene</a>
    </div>
</section>
@endsection
