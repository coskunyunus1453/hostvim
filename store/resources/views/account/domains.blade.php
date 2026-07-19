@extends('layouts.account', ['pageTitle' => 'Alan Adlarım'])

@section('account')
@if(session('success'))
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-sm text-hv-muted">Kayıtlı alan adlarınızı yönetin — DNS, nameserver, yenileme ve güvenlik ayarları tek yerde.</p>
    </div>
    <a href="{{ route('domain.index') }}" class="btn-primary inline-flex items-center gap-2 text-sm">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Yeni alan adı ara
    </a>
</div>

@if($domains->isEmpty())
    <div class="rounded-2xl border border-dashed border-hv-border bg-hv-elevated p-10 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-hv-primary/10 text-2xl">🌐</div>
        <p class="mt-4 text-lg font-semibold text-hv-text">Henüz kayıtlı alan adınız yok</p>
        <p class="mx-auto mt-2 max-w-md text-sm text-hv-muted">Alan adı satın aldığınızda burada listelenir; DNS, nameserver ve yenileme işlemlerini hesabınızdan yönetirsiniz.</p>
        <a href="{{ route('domain.index') }}" class="btn-primary mt-6 inline-flex">Alan adı ara</a>
    </div>
@else
    <div class="overflow-hidden rounded-2xl border border-hv-border bg-hv-elevated shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead class="border-b border-hv-border bg-hv-surface text-left text-xs uppercase tracking-wide text-hv-muted">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Alan adı</th>
                        <th class="px-5 py-3.5 font-semibold">Durum</th>
                        <th class="px-5 py-3.5 font-semibold">Bitiş</th>
                        <th class="px-5 py-3.5 font-semibold">DNS</th>
                        <th class="px-5 py-3.5 text-right font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-hv-border">
                    @foreach($domains as $d)
                        @php
                            $map = [
                                'registered' => ['Aktif', 'bg-emerald-100 text-emerald-800'],
                                'active' => ['Aktif', 'bg-emerald-100 text-emerald-800'],
                                'registering' => ['Hazırlanıyor', 'bg-amber-100 text-amber-800'],
                                'failed' => ['Kayıt başarısız', 'bg-red-100 text-red-800'],
                            ];
                            [$label, $cls] = $map[$d->status] ?? [ucfirst((string) $d->status), 'bg-stone-100 text-stone-800'];
                            $daysLeft = $d->expires_at ? now()->startOfDay()->diffInDays($d->expires_at->startOfDay(), false) : null;
                            $manageable = in_array($d->status, ['registered', 'active'], true);
                        @endphp
                        <tr class="transition hover:bg-hv-surface/60">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-hv-text">{{ $d->domain }}</p>
                                @if($manageable && $daysLeft !== null && $daysLeft <= 30)
                                    <p class="mt-0.5 text-xs font-medium {{ $daysLeft < 0 ? 'text-red-600' : 'text-amber-600' }}">
                                        {{ $daysLeft < 0 ? 'Süresi doldu' : $daysLeft.' gün kaldı' }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full {{ $cls }} px-2.5 py-0.5 text-xs font-semibold">{{ $label }}</span>
                            </td>
                            <td class="px-5 py-4 text-hv-muted">{{ $d->expires_at?->format('d.m.Y') ?? '—' }}</td>
                            <td class="px-5 py-4 text-hv-muted">
                                @if($manageable)
                                    {{ $d->ns_provider === 'custom' ? 'Özel NS' : config('brand.name', 'HostVim').' DNS' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($manageable)
                                    <a href="{{ route('account.domains.show', $d->id) }}" class="inline-flex items-center gap-1 rounded-xl bg-hv-primary/10 px-3 py-1.5 text-sm font-semibold text-hv-primary transition hover:bg-hv-primary hover:text-white">
                                        Yönet
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                @else
                                    <span class="text-xs text-hv-muted">{{ $d->status === 'failed' ? 'Destek ile iletişime geçin' : 'Hazırlanıyor…' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
