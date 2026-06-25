@extends('layouts.account', ['pageTitle' => 'Faturalarım'])

@section('account')
@if(!$linked)
    <div class="rounded-2xl border border-dashed border-hv-border bg-hv-surface p-8 text-center">
        <p class="font-medium text-hv-text">Fatura bulunamadı</p>
        <p class="mt-2 text-sm text-hv-muted">Hosting veya domain siparişi sonrası faturalarınız burada listelenir.</p>
    </div>
@else
    @if(!empty($error))
        <div class="mb-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $error }}</div>
    @endif

    <div class="rounded-2xl border border-hv-border bg-hv-elevated overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-hv-surface text-hv-muted">
                <tr>
                    <th class="px-4 py-3 text-left">Fatura no</th>
                    <th class="px-4 py-3 text-left">Durum</th>
                    <th class="px-4 py-3 text-left">Tutar</th>
                    <th class="px-4 py-3 text-left">Vade</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    @php $i = is_array($inv) ? $inv : $inv->toArray(); @endphp
                    <tr class="border-t border-hv-border/60">
                        <td class="px-4 py-3 font-medium text-hv-text">{{ $i['number'] ?? '#' }}</td>
                        <td class="px-4 py-3">{{ $i['status'] ?? '-' }}</td>
                        <td class="px-4 py-3">{{ number_format((float) ($i['total'] ?? 0), 2, ',', '.') }} {{ $i['currency'] ?? 'TRY' }}</td>
                        <td class="px-4 py-3 text-hv-muted">{{ !empty($i['due_at']) ? \Illuminate\Support\Carbon::parse($i['due_at'])->format('d.m.Y') : '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('account.invoices.show', $i['id']) }}" class="text-hv-primary hover:underline">Detay</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-hv-muted">Fatura yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
