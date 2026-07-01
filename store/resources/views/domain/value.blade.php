@extends('layouts.app')

@section('title', 'Domain Değer Sorgulama — Alan Adı Tahmini Piyasa Değeri')

@section('content')
@php
    $hero = $content['hero'] ?? [];
    $sections = $content['sections'] ?? [];
@endphp

{{-- ===== HERO / ARAÇ ===== --}}
<section class="relative overflow-hidden bg-hv-gradient">
    <div class="pointer-events-none absolute inset-0 opacity-20"
         style="background-image:radial-gradient(circle at 20% 20%, #fff 1px, transparent 1px);background-size:32px 32px;"></div>
    <div class="pointer-events-none absolute -right-20 top-8 h-56 w-56 rounded-full bg-white/10 blur-3xl"></div>
    <div class="relative mx-auto max-w-4xl px-4 py-16 text-center lg:px-8 lg:py-20">
        @if(!empty($hero['badge']))
            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold text-white backdrop-blur">
                {{ $hero['badge'] }}
            </span>
        @endif
        <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
            {{ $hero['title'] ?? 'Domain Değer Sorgulama' }}
        </h1>
        @if(!empty($hero['subtitle']))
            <p class="mx-auto mt-3 max-w-2xl text-base text-white/85">{{ $hero['subtitle'] }}</p>
        @endif

        <form id="value-search-form" class="mx-auto mt-8 flex max-w-2xl flex-col gap-2 sm:flex-row">
            @csrf
            <div class="relative flex-1">
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-hv-muted">💎</span>
                <input type="text" id="value-domain-input" name="domain" placeholder="ornek.com" required autocomplete="off"
                    class="w-full rounded-2xl border-0 bg-white py-4 pl-11 pr-4 text-base text-hv-text shadow-lg outline-none ring-2 ring-transparent focus:ring-white/60">
            </div>
            <button type="submit"
                class="rounded-2xl bg-hv-text px-8 py-4 text-base font-bold text-white shadow-lg transition hover:opacity-90">
                Değerini Hesapla
            </button>
        </form>
        <p class="mt-3 text-xs text-white/70">Ücretsiz · Tahmini değer referans amaçlıdır</p>
    </div>
</section>

{{-- ===== SONUÇ ===== --}}
<section class="bg-hv-bg py-10 lg:py-14">
    <div class="mx-auto max-w-3xl px-4 lg:px-8">
        <div id="value-loading" class="hidden">
            <div class="flex items-center justify-center gap-3 rounded-2xl border border-hv-border bg-hv-elevated py-10 text-hv-muted">
                <span class="h-5 w-5 animate-spin rounded-full border-2 border-hv-primary border-t-transparent"></span>
                Alan adı analiz ediliyor…
            </div>
        </div>
        <div id="value-error" class="hidden rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"></div>
        <div id="value-result" class="hidden"></div>
    </div>
</section>

