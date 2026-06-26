@extends('layouts.account', ['pageTitle' => 'Sipariş '.$order->order_number])

@section('account')
<div class="rounded-2xl border border-hv-border bg-hv-elevated p-6">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <p class="text-sm text-hv-muted">Sipariş no</p>
            <p class="text-xl font-bold text-hv-text">{{ $order->order_number }}</p>
            <p class="mt-2 text-sm text-hv-muted">{{ $order->created_at?->format('d.m.Y H:i') }}</p>
        </div>
        <div class="text-right">
            <p class="text-xl font-bold text-hv-primary">₺{{ number_format($order->total, 2, ',', '.') }}</p>
            <p class="text-sm text-hv-muted">Ödeme: {{ $order->payment_status }} · {{ $order->status }}</p>
        </div>
    </div>
    <ul class="mt-6 divide-y divide-hv-border border-t border-hv-border pt-4 text-sm">
        @foreach($order->items as $item)
            <li class="flex justify-between py-2">
                <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                <span>₺{{ number_format($item->unit_price * $item->quantity, 2, ',', '.') }}</span>
            </li>
        @endforeach
    </ul>
    <a href="{{ route('account.orders') }}" class="btn-ghost mt-6 inline-flex text-sm">← Siparişlere dön</a>
</div>
@endsection
