@extends('layouts.account', ['pageTitle' => 'Alan Adlarım'])

@section('account')
@if(session('success'))
    <div class="mb-4 rounded-xl border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif

@if($domains->isEmpty())
    <div class="rounded-2xl border border-dashed border-hv-border bg-hv-surface p-8 text-center">
        <p class="text-hv-text font-medium">Henüz kayıtlı alan adınız yok</p>
        <p class="mt-2 text-sm text-hv-muted">HostVim üzerinden domain satın aldığınızda burada listelenir ve DNS, nameserver, yenileme gibi tüm işlemleri buradan yönetirsiniz.</p>
        <a href="{{ route('domain.index') }}" class="btn-primary mt-4 inline-flex">Domain ara</a>
    </div>
@else
    <div class="overflow-hidden rounded-2xl border border-hv-border bg-hv-surface">
        <table class="w-full text-sm">
            <thead class="bg-hv-surface text-left text-xs uppercase tracking-wide text-hv-muted">
                <tr>
                    <th class="px-4 py-3">Alan Adı</th>
                    <th class="px-4 py-3">Durum</th>
                    <th class="px-4 py-3">Bitiş Tarihi</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-hv-border">
                @foreach($domains as $d)
                    <tr>
                        <td class="px-4 py-3 font-medium text-hv-text">{{ $d->domain }}</td>
                        <td class="px-4 py-3">
                            @php
                                $map = [
                                    'registered' => ['Aktif', 'bg-green-100 text-green-800'],
                                    'active' => ['Aktif', 'bg-green-100 text-green-800'],
                                    'registering' => ['Hazırlanıyor', 'bg-amber-100 text-amber-800'],
                                    'failed' => ['Kayıt Başarısız', 'bg-red-100 text-red-800'],
                                ];
                                [$label, $cls] = $map[$d->status] ?? [ucfirst((string) $d->status), 'bg-stone-100 text-stone-800'];
                            @endphp
                            <span class="inline-flex rounded-full {{ $cls }} px-2.5 py-0.5 text-xs font-medium">{{ $label }}</span>
                        </td>
                        <td class="px-4 py-3 text-hv-muted">{{ $d->expires_at?->format('d.m.Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if(in_array($d->status, ['registered', 'active'], true))
                                <a href="{{ route('account.domains.show', $d->id) }}" class="text-hv-primary font-medium hover:underline">Yönet</a>
                            @else
                                <span class="text-xs text-hv-muted">{{ $d->status === 'failed' ? 'Destek ile iletişime geçin' : 'Hazırlanıyor…' }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
