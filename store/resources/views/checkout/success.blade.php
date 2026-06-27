@extends('layouts.app')

@section('title', 'Ödeme Başarılı')

@section('content')
<section class="py-20 text-center">
    <div class="mx-auto max-w-lg px-4">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[#166534]/10 text-4xl text-[#166534]">✓</div>
        <h1 class="mt-6 text-3xl font-bold text-stone-900">Teşekkürler!</h1>
        <p class="mt-4 text-stone-600">Siparişiniz alındı. Sipariş numaranız: <strong>{{ $order->order_number }}</strong></p>
        <a href="{{ route('home') }}" class="btn-primary mt-8 inline-flex">Ana Sayfaya Dön</a>
    </div>
</section>
@endsection
