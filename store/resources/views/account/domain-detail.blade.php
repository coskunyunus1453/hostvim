@extends('layouts.account', ['pageTitle' => $domain->domain])

@section('account')
<a href="{{ route('account.domains') }}" class="mb-5 inline-flex items-center gap-1.5 text-sm font-medium text-hv-muted transition hover:text-hv-primary">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Alan adlarıma dön
</a>

@if(session('success'))
    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif
@if(session('auth_code'))
    <div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
        <span class="font-semibold">Transfer (EPP/Auth) kodu:</span>
        <code class="ml-2 rounded-lg bg-white px-2.5 py-1 font-mono text-sm text-sky-800">{{ session('auth_code') }}</code>
    </div>
@endif

@php
    $isCustomNs = $domain->ns_provider === 'custom';
    $activeNs = $isCustomNs
        ? collect($domain->nameservers ?? [])->filter()->values()
        : collect($defaultNameservers ?? []);
    $expiryClass = match (true) {
        $daysUntilExpiry === null => 'text-hv-muted',
        $daysUntilExpiry < 0 => 'text-red-600',
        $daysUntilExpiry <= 30 => 'text-amber-600',
        default => 'text-emerald-600',
    };
@endphp

{{-- Üst özet --}}
<div class="overflow-hidden rounded-2xl border border-hv-border bg-hv-elevated shadow-sm">
    <div class="border-b border-hv-border bg-hv-gradient px-5 py-6 text-white sm:px-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-white/80">Alan adı yönetimi</p>
                <h2 class="mt-1 break-all text-2xl font-bold tracking-tight sm:text-3xl">{{ $domain->domain }}</h2>
            </div>
            <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur">Aktif</span>
        </div>
    </div>
    <div class="grid gap-px bg-hv-border sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-hv-elevated p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-hv-muted">Bitiş tarihi</p>
            <p class="mt-1 text-lg font-semibold text-hv-text">{{ $domain->expires_at?->format('d.m.Y') ?? '—' }}</p>
            @if($daysUntilExpiry !== null)
                <p class="mt-0.5 text-xs font-medium {{ $expiryClass }}">
                    @if($daysUntilExpiry < 0)
                        {{ abs($daysUntilExpiry) }} gün önce süresi doldu
                    @elseif($daysUntilExpiry === 0)
                        Bugün sona eriyor
                    @else
                        {{ $daysUntilExpiry }} gün kaldı
                    @endif
                </p>
            @endif
        </div>
        <div class="bg-hv-elevated p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-hv-muted">WHOIS gizliliği</p>
            <p class="mt-1 text-lg font-semibold text-hv-text">{{ $domain->privacyEnabled() ? 'Açık' : 'Kapalı' }}</p>
            <p class="mt-0.5 text-xs text-hv-muted">Kişisel bilgilerin korunması</p>
        </div>
        <div class="bg-hv-elevated p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-hv-muted">Otomatik yenileme</p>
            <p class="mt-1 text-lg font-semibold text-hv-text">{{ $domain->auto_renew ? 'Açık' : 'Kapalı' }}</p>
            <p class="mt-0.5 text-xs text-hv-muted">Süre bitiminde otomatik uzatma</p>
        </div>
        <div class="bg-hv-elevated p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-hv-muted">DNS yönetimi</p>
            <p class="mt-1 text-lg font-semibold text-hv-text">{{ $isCustomNs ? 'Özel NS' : $brandName.' DNS' }}</p>
            <p class="mt-0.5 text-xs text-hv-muted">{{ $isCustomNs ? 'Harici nameserver' : 'Varsayılan altyapı' }}</p>
        </div>
    </div>
</div>

