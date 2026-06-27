@extends('layouts.app')

@section('title', 'Havale / EFT Talimatları')

@section('content')
<section class="py-16">
    <div class="mx-auto max-w-2xl px-4">
        <div class="rounded-2xl border border-[#166534]/20 bg-[#166534]/5 p-8">
            <h1 class="text-2xl font-bold text-stone-900">Havale / EFT ile Ödeme</h1>
            <p class="mt-2 text-stone-600">Sipariş No: <strong>{{ $order->order_number }}</strong> — Tutar: <strong>₺{{ number_format($order->total, 2, ',', '.') }}</strong></p>
            <div class="prose-hostvim mt-6">{!! nl2br(e($paymentMethod->instructions ?? 'Banka bilgileri admin panelinden ayarlanır.')) !!}</div>
            <p class="mt-6 text-sm text-stone-500">Açıklama kısmına sipariş numaranızı yazmayı unutmayın.</p>
            <a href="{{ route('home') }}" class="btn-primary mt-8 inline-flex">Ana Sayfaya Dön</a>
        </div>
    </div>
</section>
@endsection
