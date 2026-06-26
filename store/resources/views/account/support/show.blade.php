@extends('layouts.account', ['pageTitle' => $ticket->number])

@section('account')
<div class="mb-4">
    <a href="{{ route('account.support.index') }}" class="text-sm text-hv-primary hover:underline">← Destek taleplerine dön</a>
</div>

@if(session('success'))
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif

<div class="mb-6 rounded-2xl border border-hv-border bg-hv-elevated p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="font-mono text-xs text-hv-muted">{{ $ticket->number }}</p>
            <h2 class="mt-1 text-lg font-semibold text-hv-text">{{ $ticket->subject }}</h2>
            <p class="mt-2 text-sm text-hv-muted">
                {{ \App\Models\SupportTicket::departmentLabel($ticket->department) }}
                · {{ \App\Models\SupportTicket::priorityLabel($ticket->priority) }}
                · {{ \App\Models\SupportTicket::statusLabel($ticket->status) }}
            </p>
        </div>
        @if($ticket->isOpen())
            <form action="{{ route('account.support.close', $ticket) }}" method="POST" onsubmit="return confirm('Talebi kapatmak istediğinize emin misiniz?')">
                @csrf
                <button type="submit" class="btn-ghost text-sm text-red-600">Talebi Kapat</button>
            </form>
        @endif
    </div>
</div>

<div class="space-y-4">
    @foreach($ticket->messages as $message)
        <div class="rounded-2xl border border-hv-border p-4 {{ $message->is_staff ? 'bg-hv-primary/5 border-hv-primary/20' : 'bg-hv-elevated' }}">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-xs text-hv-muted">
                <span class="font-semibold text-hv-text">
                    {{ $message->is_staff ? 'Destek Ekibi' : ($message->user?->name ?? 'Siz') }}
                </span>
                <time>{{ $message->created_at?->format('d.m.Y H:i') }}</time>
            </div>
            <div class="whitespace-pre-wrap text-sm text-hv-text">{{ $message->body }}</div>
        </div>
    @endforeach
</div>

@if($ticket->isOpen())
    <form action="{{ route('account.support.reply', $ticket) }}" method="POST" class="mt-6 rounded-2xl border border-hv-border bg-hv-elevated p-5">
        @csrf
        <label for="body" class="mb-2 block text-sm font-medium text-hv-text">Yanıt yazın</label>
        <textarea name="body" id="body" rows="5" required maxlength="10000"
                  class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2 text-sm"></textarea>
        @error('body')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        <button type="submit" class="btn-primary mt-3">Gönder</button>
    </form>
@else
    <p class="mt-6 text-center text-sm text-hv-muted">Bu talep kapatılmıştır.</p>
@endif
@endsection
