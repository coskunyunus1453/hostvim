@extends('layouts.account', ['pageTitle' => 'Destek Talepleri'])

@section('account')
@if(session('success'))
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-hv-muted">Sorularınız ve teknik destek için talep oluşturun; ekibimiz e-posta ile bilgilendirilir.</p>
    <a href="{{ route('account.support.create') }}" class="btn-primary text-sm">Yeni Talep</a>
</div>

<div class="rounded-2xl border border-hv-border bg-hv-elevated overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-hv-surface text-hv-muted">
            <tr>
                <th class="px-4 py-3 text-left">No</th>
                <th class="px-4 py-3 text-left">Konu</th>
                <th class="px-4 py-3 text-left">Durum</th>
                <th class="px-4 py-3 text-left">Son güncelleme</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
                <tr class="border-t border-hv-border/60">
                    <td class="px-4 py-3 font-mono text-xs text-hv-muted">{{ $ticket->number }}</td>
                    <td class="px-4 py-3 font-medium text-hv-text">{{ $ticket->subject }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full bg-hv-surface px-2.5 py-0.5 text-xs font-medium text-hv-muted">
                            {{ \App\Models\SupportTicket::statusLabel($ticket->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-hv-muted">{{ $ticket->last_reply_at?->format('d.m.Y H:i') ?? $ticket->created_at?->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('account.support.show', $ticket) }}" class="text-hv-primary hover:underline">Görüntüle</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-hv-muted">
                        Henüz destek talebiniz yok.
                        <a href="{{ route('account.support.create') }}" class="ml-1 text-hv-primary hover:underline">İlk talebi oluşturun</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $tickets->links() }}</div>
@endsection
