@extends('layouts.account', ['pageTitle' => 'Siparişlerim'])

@section('account')
<div class="rounded-2xl border border-hv-border bg-hv-elevated overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-hv-surface text-hv-muted">
            <tr>
                <th class="px-4 py-3 text-left">Sipariş</th>
                <th class="px-4 py-3 text-left">Tarih</th>
                <th class="px-4 py-3 text-left">Ödeme</th>
                <th class="px-4 py-3 text-left">Tutar</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr class="border-t border-hv-border/60">
                    <td class="px-4 py-3 font-medium text-hv-text">{{ $order->order_number }}</td>
                    <td class="px-4 py-3 text-hv-muted">{{ $order->created_at?->format('d.m.Y') }}</td>
                    <td class="px-4 py-3">{{ $order->payment_status }}</td>
                    <td class="px-4 py-3">₺{{ number_format($order->total, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('account.orders.show', $order) }}" class="text-hv-primary hover:underline">Detay</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-hv-muted">Sipariş bulunamadı.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
