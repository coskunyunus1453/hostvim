@extends('layouts.account', ['pageTitle' => 'Profil & Fatura Adresi'])

@section('account')
<form action="{{ route('account.profile.update') }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-hv-border bg-hv-elevated p-6">
        <h2 class="text-lg font-semibold text-hv-text">Kişisel bilgiler</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-hv-muted">Ad Soyad</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-hv-muted">E-posta</label>
                <input type="email" value="{{ $user->email }}" disabled class="w-full rounded-xl border border-hv-border bg-hv-bg px-3 py-2 text-hv-muted">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-hv-muted">Telefon</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-hv-muted">Şirket (opsiyonel)</label>
                <input type="text" name="company" value="{{ old('company', $user->company) }}" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-hv-muted">Adres</label>
                <textarea name="address" rows="2" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">{{ old('address', $user->address) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-hv-muted">İl</label>
                <input type="text" name="city" value="{{ old('city', $user->city) }}" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-hv-muted">İlçe</label>
                <input type="text" name="district" value="{{ old('district', $user->district) }}" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-hv-muted">Posta kodu</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-hv-muted">Vergi dairesi</label>
                <input type="text" name="tax_office" value="{{ old('tax_office', $user->tax_office) }}" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-hv-muted">Vergi / TC no</label>
                <input type="text" name="tax_number" value="{{ old('tax_number', $user->tax_number) }}" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-hv-border bg-hv-elevated p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-hv-text">Fatura adresi</h2>
            <label class="flex items-center gap-2 text-sm text-hv-muted">
                <input type="checkbox" name="use_profile_as_billing" value="1" class="rounded border-hv-border">
                Profil adresini kullan
            </label>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-hv-muted">Fatura ünvanı</label>
                <input type="text" name="billing_company" value="{{ old('billing_company', $user->billing_company) }}" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-hv-muted">Fatura adresi</label>
                <textarea name="billing_address" rows="2" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">{{ old('billing_address', $user->billing_address) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-hv-muted">İl</label>
                <input type="text" name="billing_city" value="{{ old('billing_city', $user->billing_city) }}" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-hv-muted">İlçe</label>
                <input type="text" name="billing_district" value="{{ old('billing_district', $user->billing_district) }}" class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
            </div>
        </div>
    </div>

    <button type="submit" class="btn-primary">Kaydet</button>
</form>

<div class="mt-8 rounded-2xl border border-hv-border bg-hv-elevated p-6">
    <h2 class="text-lg font-semibold text-hv-text">Şifre değiştir</h2>
    <form action="{{ route('account.profile.password') }}" method="POST" class="mt-4 grid max-w-lg gap-4">
        @csrf
        @method('PUT')
        <div>
            <label class="mb-1 block text-sm font-medium text-hv-muted">Mevcut şifre</label>
            <input type="password" name="current_password" required class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-hv-muted">Yeni şifre</label>
            <input type="password" name="password" required class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-hv-muted">Yeni şifre (tekrar)</label>
            <input type="password" name="password_confirmation" required class="w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2">
        </div>
        <button type="submit" class="btn-secondary w-fit">Şifreyi güncelle</button>
    </form>
</div>
@endsection
