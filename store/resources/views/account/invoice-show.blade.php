@extends('layouts.account', ['pageTitle' => 'Fatura #'.($invoice['number'] ?? '')])

@section('account')
@if(!$invoice)
    <p class="text-hv-muted">Fatura bulunamadı.</p>
@else
    @php $i = is_array($invoice) ? $invoice : $invoice->toArray(); @endphp
    <div class="rounded-2xl border border-hv-border bg-hv-elevated p-6">
        <div class="flex flex-wrap justify-between gap-4">
            <div>
                <p class="text-sm text-hv-muted">Fatura</p>
                <p class="text-2xl font-bold text-hv-text">{{ $i['number'] }}</p>
                <p class="mt-1 text-sm text-hv-muted">Durum: <span class="font-medium">{{ $i['status'] }}</span></p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-hv-primary">{{ number_format((float) $i['total'], 2, ',', '.') }} {{ $i['currency'] ?? 'TRY' }}</p>
                @if(in_array($i['status'], ['unpaid', 'overdue'], true))
                    <form action="{{ route('account.invoices.pay', $i['id']) }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="btn-primary text-sm">Öde</button>
                    </form>
                @endif
            </div>
        </div>
        @if(!empty($i['items']))
            <div class="mt-6 border-t border-hv-border pt-4">
                <h3 class="font-semibold text-hv-text">Kalemler</h3>
                <ul class="mt-2 space-y-2 text-sm">
                    @foreach($i['items'] as $item)
                        @php $line = is_array($item) ? $item : $item->toArray(); @endphp
                        <li class="flex justify-between">
                            <span>{{ $line['description'] ?? 'Kalem' }}</span>
                            <span>{{ number_format((float) ($line['total'] ?? $line['amount'] ?? 0), 2, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        <a href="{{ route('account.invoices') }}" class="btn-ghost mt-6 inline-flex text-sm">← Faturalara dön</a>
    </div>
@endif
@endsection
