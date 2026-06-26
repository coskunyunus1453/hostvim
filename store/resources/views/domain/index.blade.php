@extends('layouts.app')

@section('title', 'Alan Adı Sorgula — Domain Kayıt, Transfer & Yenileme')

@section('content')
@php
    $currency = $currency ?? 'TRY';
    $symbol = ['TRY' => '₺', 'USD' => '$', 'EUR' => '€'][$currency] ?? '₺';
@endphp

{{-- ===== HERO / ARAMA ===== --}}
<section class="relative overflow-hidden bg-hv-gradient">
    <div class="pointer-events-none absolute inset-0 opacity-20"
         style="background-image:radial-gradient(circle at 20% 20%, #fff 1px, transparent 1px);background-size:32px 32px;"></div>
    <div class="relative mx-auto max-w-4xl px-4 py-16 text-center lg:px-8 lg:py-20">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold text-white backdrop-blur">
            ⚡ Saniyeler içinde müsaitlik & fiyat
        </span>
        <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
            Alan adını hemen bul
        </h1>
        <p class="mx-auto mt-3 max-w-xl text-white/85">
            Yeni kayıt, transfer ve yenileme fiyatlarını tek ekranda gör. Müsaitse anında sepete ekle.
        </p>

        <form id="domain-search-form" class="mx-auto mt-8 flex max-w-2xl flex-col gap-2 sm:flex-row">
            @csrf
            <div class="relative flex-1">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-hv-muted">🔍</span>
                <input type="text" id="domain-input" name="domain" placeholder="markanizi-yazin.com" required autocomplete="off"
                    class="w-full rounded-2xl border-0 bg-white py-4 pl-11 pr-4 text-base text-hv-text shadow-lg outline-none ring-2 ring-transparent focus:ring-white/60">
            </div>
            <button type="submit"
                class="rounded-2xl bg-hv-text px-8 py-4 text-base font-bold text-white shadow-lg transition hover:opacity-90">
                Ara
            </button>
        </form>

        @if(count($tlds) > 0)
            <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                <span class="text-xs font-medium text-white/70">Popüler:</span>
                @foreach(array_slice($tlds, 0, 8) as $tld)
                    <button type="button"
                        class="tld-pick rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur transition hover:bg-white/25"
                        data-tld="{{ $tld['tld'] }}">{{ $tld['tld'] }}</button>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ===== SONUÇLAR ===== --}}
<section class="bg-hv-bg py-10 lg:py-14">
    <div class="mx-auto max-w-5xl px-4 lg:px-8">

        {{-- Yükleniyor --}}
        <div id="domain-loading" class="hidden">
            <div class="flex items-center justify-center gap-3 rounded-2xl border border-hv-border bg-hv-elevated py-10 text-hv-muted">
                <span class="h-5 w-5 animate-spin rounded-full border-2 border-hv-primary border-t-transparent"></span>
                Müsaitlik ve fiyatlar kontrol ediliyor…
            </div>
        </div>

        {{-- Hata --}}
        <div id="domain-error" class="hidden rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"></div>

        {{-- Ana sonuç --}}
        <div id="primary-result" class="hidden"></div>

        {{-- Öneriler --}}
        <div id="suggestions-wrap" class="hidden mt-8">
            <div class="mb-4 flex items-center gap-2">
                <h2 class="text-lg font-bold text-hv-text">Bunlar da müsait</h2>
                <span class="text-sm text-hv-muted">— diğer uzantılar</span>
            </div>
            <div id="suggestions-grid" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"></div>
        </div>
    </div>
</section>

