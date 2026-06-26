@extends('layouts.account', ['pageTitle' => 'Hesap Özeti'])

@section('account')
@if($panelError)
    <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $panelError }}</div>
@endif

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-hv-muted">Sipariş</p>
        <p class="mt-2 text-2xl font-bold text-hv-text">{{ $orders->count() }}</p>
        <p class="text-xs text-hv-muted">Son siparişler</p>
    </div>
    @if($panelSummary)
        <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-hv-muted">Hosting</p>
            <p class="mt-2 text-2xl font-bold text-hv-text">{{ $panelSummary['stats']['hosting_domains'] ?? 0 }}</p>
            <p class="text-xs text-hv-muted">Aktif site</p>
        </div>
        <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-hv-muted">Alan adı</p>
            <p class="mt-2 text-2xl font-bold text-hv-text">{{ $panelSummary['stats']['registered_domains'] ?? 0 }}</p>
            <p class="text-xs text-hv-muted">Kayıtlı domain</p>
        </div>
        <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-hv-muted">Bekleyen fatura</p>
            <p class="mt-2 text-2xl font-bold text-hv-text">{{ $panelSummary['stats']['unpaid_invoices'] ?? 0 }}</p>
            <a href="{{ route('account.invoices') }}" class="text-xs text-hv-primary hover:underline">Görüntüle</a>
        </div>
    @else
        <div class="sm:col-span-3 rounded-2xl border border-dashed border-hv-border bg-hv-surface p-6">
            <p class="font-medium text-hv-text">Hosting hesabınız henüz bağlanmadı</p>
            <p class="mt-1 text-sm text-hv-muted">İlk siparişiniz tamamlandığında hosting ve faturalar burada görünür.</p>
            <a href="{{ route('products.index') }}" class="btn-primary mt-4 inline-flex text-sm">Paketleri incele</a>
        </div>
    @endif
</div>

@if($panelSummary && !empty($panelSummary['hosting']))
    @php $h = $panelSummary['hosting']; @endphp
    <div class="mt-6 rounded-2xl border border-hv-border bg-hv-elevated p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-hv-text">Hosting kullanımı</h2>
            <form action="{{ route('account.hosting.panel') }}" method="POST">
                @csrf
                <button type="submit" class="btn-primary text-sm">Panele giriş</button>
            </form>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
                <p class="text-xs text-hv-muted">Disk</p>
                <p class="font-semibold text-hv-text">
                    {{ $h['disk_used_mb'] ?? 0 }} MB
                    @if(!empty($h['disk_limit_mb']))
                        / {{ $h['disk_limit_mb'] }} MB
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs text-hv-muted">Site sayısı</p>
                <p class="font-semibold text-hv-text">{{ $h['domain_count'] ?? 0 }}</p>
            </div>
            <div>
                <p class="text-xs text-hv-muted">Paket</p>
                <p class="font-semibold text-hv-text">{{ $panelSummary['user']['name'] ?? '-' }}</p>
            </div>
        </div>
    </div>
@endif

<div class="mt-6 rounded-2xl border border-hv-border bg-hv-elevated p-6">
    <h2 class="text-lg font-semibold text-hv-text">Son siparişler</h2>
    @if($orders->isEmpty())
        <p class="mt-3 text-sm text-hv-muted">Henüz siparişiniz yok.</p>
    @else
        <ul class="mt-4 divide-y divide-hv-border">
            @foreach($orders as $order)
                <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                    <div>
                        <p class="font-medium text-hv-text">{{ $order->order_number }}</p>
                        <p class="text-xs text-hv-muted">{{ $order->created_at?->format('d.m.Y H:i') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-hv-primary">₺{{ number_format($order->total, 2, ',', '.') }}</p>
                        <a href="{{ route('account.orders.show', $order) }}" class="text-xs text-hv-link hover:underline">Detay</a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