{{-- ===== KRİTERLER ÖZET ===== --}}
<section class="border-t border-hv-border bg-hv-surface py-12">
    <div class="mx-auto max-w-5xl px-4 lg:px-8">
        <h2 class="section-title text-center">Değerlendirme kriterleri</h2>
        <p class="mx-auto mt-2 max-w-2xl text-center text-sm text-hv-muted">Tahminimiz altı ana kriterin ağırlıklı ortalamasına dayanır.</p>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach([
                ['🏷️', 'Uzantı (TLD)', '.com, .ai, .com.tr gibi uzantıların piyasa talebi'],
                ['📏', 'Uzunluk', 'Kısa alan adları genelde daha yüksek değer taşır'],
                ['✨', 'Karakter kalitesi', 'Harf, rakam ve tire yapısı'],
                ['🔑', 'Anahtar kelime', 'Ticari ve sektörel kelime eşleşmeleri'],
                ['🎯', 'Marka potansiyeli', 'Telaffuz ve akılda kalıcılık'],
                ['📅', 'Kayıt yaşı', 'WHOIS ile tespit edilen geçmiş'],
            ] as $c)
                <div class="rounded-2xl border border-hv-border bg-hv-elevated p-5">
                    <div class="text-2xl">{{ $c[0] }}</div>
                    <h3 class="mt-2 font-bold text-hv-text">{{ $c[1] }}</h3>
                    <p class="mt-1 text-sm text-hv-muted">{{ $c[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===== SEO MAKALE ===== --}}
@if(!empty($sections))
<section class="border-t border-hv-border bg-hv-bg py-14 lg:py-20">
    <div class="mx-auto max-w-3xl px-4 lg:px-8">
        <article class="prose-hv space-y-12">
            @foreach($sections as $section)
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-hv-text">{{ $section['title'] }}</h2>
                    <div class="mt-4 space-y-4 text-sm leading-relaxed text-hv-muted [&_h3]:mt-6 [&_h3]:text-base [&_h3]:font-bold [&_h3]:text-hv-text [&_ol]:list-decimal [&_ol]:pl-5 [&_ul]:list-disc [&_ul]:pl-5 [&_a]:font-semibold [&_a]:text-hv-primary [&_a]:underline">
                        {!! $section['body'] !!}
                    </div>
                </div>
            @endforeach
        </article>
    </div>
</section>
@endif

{{-- ===== SSS ===== --}}
@if(!empty($faqs))
<section class="border-t border-hv-border bg-hv-surface py-14">
    <div class="mx-auto max-w-3xl px-4 lg:px-8">
        <div class="text-center">
            <h2 class="section-title">Sıkça sorulan sorular</h2>
            <p class="mx-auto mt-2 max-w-2xl text-sm text-hv-muted">Domain değer sorgulama hakkında merak edilenler.</p>
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
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebApplication',
    'name' => 'Hostvim Domain Değer Sorgulama',
    'url' => route('domain.value.index'),
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Web',
    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'TRY'],
    'description' => 'Alan adı tahmini piyasa değeri hesaplama aracı',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@endif

