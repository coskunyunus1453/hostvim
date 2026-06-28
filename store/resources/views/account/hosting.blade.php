@extends('layouts.account', ['pageTitle' => 'Hostinglerim'])

@section('account')
@if(session('success'))
    <div class="mb-4 rounded-xl border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
@if(!$linked)
    <div class="rounded-2xl border border-dashed border-hv-border bg-hv-surface p-8 text-center">
        <p class="font-medium text-hv-text">Aktif hosting hesabınız yok</p>
        <p class="mt-2 text-sm text-hv-muted">Hosting paketi satın aldığınızda sitelerinizi buradan yönetip panele geçebilirsiniz.</p>
        <a href="{{ route('products.index') }}" class="btn-primary mt-4 inline-flex">Hosting paketleri</a>
    </div>
@else
    @if(!empty($error))
        <div class="mb-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $error }}</div>
    @endif

    @php
        $summary = $hosting['summary'] ?? [];
        $package = $hosting['package'] ?? null;
        $domains = $hosting['domains'] ?? [];
    @endphp

    <div class="rounded-2xl border border-hv-border bg-hv-elevated p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-hv-text">{{ $package['name'] ?? 'Hosting paketi' }}</h2>
                <p class="text-sm text-hv-muted">Teknik yönetim için hosting paneline geçin</p>
            </div>
            <form action="{{ route('account.hosting.panel') }}" method="POST">
                @csrf
                <button type="submit" class="btn-primary">Panele giriş</button>
            </form>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl bg-hv-surface p-4">
                <p class="text-xs uppercase text-hv-muted">Disk kullanımı</p>
                <p class="mt-1 text-xl font-bold text-hv-text">
                    {{ $summary['disk_used_mb'] ?? 0 }} MB
                    @if(!empty($summary['disk_limit_mb']))
                        <span class="text-sm font-normal text-hv-muted">/ {{ $summary['disk_limit_mb'] }} MB</span>
                    @endif
                </p>
            </div>
            <div class="rounded-xl bg-hv-surface p-4">
                <p class="text-xs uppercase text-hv-muted">Site sayısı</p>
                <p class="mt-1 text-xl font-bold text-hv-text">{{ $summary['domain_count'] ?? 0 }}</p>
            </div>
            <div class="rounded-xl bg-hv-surface p-4">
                <p class="text-xs uppercase text-hv-muted">Paket limiti</p>
                <p class="mt-1 text-sm font-medium text-hv-text">
                    @if(!empty($package['max_domains']))
                        En fazla {{ $package['max_domains'] }} domain
                    @else
                        Sınırsız domain
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-hv-border bg-hv-elevated p-6">
        <h2 class="text-lg font-semibold text-hv-text">Siteleriniz</h2>
        @if(empty($domains))
            <p class="mt-3 text-sm text-hv-muted">Henüz site oluşturulmamış.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-hv-border text-left text-hv-muted">
                            <th class="py-2 pr-4">Domain</th>
                            <th class="py-2 pr-4">Durum</th>
                            <th class="py-2 pr-4">Disk</th>
                            <th class="py-2">Trafik (örnek)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($domains as $d)
                            <tr class="border-b border-hv-border/60">
                                <td class="py-3 pr-4 font-medium text-hv-text">{{ $d['name'] ?? '' }}</td>
                                <td class="py-3 pr-4">{{ $d['status'] ?? '-' }}</td>
                                <td class="py-3 pr-4">{{ $d['disk_mb'] ?? 0 }} MB</td>
                                <td class="py-3">{{ $d['bandwidth_mb'] ?? 0 }} MB</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif

{{-- Hosting devri --}}
@if(!empty($hostingOrders) && count($hostingOrders) > 0)
<div class="mt-6 rounded-2xl border border-hv-border bg-hv-elevated p-6">
    <h2 class="text-lg font-semibold text-hv-text">Hosting'i başka hesaba devret</h2>
    <p class="mt-1 text-sm text-hv-muted">Bir hosting hizmetini başka bir HostVim hesabına devredebilirsiniz. Devralacak kişinin HostVim'de kayıtlı olması gerekir; talep <strong>admin onayından</strong> sonra tamamlanır.</p>

    <div class="mt-4 space-y-4">
        @foreach($hostingOrders as $order)
            @php($pending = $pendingTransfers[$order->id] ?? null)
            <div class="rounded-xl border border-hv-border bg-hv-surface p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="font-medium text-hv-text">{{ $order->hosting_product_label }}</p>
                        <p class="text-sm text-hv-muted">{{ $order->service_domain_label }} · Sipariş {{ $order->order_number }}</p>
                    </div>
                </div>

                @if($pending)
                    <div class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <p><strong>{{ $pending->target_email }}</strong> adresine devir talebiniz <strong>onay bekliyor</strong> (No: {{ $pending->number }}).</p>
                        <form method="POST" action="{{ route('account.transfers.cancel', $pending->id) }}" class="mt-2">
                            @csrf
                            <button class="rounded-lg border border-amber-400 px-3 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-100">Talebi iptal et</button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('account.transfers.hosting', $order->id) }}" class="mt-3 grid gap-3 sm:max-w-lg">
                        @csrf
                        <label class="text-sm">
                            <span class="block text-hv-muted">Devralacak hesabın e-postası</span>
                            <input type="email" name="target_email" required placeholder="devralacak@eposta.com"
                                class="mt-1 w-full rounded-lg border border-hv-border bg-hv-surface px-3 py-2 text-sm">
                        </label>
                        <label class="text-sm">
                            <span class="block text-hv-muted">Not (opsiyonel)</span>
                            <textarea name="note" rows="2" class="mt-1 w-full rounded-lg border border-hv-border bg-hv-surface px-3 py-2 text-sm"></textarea>
                        </label>
                        <div>
                            <button class="btn-primary" onclick="return confirm('{{ $order->service_domain_label }} hosting hizmetini başka bir hesaba devretmek üzere talep oluşturulacak. Onaylıyor musunuz?')">Devir talebi gönder</button>
                        </div>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif
@endsection