{{-- Sekmeler --}}
<div class="mt-6" id="domain-tabs">
    <div class="flex flex-wrap gap-2 border-b border-hv-border pb-3" role="tablist">
        @foreach([
            'overview' => 'Genel',
            'dns' => 'DNS kayıtları',
            'ns' => 'Nameserver',
            'renew' => 'Yenileme',
            'transfer' => 'Devir',
        ] as $id => $label)
            <button type="button" data-tab="{{ $id }}"
                class="domain-tab rounded-xl px-4 py-2 text-sm font-medium transition {{ $loop->first ? 'bg-hv-primary text-white shadow-sm' : 'text-hv-muted hover:bg-hv-surface hover:text-hv-text' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Genel --}}
    <div class="domain-panel mt-6" data-panel="overview">
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5">
                <h3 class="text-base font-semibold text-hv-text">Hızlı işlemler</h3>
                <p class="mt-1 text-sm text-hv-muted">Güvenlik ve yenileme ayarlarını tek tıkla güncelleyin.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('account.domains.privacy', $domain->id) }}">
                        @csrf
                        <button class="rounded-xl border border-hv-border bg-hv-surface px-4 py-2 text-sm font-medium text-hv-text transition hover:border-hv-primary hover:text-hv-primary">
                            {{ $domain->privacyEnabled() ? 'Gizliliği kapat' : 'Gizliliği aç' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('account.domains.autorenew', $domain->id) }}">
                        @csrf
                        <button class="rounded-xl border border-hv-border bg-hv-surface px-4 py-2 text-sm font-medium text-hv-text transition hover:border-hv-primary hover:text-hv-primary">
                            {{ $domain->auto_renew ? 'Oto. yenilemeyi kapat' : 'Oto. yenilemeyi aç' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('account.domains.authcode', $domain->id) }}">
                        @csrf
                        <button class="rounded-xl border border-hv-border bg-hv-surface px-4 py-2 text-sm font-medium text-hv-text transition hover:border-hv-primary hover:text-hv-primary">
                            Transfer kodu al
                        </button>
                    </form>
                </div>
            </div>
            <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5">
                <h3 class="text-base font-semibold text-hv-text">Aktif nameserver'lar</h3>
                <p class="mt-1 text-sm text-hv-muted">Alan adınızın şu an kullandığı sunucular.</p>
                @if($activeNs->isEmpty())
                    <p class="mt-4 text-sm text-hv-muted">Nameserver bilgisi henüz yüklenmedi.</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach($activeNs as $ns)
                            <li class="flex items-center gap-2 rounded-xl border border-hv-border bg-hv-surface px-3 py-2 font-mono text-sm text-hv-text">{{ $ns }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- DNS --}}
    <div class="domain-panel mt-6 hidden" data-panel="dns">
        <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-hv-text">DNS kayıtları</h3>
                    <p class="mt-1 text-sm text-hv-muted">A, CNAME, MX ve diğer kayıtları buradan yönetin.</p>
                </div>
                <button type="button" onclick="addDnsRow()" class="rounded-xl border border-hv-border bg-hv-surface px-4 py-2 text-sm font-medium text-hv-text hover:border-hv-primary">
                    + Kayıt ekle
                </button>
            </div>
            @if($dnsError)
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    DNS kayıtları yüklenemedi: {{ $dnsError }}
                </div>
            @endif
            <form method="POST" action="{{ route('account.domains.dns', $domain->id) }}" class="mt-5">
                @csrf
                <div class="overflow-x-auto rounded-xl border border-hv-border">
                    <table class="w-full min-w-[720px] text-sm" id="dns-table">
                        <thead class="bg-hv-surface text-left text-xs uppercase tracking-wide text-hv-muted">
                            <tr>
                                <th class="px-3 py-3">Tip</th>
                                <th class="px-3 py-3">Ad</th>
                                <th class="px-3 py-3">Değer</th>
                                <th class="px-3 py-3">TTL</th>
                                <th class="px-3 py-3">Öncelik</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="dns-rows" class="divide-y divide-hv-border bg-hv-elevated">
                            @foreach($records as $i => $r)
                                <tr>
                                    <td class="px-3 py-2">
                                        <select name="records[{{ $i }}][type]" class="w-full rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5">
                                            @foreach(['A','AAAA','CNAME','MX','TXT','NS','CAA','SRV','ALIAS'] as $t)
                                                <option value="{{ $t }}" {{ ($r['type'] ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2"><input name="records[{{ $i }}][name]" value="{{ $r['name'] ?? '@' }}" class="w-28 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5 font-mono text-xs"></td>
                                    <td class="px-3 py-2"><input name="records[{{ $i }}][value]" value="{{ $r['value'] ?? '' }}" class="w-full min-w-[200px] rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5 font-mono text-xs"></td>
                                    <td class="px-3 py-2"><input name="records[{{ $i }}][ttl]" type="number" value="{{ $r['ttl'] ?? 3600 }}" class="w-20 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5"></td>
                                    <td class="px-3 py-2"><input name="records[{{ $i }}][priority]" type="number" value="{{ $r['priority'] ?? '' }}" class="w-16 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5" placeholder="—"></td>
                                    <td class="px-3 py-2"><button type="button" onclick="this.closest('tr').remove()" class="text-sm font-medium text-red-600 hover:underline">Sil</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-hv-muted">Kaydettiğinizde tablodaki liste geçerli DNS seti olarak uygulanır; listede olmayan kayıtlar kaldırılır.</p>
                <button class="btn-primary mt-4">DNS kayıtlarını kaydet</button>
            </form>
        </div>
    </div>

    {{-- Nameserver --}}
    <div class="domain-panel mt-6 hidden" data-panel="ns">
        <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5 sm:p-6">
            <h3 class="text-base font-semibold text-hv-text">Nameserver (NS) ayarları</h3>
            <p class="mt-1 text-sm text-hv-muted">Alan adınızın DNS trafiğini yönlendireceği sunucuları belirleyin.</p>

            <form method="POST" action="{{ route('account.domains.nameservers', $domain->id) }}" class="mt-5 space-y-5" id="ns-form">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="relative flex cursor-pointer rounded-2xl border-2 p-4 transition {{ !$isCustomNs ? 'border-hv-primary bg-hv-primary/5' : 'border-hv-border hover:border-hv-primary/40' }}">
                        <input type="radio" name="provider" value="basic" class="sr-only" {{ !$isCustomNs ? 'checked' : '' }} onchange="toggleNsCustom(false)">
                        <div>
                            <p class="font-semibold text-hv-text">{{ $brandName }} DNS</p>
                            <p class="mt-1 text-sm text-hv-muted">Önerilen — DNS kayıtlarını bu panelden yönetin.</p>
                            @if(!empty($defaultNameservers))
                                <ul class="mt-3 space-y-1 font-mono text-xs text-hv-text">
                                    @foreach($defaultNameservers as $ns)
                                        <li>{{ $ns }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </label>
                    <label class="relative flex cursor-pointer rounded-2xl border-2 p-4 transition {{ $isCustomNs ? 'border-hv-primary bg-hv-primary/5' : 'border-hv-border hover:border-hv-primary/40' }}">
                        <input type="radio" name="provider" value="custom" class="sr-only" {{ $isCustomNs ? 'checked' : '' }} onchange="toggleNsCustom(true)">
                        <div>
                            <p class="font-semibold text-hv-text">Özel nameserver</p>
                            <p class="mt-1 text-sm text-hv-muted">Harici hosting veya CDN sağlayıcınızın NS adreslerini kullanın.</p>
                        </div>
                    </label>
                </div>
                <div id="ns-custom" class="{{ $isCustomNs ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-hv-text">Nameserver listesi</label>
                    <textarea name="hosts" rows="4" placeholder="ns1.ornek.com&#10;ns2.ornek.com" class="mt-2 w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2.5 font-mono text-sm">{{ collect($domain->nameservers ?? [])->implode("\n") }}</textarea>
                    <p class="mt-2 text-xs text-hv-muted">Her satıra bir nameserver yazın (en az 2 adet).</p>
                </div>
                <button class="btn-primary">Nameserver ayarlarını kaydet</button>
            </form>
        </div>
    </div>

    {{-- Yenileme --}}
    <div class="domain-panel mt-6 hidden" data-panel="renew">
        <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5 sm:p-6">
            <h3 class="text-base font-semibold text-hv-text">Süre uzat / yenile</h3>
            <p class="mt-1 text-sm text-hv-muted">Alan adı kaydınızı uzatmak için süre seçin. Ücret sipariş akışına göre tahsil edilir.</p>
            <form method="POST" action="{{ route('account.domains.renew', $domain->id) }}" class="mt-5 flex flex-wrap items-end gap-4">
                @csrf
                <label class="text-sm">
                    <span class="block font-medium text-hv-muted">Uzatma süresi</span>
                    <select name="years" class="mt-2 min-w-[8rem] rounded-xl border border-hv-border bg-hv-surface px-3 py-2.5 text-sm">
                        @for($y = 1; $y <= 10; $y++)
                            <option value="{{ $y }}">{{ $y }} yıl</option>
                        @endfor
                    </select>
                </label>
                <button class="btn-primary" onclick="return confirm('Seçilen süre için yenileme işlemi başlatılacak. Onaylıyor musunuz?')">Yenile</button>
            </form>
        </div>
    </div>

    {{-- Devir --}}
    <div class="domain-panel mt-6 hidden" data-panel="transfer">
        <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5 sm:p-6">
            <h3 class="text-base font-semibold text-hv-text">Başka hesaba devret</h3>
            @if(!empty($pendingTransfer))
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                    <p><strong>{{ $pendingTransfer->target_email }}</strong> adresine devir talebiniz <strong>onay bekliyor</strong> (No: {{ $pendingTransfer->number }}).</p>
                    <form method="POST" action="{{ route('account.transfers.cancel', $pendingTransfer->id) }}" class="mt-3">
                        @csrf
                        <button class="rounded-xl border border-amber-300 bg-white px-4 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100">Talebi iptal et</button>
                    </form>
                </div>
            @else
                <p class="mt-2 text-sm text-hv-muted">Bu alan adını başka bir {{ $brandName }} hesabına devredebilirsiniz. Devralacak kişinin kayıtlı olması gerekir; talep yönetici onayından sonra tamamlanır.</p>
                <form method="POST" action="{{ route('account.transfers.domain', $domain->id) }}" class="mt-5 grid max-w-lg gap-4">
                    @csrf
                    <label class="text-sm">
                        <span class="block font-medium text-hv-muted">Devralacak hesabın e-postası</span>
                        <input type="email" name="target_email" required placeholder="devralacak@eposta.com"
                            class="mt-2 w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2.5 text-sm" value="{{ old('target_email') }}">
                    </label>
                    @error('target_email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <label class="text-sm">
                        <span class="block font-medium text-hv-muted">Not (opsiyonel)</span>
                        <textarea name="note" rows="3" class="mt-2 w-full rounded-xl border border-hv-border bg-hv-surface px-3 py-2.5 text-sm">{{ old('note') }}</textarea>
                    </label>
                    <div>
                        <button class="btn-primary" onclick="return confirm('{{ $domain->domain }} için devir talebi oluşturulacak. Onaylıyor musunuz?')">Devir talebi gönder</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<script>
    let dnsIndex = {{ count($records) }};

    document.querySelectorAll('.domain-tab').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.tab;
            document.querySelectorAll('.domain-tab').forEach((b) => {
                b.classList.remove('bg-hv-primary', 'text-white', 'shadow-sm');
                b.classList.add('text-hv-muted', 'hover:bg-hv-surface', 'hover:text-hv-text');
            });
            btn.classList.add('bg-hv-primary', 'text-white', 'shadow-sm');
            btn.classList.remove('text-hv-muted', 'hover:bg-hv-surface', 'hover:text-hv-text');
            document.querySelectorAll('.domain-panel').forEach((p) => {
                p.classList.toggle('hidden', p.dataset.panel !== id);
            });
        });
    });

    function toggleNsCustom(show) {
        document.getElementById('ns-custom').classList.toggle('hidden', !show);
    }

    function addDnsRow() {
        const types = ['A','AAAA','CNAME','MX','TXT','NS','CAA','SRV','ALIAS'];
        const i = dnsIndex++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-3 py-2"><select name="records[${i}][type]" class="w-full rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5">${types.map(t => `<option value="${t}">${t}</option>`).join('')}</select></td>
            <td class="px-3 py-2"><input name="records[${i}][name]" value="@" class="w-28 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5 font-mono text-xs"></td>
            <td class="px-3 py-2"><input name="records[${i}][value]" value="" class="w-full min-w-[200px] rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5 font-mono text-xs"></td>
            <td class="px-3 py-2"><input name="records[${i}][ttl]" type="number" value="3600" class="w-20 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5"></td>
            <td class="px-3 py-2"><input name="records[${i}][priority]" type="number" value="" class="w-16 rounded-lg border border-hv-border bg-hv-surface px-2 py-1.5" placeholder="—"></td>
            <td class="px-3 py-2"><button type="button" onclick="this.closest('tr').remove()" class="text-sm font-medium text-red-600 hover:underline">Sil</button></td>`;
        document.getElementById('dns-rows').appendChild(tr);
    }
</script>
@endsection