@push('scripts')
<script>
(function () {
    const routes = { estimate: @json(route('domain.value.estimate')) };
    const csrf = @json(csrf_token());
    const form = document.getElementById('value-search-form');
    const input = document.getElementById('value-domain-input');
    const loading = document.getElementById('value-loading');
    const error = document.getElementById('value-error');
    const result = document.getElementById('value-result');

    const fmt = (n) => '₺' + Number(n || 0).toLocaleString('tr-TR', { maximumFractionDigits: 0 });
    const esc = (s) => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    const tierColors = {
        ultra: 'border-amber-500/60 bg-gradient-to-br from-amber-100/80 to-hv-elevated',
        premium: 'border-amber-400/50 bg-gradient-to-br from-amber-50 to-hv-elevated',
        iyi: 'border-green-500/40 bg-hv-elevated',
        orta: 'border-hv-primary/30 bg-hv-elevated',
        dusuk: 'border-hv-border bg-hv-elevated',
    };
    const tierBadge = {
        ultra: 'bg-amber-600/15 text-amber-800',
        premium: 'bg-amber-500/15 text-amber-700',
        iyi: 'bg-green-500/15 text-green-700',
        orta: 'bg-hv-primary/10 text-hv-primary',
        dusuk: 'bg-hv-surface text-hv-muted',
    };

    function criteriaRow(c) {
        const pct = Math.max(4, c.score);
        return `<div class="rounded-xl border border-hv-border bg-hv-surface p-4">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-hv-text">${esc(c.label)}</span>
                <span class="text-sm font-bold text-hv-primary">${c.score}/100</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-hv-border">
                <div class="h-full rounded-full bg-hv-primary transition-all" style="width:${pct}%"></div>
            </div>
            <p class="mt-2 text-xs text-hv-muted">${esc(c.detail)}</p>
        </div>`;
    }

    function resultCard(d) {
        const tier = d.tier || 'orta';
        const regLabel = d.registered === true ? 'Kayıtlı' : (d.registered === false ? 'Müsait' : 'Bilinmiyor');
        const regClass = d.registered === true ? 'text-amber-600' : (d.registered === false ? 'text-green-600' : 'text-hv-muted');
        const criteria = (d.criteria || []).map(criteriaRow).join('');

        const blendNote = d.blend
            ? `<p class="mt-2 text-xs text-violet-600">Karma değer: %${d.blend.ai} AI + %${d.blend.rules} kriter${d.rules_estimate != null ? ' · Kriter: '+fmt(d.rules_estimate)+' · AI: '+fmt(d.ai_estimate) : ''}</p>`
            : '';
        const aiFailNote = (!d.ai_powered && d.ai_status === 'failed')
            ? `<p class="mt-2 text-xs text-amber-600">${esc(d.ai_error || 'AI şu an devreye giremedi. Yalnızca kriter motoru kullanıldı.')}</p>`
            : '';

        const aiBadge = d.ai_powered
            ? '<span class="ml-2 inline-flex rounded-full bg-violet-500/15 px-2 py-0.5 text-[10px] font-bold text-violet-700">✨ AI destekli</span>'
            : '';
        const genericBadge = d.is_generic && d.generic_label
            ? `<span class="mt-2 inline-flex rounded-full bg-hv-primary/10 px-3 py-1 text-xs font-semibold text-hv-primary">${esc(d.generic_label)}</span>`
            : '';

        return `<div class="overflow-hidden rounded-2xl border-2 ${tierColors[tier] || tierColors.orta} shadow-lg">
            <div class="p-6 sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-1">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ${tierBadge[tier] || tierBadge.orta}">${esc(d.tier_label || 'Orta')}</span>
                            ${aiBadge}
                        </div>
                        <h2 class="mt-3 text-2xl font-extrabold text-hv-text sm:text-3xl">${esc(d.domain)}</h2>
                        ${genericBadge}
                        <p class="mt-1 text-sm ${regClass}">${regLabel}${d.age_years != null && d.registered ? ' · ' + d.age_years + ' yıl' : ''}</p>
                    </div>
                    <div class="text-center sm:text-right">
                        <p class="text-xs font-semibold uppercase tracking-wide text-hv-muted">Tahmini değer</p>
                        <p class="text-3xl font-extrabold text-hv-primary sm:text-4xl">${fmt(d.estimate)}</p>
                        <p class="mt-1 text-sm text-hv-muted">${fmt(d.low)} – ${fmt(d.high)}</p>
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-4 rounded-xl bg-hv-surface p-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-hv-primary/10 text-xl font-extrabold text-hv-primary">${d.score}</div>
                    <div>
                        <p class="font-bold text-hv-text">Genel skor: ${d.score}/100</p>
                        <p class="mt-0.5 text-sm text-hv-muted">${esc(d.summary || '')}</p>
                        ${d.reasoning ? `<p class="mt-2 text-sm leading-relaxed text-hv-text/80">${esc(d.reasoning)}</p>` : ''}
                        ${blendNote}
                        ${aiFailNote}
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    ${criteria}
                </div>

                <p class="mt-6 text-xs leading-relaxed text-hv-muted">⚠️ Bu tahmin istatistiksel modelle üretilmiştir; kesin satış fiyatı garantisi vermez. Yüksek tutarlı işlemlerde uzman görüşü alınması önerilir.</p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="/domain?q=${encodeURIComponent(d.domain)}" class="rounded-xl bg-hv-primary px-5 py-2.5 text-sm font-bold text-white transition hover:opacity-90">Domain Sorgula</a>
                    <a href="/web-hosting" class="rounded-xl border border-hv-border bg-hv-elevated px-5 py-2.5 text-sm font-semibold text-hv-text transition hover:border-hv-primary">Hosting Paketleri</a>
                </div>
            </div>
        </div>`;
    }

    async function runEstimate(query) {
        query = (query || '').trim();
        if (!query) return;
        if (!query.includes('.')) query += '.com';
        input.value = query;

        error.classList.add('hidden');
        result.classList.add('hidden');
        loading.classList.remove('hidden');
        loading.scrollIntoView({ behavior: 'smooth', block: 'center' });

        try {
            const res = await fetch(routes.estimate, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ domain: query }),
            });

            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseErr) {
                loading.classList.add('hidden');
                error.textContent = res.ok ? 'Sunucu yanıtı işlenemedi.' : ('Hata (' + res.status + '). Lütfen tekrar deneyin.');
                error.classList.remove('hidden');
                return;
            }

            loading.classList.add('hidden');

            if (!res.ok) {
                error.textContent = data.message || 'Hesaplama başarısız.';
                error.classList.remove('hidden');
                return;
            }

            result.innerHTML = resultCard(data);
            result.classList.remove('hidden');
        } catch (e) {
            loading.classList.add('hidden');
            error.textContent = 'Bağlantı hatası. Lütfen tekrar deneyin.';
            error.classList.remove('hidden');
        }
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); runEstimate(input.value); });

    const params = new URLSearchParams(window.location.search);
    const auto = params.get('q') || params.get('domain');
    if (auto) runEstimate(auto);
})();
</script>
@endpush
@endsection