{{-- ===== TÜM FİYATLAR ===== --}}
@if(count($tlds) > 0)
<section class="border-t border-hv-border bg-hv-surface py-14">
    <div class="mx-auto max-w-5xl px-4 lg:px-8">
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="section-title">Tüm uzantılar & fiyatları</h2>
                <p class="mt-1 text-sm text-hv-muted">Yeni kayıt, yenileme ve transfer fiyatları. Fiyatlara KDV dahil değildir.</p>
            </div>
            <div class="relative w-full sm:w-64">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-hv-muted">🔍</span>
                <input type="text" id="tld-filter" placeholder="Uzantı ara (.com, .io…)"
                    class="w-full rounded-xl border border-hv-border bg-hv-elevated py-2.5 pl-9 pr-3 text-sm text-hv-text outline-none focus:border-hv-primary">
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-hv-border bg-hv-elevated">
            <table class="w-full text-sm">
                <thead class="bg-hv-surface text-left text-xs uppercase tracking-wide text-hv-muted">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Uzantı</th>
                        <th class="px-5 py-3 text-right font-semibold">Yeni Kayıt</th>
                        <th class="px-5 py-3 text-right font-semibold">Yenileme</th>
                        <th class="px-5 py-3 text-right font-semibold">Transfer</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody id="tld-table-body" class="divide-y divide-hv-border">
                    @foreach($tlds as $tld)
                        <tr class="tld-row transition hover:bg-hv-surface {{ $loop->index >= 10 ? 'tld-extra hidden' : '' }}" data-tld="{{ $tld['tld'] }}">
                            <td class="px-5 py-3 font-bold text-hv-text">{{ $tld['tld'] }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-hv-primary">{{ $symbol }}{{ number_format($tld['register_price'], 2, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right text-hv-muted">{{ $symbol }}{{ number_format($tld['renew_price'], 2, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right text-hv-muted">{{ $symbol }}{{ number_format($tld['transfer_price'] ?? $tld['renew_price'], 2, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right">
                                <button type="button" class="tld-row-search rounded-lg border border-hv-border px-3 py-1 text-xs font-semibold text-hv-text transition hover:border-hv-primary hover:text-hv-primary" data-tld="{{ $tld['tld'] }}">Sorgula</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div id="tld-empty" class="hidden px-5 py-8 text-center text-sm text-hv-muted">Eşleşen uzantı bulunamadı.</div>
        </div>

        @if(count($tlds) > 10)
            <div id="tld-more-wrap" class="mt-5 text-center">
                <button type="button" id="tld-more-btn"
                    class="inline-flex items-center gap-2 rounded-xl border border-hv-border bg-hv-elevated px-6 py-2.5 text-sm font-semibold text-hv-text transition hover:border-hv-primary hover:text-hv-primary">
                    Devamını gör <span class="text-hv-muted">({{ count($tlds) - 10 }} uzantı daha)</span>
                </button>
            </div>
        @endif
    </div>
</section>
@endif

{{-- ===== NEDEN HOSTVIM ===== --}}
<section class="border-t border-hv-border bg-hv-bg py-14">
    <div class="mx-auto max-w-5xl px-4 lg:px-8">
        <h2 class="section-title text-center">Neden HostVim ile alan adı?</h2>
        <p class="mx-auto mt-2 max-w-2xl text-center text-sm text-hv-muted">Alan adınızı kaydetmek, yönetmek ve büyütmek için ihtiyacınız olan her şey tek panelde.</p>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $features = [
                    ['⚡', 'Anında tescil', 'Kartla ödemede alan adınız saniyeler içinde adınıza kaydedilir.'],
                    ['🛡️', 'Ücretsiz WHOIS gizliliği', 'Kişisel bilgileriniz desteklenen uzantılarda gizli tutulur.'],
                    ['🧭', 'Kolay DNS yönetimi', 'NS ve DNS kayıtlarınızı müşteri panelinden dilediğiniz gibi yönetin.'],
                    ['🔁', 'Sorunsuz transfer', 'Mevcut alan adlarınızı kolayca HostVim\'e taşıyın, 1 yıl ek süre kazanın.'],
                ];
            @endphp
            @foreach($features as $f)
                <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5">
                    <div class="text-2xl">{{ $f[0] }}</div>
                    <h3 class="mt-3 font-bold text-hv-text">{{ $f[1] }}</h3>
                    <p class="mt-1 text-sm text-hv-muted">{{ $f[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== UZANTILAR HAKKINDA (SEO İÇERİK) ===== --}}
@if(!empty($tldContents))
<section class="border-t border-hv-border bg-hv-surface py-14">
    <div class="mx-auto max-w-5xl px-4 lg:px-8">
        <div class="text-center">
            <h2 class="section-title">Popüler uzantılar hakkında</h2>
            <p class="mx-auto mt-2 max-w-2xl text-sm text-hv-muted">Hangi uzantının size uygun olduğunu seçin. Her uzantının kullanım alanı ve kayıt operatörü bilgisi aşağıda.</p>
        </div>

        <div class="mt-8 grid items-stretch gap-4 md:grid-cols-2">
            @foreach($tldContents as $tld => $c)
                <article id="uzanti-{{ trim($tld, '.') }}" class="flex h-full flex-col rounded-2xl border border-hv-border bg-hv-elevated p-6">
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center rounded-xl bg-hv-gradient px-4 py-2 text-lg font-extrabold text-white">{{ $tld }}</span>
                        <button type="button" class="tld-pick shrink-0 rounded-lg border border-hv-primary px-3 py-1.5 text-xs font-bold text-hv-primary transition hover:bg-hv-primary hover:text-white" data-tld="{{ $tld }}">Sorgula</button>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-hv-text">{{ $c['title'] }}</h3>
                    <div class="mt-2 flex-1 space-y-2">
                        @foreach($c['paragraphs'] as $p)
                            <p class="text-sm leading-relaxed text-hv-muted">{{ $p }}</p>
                        @endforeach
                    </div>
                    <dl class="mt-5 grid grid-cols-1 gap-3 border-t border-hv-border pt-4 text-xs sm:grid-cols-2">
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-hv-muted">Kayıt operatörü</dt>
                            <dd class="mt-0.5 text-hv-text">{{ $c['registry'] }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-hv-muted">Adres</dt>
                            <dd class="mt-0.5 text-hv-muted">{{ $c['address'] }}</dd>
                        </div>
                    </dl>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===== SIKÇA SORULAN SORULAR ===== --}}
@if(!empty($faqs))
<section class="border-t border-hv-border bg-hv-bg py-14">
    <div class="mx-auto max-w-3xl px-4 lg:px-8">
        <div class="text-center">
            <h2 class="section-title">Sıkça sorulan sorular</h2>
            <p class="mx-auto mt-2 max-w-2xl text-sm text-hv-muted">Alan adı kaydı, transferi ve yönetimi hakkında merak edilenler.</p>
        </div>

        <div class="mt-8 space-y-3">
            @foreach($faqs as $faq)
                <details class="group rounded-2xl border border-hv-border bg-hv-elevated p-5 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold text-hv-text">
                        <span>{{ $faq['q'] }}</span>
                        <span class="shrink-0 text-hv-primary transition group-open:rotate-45">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-hv-muted">{{ $faq['a'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($faqs)->map(fn ($f) => [
        '@type' => 'Question',
        'name' => $f['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
    ])->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@endif

@push('scripts')
<script>
(function () {
    const SYMBOL = @json($symbol);
    const routes = {
        search: @json(route('domain.search')),
        add: @json(route('domain.cart.add')),
        whois: @json(route('domain.whois')),
    };
    const csrf = @json(csrf_token());

    const els = {
        form: document.getElementById('domain-search-form'),
        input: document.getElementById('domain-input'),
        loading: document.getElementById('domain-loading'),
        error: document.getElementById('domain-error'),
        primary: document.getElementById('primary-result'),
        sugWrap: document.getElementById('suggestions-wrap'),
        sugGrid: document.getElementById('suggestions-grid'),
    };

    const fmt = (n) => SYMBOL + Number(n || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = (s) => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function priceRow(label, value, strong) {
        return `<div class="flex items-baseline justify-between gap-3">
            <span class="text-xs text-hv-muted">${label}</span>
            <span class="${strong ? 'text-base font-bold text-hv-primary' : 'text-sm font-medium text-hv-text'}">${fmt(value)}<span class="text-xs font-normal text-hv-muted">/yıl</span></span>
        </div>`;
    }

    function primaryCard(d) {
        if (d.available) {
            return `<div class="overflow-hidden rounded-2xl border-2 border-green-500/40 bg-hv-elevated shadow-lg">
                <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-green-500/15 text-green-600">✓</span>
                            <span class="text-xs font-bold uppercase tracking-wide text-green-600">Müsait</span>
                        </div>
                        <p class="mt-2 text-2xl font-extrabold text-hv-text">${esc(d.domain)}</p>
                        <p class="mt-1 text-sm text-hv-muted">Tebrikler, bu alan adı boşta! Hemen kaydedebilirsin.</p>
                    </div>
                    <div class="shrink-0 sm:w-64">
                        <div class="space-y-1.5 rounded-xl bg-hv-surface p-4">
                            ${priceRow('Yeni kayıt', d.register_price, true)}
                            ${priceRow('Yenileme', d.renew_price)}
                            ${priceRow('Transfer', d.transfer_price)}
                        </div>
                        <button type="button" class="add-cart mt-3 w-full rounded-xl bg-hv-primary px-5 py-3 text-sm font-bold text-white shadow transition hover:opacity-90" data-domain="${esc(d.domain)}">
                            Sepete Ekle — ${fmt(d.register_price)}
                        </button>
                    </div>
                </div>
            </div>`;
        }
        const reasonMap = {
            tld_not_supported: 'Bu uzantı şu an satışta değil.',
            tldnotsupported: 'Bu uzantı şu an satışta değil.',
            disabled: 'Alan adı kaydı şu an kapalı.',
            unverified: 'Müsaitlik şu an doğrulanamadı, lütfen tekrar deneyin.',
        };
        const reason = reasonMap[d.reason] || 'Bu alan adı kayıtlı. Sahibiyseniz bize transfer edebilirsiniz.';
        const isRegistered = !['tld_not_supported', 'tldnotsupported', 'disabled', 'unverified'].includes(d.reason);
        const whoisBtn = isRegistered
            ? `<button type="button" class="whois-btn mt-4 inline-flex items-center gap-2 rounded-xl border border-hv-border bg-hv-surface px-4 py-2 text-sm font-semibold text-hv-text transition hover:border-hv-primary" data-domain="${esc(d.domain)}">
                    <span>🔎</span> Sahiplik bilgisi (WHOIS)
                </button>
                <div id="whois-box" class="mt-3 hidden"></div>`
            : '';
        return `<div class="overflow-hidden rounded-2xl border-2 border-amber-400/40 bg-hv-elevated shadow-lg">
            <div class="flex flex-col gap-4 p-6">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-400/20 text-amber-600">!</span>
                        <span class="text-xs font-bold uppercase tracking-wide text-amber-600">Müsait değil</span>
                    </div>
                    <p class="mt-2 text-2xl font-extrabold text-hv-text">${esc(d.domain)}</p>
                    <p class="mt-1 text-sm text-hv-muted">${reason}</p>
                    ${whoisBtn}
                </div>
            </div>
        </div>`;
    }

    function whoisRow(label, value) {
        if (!value) return '';
        return `<div class="flex items-start justify-between gap-4 border-b border-hv-border py-2 last:border-0">
            <span class="shrink-0 text-xs font-medium uppercase tracking-wide text-hv-muted">${label}</span>
            <span class="text-right text-sm font-medium text-hv-text break-all">${esc(value)}</span>
        </div>`;
    }

    function fmtDate(s) {
        if (!s) return '';
        const d = new Date(s);
        if (isNaN(d)) return s;
        return d.toLocaleDateString('tr-TR', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function whoisCard(w) {
        if (!w || w.ok === false) {
            return `<p class="rounded-xl bg-hv-surface p-4 text-sm text-hv-muted">Sahiplik bilgisi şu an alınamadı. Bazı uzantılar WHOIS/RDAP sorgusunu desteklemeyebilir.</p>`;
        }
        if (w.registered === false) {
            return `<p class="rounded-xl bg-hv-surface p-4 text-sm text-hv-muted">Bu alan adı için kayıt bulunamadı.</p>`;
        }
        const owner = w.registrant_org || w.registrant
            || (w.privacy_protected ? 'Gizlilik koruması ile gizlenmiş' : null);
        const ns = (w.name_servers || []).slice(0, 4).join(', ');
        const status = (w.statuses || []).slice(0, 3).join(', ');
        const rows = [
            whoisRow('Sahip', owner),
            whoisRow('Ülke', w.registrant_country),
            whoisRow('Kayıt firması', w.registrar),
            whoisRow('Kayıt tarihi', fmtDate(w.created_at)),
            whoisRow('Bitiş tarihi', fmtDate(w.expires_at)),
            whoisRow('Son güncelleme', fmtDate(w.updated_at)),
            whoisRow('Ad sunucuları', ns),
            whoisRow('Durum', status),
        ].filter(Boolean).join('');
        const privacyNote = w.privacy_protected
            ? `<p class="mt-3 text-xs text-hv-muted">ℹ️ Sahip kişisel bilgileri gizlilik (GDPR/WHOIS privacy) nedeniyle gizlenmiştir. Kayıt firması ve teknik bilgiler gösterilir.</p>`
            : '';
        return `<div class="rounded-xl border border-hv-border bg-hv-surface p-4">
            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-hv-muted">WHOIS / RDAP kayıt bilgisi</p>
            <div class="divide-hv-border">${rows || '<p class="text-sm text-hv-muted">Detay bulunamadı.</p>'}</div>
            ${privacyNote}
        </div>`;
    }

    async function loadWhois(domain, btn) {
        const box = document.getElementById('whois-box');
        if (!box) return;
        if (!box.classList.contains('hidden') && box.dataset.domain === domain) {
            box.classList.add('hidden');
            return;
        }
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span> Sorgulanıyor…';
        box.classList.remove('hidden');
        box.dataset.domain = domain;
        box.innerHTML = `<p class="rounded-xl bg-hv-surface p-4 text-sm text-hv-muted">WHOIS sorgulanıyor…</p>`;
        try {
            const res = await fetch(routes.whois, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ domain }),
            });
            const data = await res.json();
            box.innerHTML = whoisCard(data);
        } catch (e) {
            box.innerHTML = whoisCard(null);
        }
        btn.disabled = false;
        btn.innerHTML = original;
    }

    function suggestionCard(d) {
        if (!d.available) return '';
        return `<div class="flex items-center justify-between gap-3 rounded-xl border border-hv-border bg-hv-elevated p-4 transition hover:border-hv-primary hover:shadow">
            <div class="min-w-0">
                <p class="truncate font-bold text-hv-text">${esc(d.domain)}</p>
                <p class="mt-0.5 text-xs text-hv-muted">Yenileme ${fmt(d.renew_price)} · Transfer ${fmt(d.transfer_price)}</p>
            </div>
            <div class="shrink-0 text-right">
                <p class="text-sm font-bold text-hv-primary">${fmt(d.register_price)}</p>
                <button type="button" class="add-cart mt-1 rounded-lg border border-hv-primary px-3 py-1 text-xs font-bold text-hv-primary transition hover:bg-hv-primary hover:text-white" data-domain="${esc(d.domain)}">Ekle</button>
            </div>
        </div>`;
    }

    async function runSearch(query) {
        query = (query || '').trim();
        if (!query) return;
        if (!query.includes('.')) query += '.com';
        els.input.value = query;

        els.error.classList.add('hidden');
        els.primary.classList.add('hidden');
        els.sugWrap.classList.add('hidden');
        els.loading.classList.remove('hidden');
        els.loading.scrollIntoView({ behavior: 'smooth', block: 'center' });

        try {
            const res = await fetch(routes.search, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ domain: query }),
            });
            const data = await res.json();
            els.loading.classList.add('hidden');

            if (!res.ok) {
                els.error.textContent = data.message || 'Sorgu başarısız oldu.';
                els.error.classList.remove('hidden');
                return;
            }

            els.primary.innerHTML = primaryCard(data.primary);
            els.primary.classList.remove('hidden');

            const cards = (data.suggestions || []).map(suggestionCard).filter(Boolean);
            if (cards.length) {
                els.sugGrid.innerHTML = cards.join('');
                els.sugWrap.classList.remove('hidden');
            }
        } catch (e) {
            els.loading.classList.add('hidden');
            els.error.textContent = 'Bağlantı hatası. Lütfen tekrar deneyin.';
            els.error.classList.remove('hidden');
        }
    }

    async function addToCart(domain, btn) {
        const original = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Ekleniyor…';
        try {
            const res = await fetch(routes.add, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ domain, years: 1 }),
            });
            const data = await res.json();
            if (res.ok && data.redirect) { window.location = data.redirect; return; }
            alert(data.message || 'Sepete eklenemedi.');
        } catch (e) {
            alert('Sepete eklenemedi.');
        }
        btn.disabled = false;
        btn.textContent = original;
    }

    // Form gönderimi
    els.form.addEventListener('submit', (e) => { e.preventDefault(); runSearch(els.input.value); });

    // Sepete ekle + WHOIS (delegasyon)
    document.addEventListener('click', (e) => {
        const add = e.target.closest('.add-cart');
        if (add) { addToCart(add.dataset.domain, add); return; }
        const wb = e.target.closest('.whois-btn');
        if (wb) { loadWhois(wb.dataset.domain, wb); }
    });

    // Popüler uzantı çipi
    document.querySelectorAll('.tld-pick').forEach(btn => btn.addEventListener('click', () => {
        const base = (els.input.value.replace(/\.[a-z0-9.]+$/i, '') || 'markam');
        runSearch(base + btn.dataset.tld);
    }));

    // Fiyat tablosundan sorgula
    document.querySelectorAll('.tld-row-search').forEach(btn => btn.addEventListener('click', () => {
        const base = (els.input.value.replace(/\.[a-z0-9.]+$/i, '') || 'markam');
        runSearch(base + btn.dataset.tld);
    }));

    // Tablo filtresi + "Devamını gör" (ilk 10 göster, kalanı aç)
    const filter = document.getElementById('tld-filter');
    const moreWrap = document.getElementById('tld-more-wrap');
    const moreBtn = document.getElementById('tld-more-btn');
    const COLLAPSE_LIMIT = 10;
    let tldExpanded = false;

    function applyTldFilter() {
        const q = (filter ? filter.value : '').trim().toLowerCase();
        let visible = 0;
        document.querySelectorAll('.tld-row').forEach((row, idx) => {
            const match = row.dataset.tld.toLowerCase().includes(q);
            // Arama boşken ve daraltılmışken yalnızca ilk 10 satırı göster
            const show = q === '' && !tldExpanded ? (match && idx < COLLAPSE_LIMIT) : match;
            row.classList.toggle('hidden', !show);
            if (match) visible++;
        });
        const empty = document.getElementById('tld-empty');
        if (empty) empty.classList.toggle('hidden', visible !== 0);
        // "Devamını gör" yalnızca daraltılmış + arama boşken görünür
        if (moreWrap) moreWrap.classList.toggle('hidden', tldExpanded || q !== '');
    }

    if (moreBtn) {
        moreBtn.addEventListener('click', () => { tldExpanded = true; applyTldFilter(); });
    }
    if (filter) {
        filter.addEventListener('input', applyTldFilter);
    }

    // URL ?q= / ?domain= ile otomatik arama (ana sayfadan yönlendirme)
    const params = new URLSearchParams(window.location.search);
    const auto = params.get('q') || params.get('domain');
    if (auto) runSearch(auto);
})();
</script>
@endpush
@endsection
