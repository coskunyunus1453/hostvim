@extends('layouts.account', ['pageTitle' => 'Yeni Destek Talebi'])

@section('account')
<div class="mb-4">
    <a href="{{ route('account.support.index') }}" class="text-sm text-hv-primary hover:underline">← Destek taleplerine dön</a>
</div>

<form action="{{ route('account.support.store') }}" method="POST" class="max-w-2xl space-y-4 rounded-2xl border border-hv-border bg-hv-elevated p-6">
    @csrf

    <div>
        <label for="subject" class="mb-1 block text-sm font-medium text-hv-text">Konu</label>
        <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required maxlength="200"
               class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2 text-sm">
        @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="department" class="mb-1 block text-sm font-medium text-hv-text">Departman</label>
            <select name="department" id="department" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2 text-sm">
                <option value="general" @selected(old('department', 'general') === 'general')>Genel</option>
                <option value="technical" @selected(old('department') === 'technical')>Teknik</option>
                <option value="billing" @selected(old('department') === 'billing')>Fatura</option>
            </select>
        </div>
        <div>
            <label for="priority" class="mb-1 block text-sm font-medium text-hv-text">Öncelik</label>
            <select name="priority" id="priority" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2 text-sm">
                <option value="low" @selected(old('priority') === 'low')>Düşük</option>
                <option value="medium" @selected(old('priority', 'medium') === 'medium')>Orta</option>
                <option value="high" @selected(old('priority') === 'high')>Yüksek</option>
            </select>
        </div>
    </div>

    <div>
        <label for="body" class="mb-1 block text-sm font-medium text-hv-text">Mesajınız</label>
        <textarea name="body" id="body" rows="8" required maxlength="10000"
                  class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2 text-sm">{{ old('body') }}</textarea>
        @error('body')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <button type="submit" class="btn-primary">Talebi Gönder</button>
</form>
@endsection
