@extends('layouts.account', ['pageTitle' => 'Hostinglerim'])

@section('account')
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
@endsection
