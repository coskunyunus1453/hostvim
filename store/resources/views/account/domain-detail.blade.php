@extends('layouts.account', ['pageTitle' => $domain->domain])

@section('account')
<a href="{{ route('account.domains') }}" class="mb-4 inline-flex items-center gap-1 text-sm text-hv-muted hover:text-hv-text">&larr; Alan adlarıma dön</a>

@if(session('success'))
    <div class="mb-4 rounded-xl border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif
@if(session('auth_code'))
    <div class="mb-4 rounded-xl border border-sky-300 bg-sky-50 px-4 py-3 text-sm text-sky-900">
        <span class="font-semibold">Transfer (EPP/Auth) kodu:</span>
        <code class="ml-2 rounded bg-white px-2 py-1 font-mono text-sky-800">{{ session('auth_code') }}</code>
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Genel bilgi --}}
    <div class="rounded-2xl border border-hv-border bg-hv-surface p-5 lg:col-span-1">
        <h2 class="text-lg font-semibold text-hv-text">{{ $domain->domain }}</h2>
        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-hv-muted">Durum</dt><dd class="font-medium text-hv-text">Aktif</dd></div>
            <div class="flex justify-between"><dt class="text-hv-muted">Bitiş</dt><dd class="text-hv-text">{{ $domain->expires_at?->format('d.m.Y') ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-hv-muted">Gizlilik</dt><dd class="text-hv-text">{{ $domain->privacyEnabled() ? 'Açık' : 'Kapalı' }}</dd></div>
            <div class="flex justify-between"><dt class="text-hv-muted">Oto. Yenileme</dt><dd class="text-hv-text">{{ $domain->auto_renew ? 'Açık' : 'Kapalı' }}</dd></div>
            <div class="flex justify-between"><dt class="text-hv-muted">Nameserver</dt><dd class="text-hv-text">{{ $domain->ns_provider === 'custom' ? 'Özel' : 'Varsayılan' }}</dd></div>
        </dl>

        <div class="mt-5 flex flex-wrap gap-2">
            <form method="POST" action="{{ route('account.domains.privacy', $domain->id) }}">
                @csrf
                <button class="rounded-lg border border-hv-border px-3 py-1.5 text-xs font-medium text-hv-text hover:bg-stone-50">{{ $domain->privacyEnabled() ? 'Gizliliği Kapat' : 'Gizliliği Aç' }}</button>
            </form>
            <form method="POST" action="{{ route('account.domains.autorenew', $domain->id) }}">
                @csrf
                <button class="rounded-lg border border-hv-border px-3 py-1.5 text-xs font-medium text-hv-text hover:bg-stone-50">{{ $domain->auto_renew ? 'Oto. Yenilemeyi Kapat' : 'Oto. Yenilemeyi Aç' }}</button>
            </form>
            <form method="POST" action="{{ route('account.domains.authcode', $domain->id) }}">
                @csrf
                <button class="rounded-lg border border-hv-border px-3 py-1.5 text-xs font-medium text-hv-text hover:bg-stone-50">Transfer Kodu</button>
            </form>
        </div>
    </div>

    {{-- Yenileme + Nameserver --}}
    <div class="space-y-6 lg:col-span-2">
        <div class="rounded-2xl border border-hv-border bg-hv-surface p-5">
            <h3 class="text-base font-semibold text-hv-text">Süre Uzat / Yenile</h3>
            <form method="POST" action="{{ route('account.domains.renew', $domain->id) }}" class="mt-3 flex items-end gap-3">
                @csrf
                <label class="text-sm">
                    <span class="block text-hv-muted">Yıl</span>
                    <select name="years" class="mt-1 rounded-lg border border-hv-border bg-hv-surface px-3 py-2 text-sm">
                        @for($y = 1; $y <= 10; $y++)
                            <option value="{{ $y }}">{{ $y }} yıl</option>
                        @endfor
                    </select>
                </label>
                <button class="btn-primary">Yenile</button>
            </form>
        </div>

        <div class="rounded-2xl border border-hv-border bg-hv-surface p-5">
            <h3 class="text-base font-semibold text-hv-text">Nameserver (NS)</h3>
            <form method="POST" action="{{ route('account.domains.nameservers', $domain->id) }}" class="mt-3 space-y-3" id="ns-form">
                @csrf
                <div class="flex gap-4 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="provider" value="basic" {{ $domain->ns_provider !== 'custom' ? 'checked' : '' }} onchange="document.getElementById('ns-custom').classList.add('hidden')">
                        <span>Varsayılan (HostVim/Spaceship)</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="provider" value="custom" {{ $domain->ns_provider === 'custom' ? 'checked' : '' }} onchange="document.getElementById('ns-custom').classList.remove('hidden')">
                        <span>Özel Nameserver</span>
                    </label>
                </div>
                <div id="ns-custom" class="{{ $domain->ns_provider === 'custom' ? '' : 'hidden' }}">
                    <textarea name="hosts" rows="3" placeholder="ns1.example.com&#10;ns2.example.com" class="w-full rounded-lg border border-hv-border bg-hv-surface px-3 py-2 text-sm font-mono">{{ collect($domain->nameservers ?? [])->implode("\n") }}</textarea>
                    <p class="mt-1 text-xs text-hv-muted">Her satıra bir nameserver (en az 2 adet).</p>
                </div>
                <button class="btn-primary">Kaydet</button>
            </form>
        </div>

        {{-- DNS --}}
        <div class="rounded-2xl border border-hv-border bg-hv-surface p-5">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-hv-text">DNS Kayıtları</h3>
                <button type="button" onclick="addDnsRow()" class="rounded-lg border border-hv-border px-3 py-1.5 text-xs font-medium text-hv-text hover:bg-stone-50">+ Kayıt Ekle</button>
            </div>
            @if($dnsError)
                <p class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800">DNS kayıtları yüklenemedi: {{ $dnsError }}</p>
            @endif
            <form method="POST" action="{{ route('account.domains.dns', $domain->id) }}" class="mt-3">
                @csrf
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="dns-table">
                        <thead class="text-left text-xs uppercase text-hv-muted">
                            <tr>
                                <th class="py-2 pr-2">Tip</th>
                                <th class="py-2 pr-2">Ad</th>
                                <th class="py-2 pr-2">Değer</th>
                                <th class="py-2 pr-2">TTL</th>
                                <th class="py-2 pr-2">Öncelik</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="dns-rows">
                            @foreach($records as $i => $r)
                                <tr>
                                    <td class="py-1 pr-2">
                                        <select name="records[{{ $i }}][type]" class="rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5">
                                            @foreach(['A','AAAA','CNAME','MX','TXT','NS','CAA','SRV','ALIAS'] as $t)
                                                <option value="{{ $t }}" {{ ($r['type'] ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-1 pr-2"><input name="records[{{ $i }}][name]" value="{{ $r['name'] ?? '@' }}" class="w-24 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5"></td>
                                    <td class="py-1 pr-2"><input name="records[{{ $i }}][value]" value="{{ $r['value'] ?? '' }}" class="w-full min-w-[180px] rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5"></td>
                                    <td class="py-1 pr-2"><input name="records[{{ $i }}][ttl]" type="number" value="{{ $r['ttl'] ?? 3600 }}" class="w-20 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5"></td>
                                    <td class="py-1 pr-2"><input name="records[{{ $i }}][priority]" type="number" value="{{ $r['priority'] ?? '' }}" class="w-16 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5"></td>
                                    <td class="py-1"><button type="button" onclick="this.closest('tr').remove()" class="text-red-600 hover:underline">Sil</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-hv-muted">Değişiklikler kaydedildiğinde tablodaki tüm kayıtlar geçerli set olarak uygulanır (eksik olanlar silinir).</p>
                <button class="btn-primary mt-3">DNS Kayıtlarını Kaydet</button>
            </form>
        </div>
    </div>
</div>

{{-- Başka hesaba devret --}}
<div class="mt-6 rounded-2xl border border-hv-border bg-hv-surface p-5">
    <h3 class="text-base font-semibold text-hv-text">Başka hesaba devret</h3>
    @if(!empty($pendingTransfer))
        <div class="mt-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p><strong>{{ $pendingTransfer->target_email }}</strong> adresine alan adı devir talebiniz <strong>onay bekliyor</strong> (No: {{ $pendingTransfer->number }}).</p>
            <form method="POST" action="{{ route('account.transfers.cancel', $pendingTransfer->id) }}" class="mt-2">
                @csrf
                <button class="rounded-lg border border-amber-400 px-3 py-1.5 text-xs font-medium text-amber-900 hover:bg-amber-100">Talebi iptal et</button>
            </form>
        </div>
    @else
        <p class="mt-2 text-sm text-hv-muted">Bu alan adını başka bir HostVim hesabına devredebilirsiniz. Devralacak kişinin HostVim'de kayıtlı olması gerekir. Talebiniz <strong>admin onayından</strong> sonra tamamlanır.</p>
        <form method="POST" action="{{ route('account.transfers.domain', $domain->id) }}" class="mt-3 grid gap-3 sm:max-w-lg">
            @csrf
            <label class="text-sm">
                <span class="block text-hv-muted">Devralacak hesabın e-postası</span>
                <input type="email" name="target_email" required placeholder="devralacak@eposta.com"
                    class="mt-1 w-full rounded-lg border border-hv-border bg-hv-surface px-3 py-2 text-sm" value="{{ old('target_email') }}">
            </label>
            @error('target_email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            <label class="text-sm">
                <span class="block text-hv-muted">Not (opsiyonel)</span>
                <textarea name="note" rows="2" class="mt-1 w-full rounded-lg border border-hv-border bg-hv-surface px-3 py-2 text-sm">{{ old('note') }}</textarea>
            </label>
            <div>
                <button class="btn-primary" onclick="return confirm('Bu alan adını {{ $domain->domain }} başka bir hesaba devretmek üzere talep oluşturulacak. Onaylıyor musunuz?')">Devir talebi gönder</button>
            </div>
        </form>
    @endif
</div>

<script>
    let dnsIndex = {{ count($records) }};
    function addDnsRow() {
        const types = ['A','AAAA','CNAME','MX','TXT','NS','CAA','SRV','ALIAS'];
        const i = dnsIndex++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="py-1 pr-2"><select name="records[${i}][type]" class="rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5">${types.map(t => `<option value="${t}">${t}</option>`).join('')}</select></td>
            <td class="py-1 pr-2"><input name="records[${i}][name]" value="@" class="w-24 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5"></td>
            <td class="py-1 pr-2"><input name="records[${i}][value]" value="" class="w-full min-w-[180px] rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5"></td>
            <td class="py-1 pr-2"><input name="records[${i}][ttl]" type="number" value="3600" class="w-20 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5"></td>
            <td class="py-1 pr-2"><input name="records[${i}][priority]" type="number" value="" class="w-16 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5"></td>
            <td class="py-1"><button type="button" onclick="this.closest('tr').remove()" class="text-red-600 hover:underline">Sil</button></td>`;
        document.getElementById('dns-rows').appendChild(tr);
    }
</script>
@endsection
