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
    <div class="mt-4 space-y-1 border-t border-hv-border pt-4 text-sm">
        <div class="flex justify-between text-hv-muted"><span>Ara toplam</span><span>₺{{ number_format($order->subtotal, 2, ',', '.') }}</span></div>
        @if((float) $order->discount_amount > 0)
            <div class="flex justify-between text-green-700"><span>İndirim</span><span>-₺{{ number_format($order->discount_amount, 2, ',', '.') }}</span></div>
        @endif
        @if((float) $order->tax_rate > 0)
            <div class="flex justify-between text-hv-muted">
                <span>KDV (%{{ rtrim(rtrim(number_format($order->tax_rate, 2, ',', '.'), '0'), ',') }}){{ (float) $order->subtotal >= (float) $order->total ? ' dahil' : '' }}</span>
                <span>₺{{ number_format($order->tax_amount, 2, ',', '.') }}</span>
            </div>
        @endif
        <div class="flex justify-between pt-1 text-base font-bold"><span>Toplam</span><span class="text-hv-primary">₺{{ number_format($order->total, 2, ',', '.') }}</span></div>
    </div>
    <div class="mt-6 flex flex-wrap items-center gap-3">
        <a href="{{ route('account.orders') }}" class="btn-ghost inline-flex text-sm">← Siparişlere dön</a>
        @if($order->invoice)
            <a href="{{ route('account.einvoice.pdf', $order->invoice) }}" target="_blank"
               class="btn-primary inline-flex items-center gap-2 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M4 6h16M4 6a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2"/></svg>
                Faturayı indir (PDF)
            </a>
        @endif
    </div>
</div>
@endsection
