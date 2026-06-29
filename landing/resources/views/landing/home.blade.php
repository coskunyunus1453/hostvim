@php
    $tr = app()->getLocale() === 'tr';
    $neon = \App\Services\LandingAppearance::isNeonTheme();
    $pricing = $pricing ?? null;
    $oneLiner = \App\Services\InstallGuide::oneLiner();
@endphp
<x-layouts.landing>
    @unless ($neon)
    {{-- ============ HERO (merkezli · sade) ============ --}}
    <section class="relative overflow-hidden pt-12 sm:pt-16 lg:pt-24">
        <div class="hv-container">
            <div class="mx-auto max-w-3xl text-center">
                <div class="mb-6 flex flex-wrap items-center justify-center gap-2">
                    <span class="hv-pill">
                        <span class="hv-accent-dot h-2 w-2 rounded-full shadow-[0_0_0_4px_rgb(var(--hv-brand-500)/0.25)]"></span>
                        {{ landing_p('home.hero_badge_engine') }}
                    </span>
                    <span class="hv-badge">
                        <span class="hv-accent-dot h-1.5 w-1.5 rounded-full opacity-90"></span>
                        {{ landing_p('home.hero_badge_model') }}
                    </span>
                </div>

                <h1 class="mx-auto max-w-2xl text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl lg:text-[3.4rem] lg:leading-[1.08] dark:text-slate-50">
                    {!! str_replace(
                        ':brand',
                        '<span class="hv-text-brand">'.e(landing_p('brand.name')).'</span>',
                        e(landing_p('home.hero_title'))
                    ) !!}
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ landing_p('home.hero_lead') }}
                </p>

                {{-- Gerçek tek satır kurulum komutu --}}
                <div x-data="{ copied: false }" class="mx-auto mt-8 flex max-w-xl items-center gap-2 rounded-2xl border border-slate-200/90 bg-slate-950 p-2 pl-4 text-left shadow-lg shadow-slate-900/10 dark:border-slate-700">
                    <span class="select-none font-mono text-sm text-emerald-400">$</span>
                    <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap font-mono text-sm text-slate-100">{{ $oneLiner }}</code>
                    <button type="button"
                            @click="navigator.clipboard.writeText(@js($oneLiner)); copied = true; setTimeout(() => copied = false, 1500)"
                            class="inline-flex flex-none items-center gap-1.5 rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold text-slate-100 transition hover:bg-white/20">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>
                        <span x-show="!copied">{{ $tr ? 'Kopyala' : 'Copy' }}</span>
                        <span x-show="copied" x-cloak>{{ $tr ? 'Kopyalandı' : 'Copied' }}</span>
                    </button>
                </div>
                <p class="mx-auto mt-2 max-w-xl text-xs text-slate-500 dark:text-slate-500">
                    {{ $tr ? 'Debian/Ubuntu sunucuda root/sudo · dakikalar içinde yayında' : 'On a Debian/Ubuntu server as root/sudo · live in minutes' }}
                </p>

                <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('site.pricing') }}" class="hv-btn-primary gap-2 px-6 py-3.5 text-base">
                        {{ landing_p('home.hero_cta_primary') }}
                        <span class="text-sm opacity-90">→</span>
                    </a>
                    <a href="{{ route('site.setup') }}" class="hv-btn-secondary gap-2 px-6 py-3.5 text-base">
                        {{ landing_p('home.hero_cta_secondary') }}
                    </a>
                </div>

                <div class="mt-7 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-slate-500 dark:text-slate-400">
                    @foreach ([
                        $tr ? 'Community kalıcı ücretsiz' : 'Community free forever',
                        $tr ? 'Kendi sunucunuzda' : 'Self-hosted',
                        $tr ? 'Kart gerekmez' : 'No card required',
                    ] as $trust)
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ $trust }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- istatistik şeridi --}}
            <div class="mx-auto mt-14 grid max-w-4xl grid-cols-2 gap-px overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-200/60 text-center sm:grid-cols-4 dark:border-slate-800 dark:bg-slate-800/60">
                @foreach ([
                    [$tr ? 'Tek komut' : 'One command', $tr ? 'ile kurulum' : 'to install'],
                    ['500', $tr ? 'site / sunucu' : 'sites / server'],
                    ['7+', $tr ? 'Pro modül' : 'Pro modules'],
                    ['7.4–8.4', 'PHP · Nginx/Apache/OLS'],
                ] as $stat)
                    <div class="bg-white/90 px-4 py-6 dark:bg-slate-900/70">
                        <div class="text-xl font-bold text-slate-900 sm:text-2xl dark:text-slate-50">{{ $stat[0] }}</div>
                        <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $stat[1] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endunless

    @if ($neon)
        @include('landing.partials.neon-hero')
        @include('landing.partials.neon-stack')
        @include('landing.partials.neon-grid')
    @endif

    @unless ($neon)
    {{-- ============ HIZ / PERFORMANS ============ --}}
    <section class="relative mt-20 lg:mt-28">
        <div class="hv-container">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <div class="hv-section-eyebrow justify-center">{{ $tr ? 'Hız & performans' : 'Speed & performance' }}</div>
                <h2 class="hv-section-title">{{ $tr ? 'Hafif motor, hızlı kurulum' : 'A lightweight engine, fast setup' }}</h2>
                <p class="hv-section-lead">{{ $tr ? 'Go tabanlı motor sunucu kaynağını yormaz; siteleriniz performans için optimize başlar.' : 'A Go-based engine that stays light on resources; your sites start tuned for performance.' }}</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-3">
                @php
                    $speed = $tr ? [
                        ['rocket', 'Dakikalar içinde yayında', 'Tek satır kurulum (get.panelze.com) ile boş Debian/Ubuntu sunucuya web sunucusu, PHP, veritabanı ve panel tek seferde gelir.'],
                        ['cpu', 'Go tabanlı motor', 'Vhost, TLS, DNS ve dosya işlemleri düşük RAM ve gecikmeyle uygulanır; panel Laravel 11 + React, motor loopback üzerinde çalışır.'],
                        ['layers', 'Performans için optimize', 'Nginx / Apache / OpenLiteSpeed + LiteSpeed cache, PHP-FPM havuzları, OPcache ve HTTP/2 ile hızlı sayfa yanıtları.'],
                    ] : [
                        ['rocket', 'Live in minutes', 'A single-line install (get.panelze.com) brings the web server, PHP, database and panel to a fresh Debian/Ubuntu box in one go.'],
                        ['cpu', 'Go-based engine', 'Vhosts, TLS, DNS and file ops apply with low RAM and latency; the panel is Laravel 11 + React, the engine runs on loopback.'],
                        ['layers', 'Tuned for performance', 'Nginx / Apache / OpenLiteSpeed + LiteSpeed cache, PHP-FPM pools, OPcache and HTTP/2 for fast page responses.'],
                    ];
                @endphp
                @foreach ($speed as $s)
                    <article class="hv-glass flex flex-col gap-3 rounded-2xl p-6">
                        <div class="hv-card-icon"><x-landing.feature-icon :name="$s[0]" /></div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $s[1] }}</h3>
                        <p class="text-base leading-relaxed text-slate-600 dark:text-slate-400">{{ $s[2] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ ÇEKİRDEK ÖZELLİKLER ============ --}}
    <section id="features" class="relative mt-20 lg:mt-28">
        <div class="hv-container">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <div class="hv-section-eyebrow justify-center">{{ landing_p('home.features_badge') }}</div>
                <h2 class="hv-section-title">{{ landing_p('home.features_title') }}</h2>
                <p class="hv-section-lead">{{ landing_p('home.features_lead') }}</p>
            </div>

            <div class="grid gap-5 text-base sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($landingFeatureCards ?? [] as $card)
                    <article class="hv-glass group flex flex-col gap-3 rounded-2xl p-6 transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="hv-card-icon">
                            <x-landing.feature-icon :name="$card['icon'] ?? 'layers'" />
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $card['title'] }}</h3>
                        <p class="text-base leading-relaxed text-slate-600 dark:text-slate-400">{{ $card['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ PRO MODÜLLER ============ --}}
    @if ($pricing && ! empty($pricing['pro_modules']))
    @php
        $homeBullets = \App\Support\PanelFeatureCatalog::proModuleMarketingBullets($tr ? 'tr' : 'en');
        $homeMods = [];
        foreach ($pricing['pro_modules'] as $m) {
            $b = $homeBullets[$m['key']] ?? ($m['label'].' — '.$m['description']);
            [$l, $d] = array_pad(explode(' — ', $b, 2), 2, '');
            $homeMods[] = ['key' => $m['key'], 'label' => $l, 'description' => $d];
        }
        $modIcon = ['phpmyadmin_sso'=>'database','security_pro'=>'shield','backups_pro'=>'layers','monitoring_advanced'=>'cpu','ai_advisor'=>'rocket','curious_tools'=>'terminal','stripe_billing'=>'layers','vendor_panel'=>'users'];
    @endphp
    <section class="relative mt-20 lg:mt-28">
        <div class="hv-container">
            <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/60 p-8 sm:p-10 dark:border-slate-800 dark:from-slate-900/70 dark:to-slate-950/40">
                <div class="mx-auto mb-10 max-w-2xl text-center">
                    <div class="hv-section-eyebrow justify-center">Panelze Pro</div>
                    <h2 class="hv-section-title">{{ $tr ? 'Lisansla açılan gelişmiş modüller' : 'Advanced modules unlocked by your license' }}</h2>
                    <p class="hv-section-lead">{{ $tr ? 'Ücretsiz çekirdeğin üzerine; güvenlik, yedekleme, izleme ve panel içi AI.' : 'On top of the free core: security, backups, monitoring, and in-panel AI.' }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($homeMods as $m)
                        <article class="flex flex-col gap-2 rounded-2xl border border-slate-200/80 bg-white/80 p-5 dark:border-slate-800 dark:bg-slate-900/60">
                            <div class="hv-card-icon">
                                <x-landing.feature-icon :name="$modIcon[$m['key']] ?? 'layers'" />
                            </div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $m['label'] }}</h3>
                            <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $m['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif
    @endunless

    {{-- ============ FİYATLANDIRMA ============ --}}
    <section id="pricing" class="relative mt-20 lg:mt-28 @if ($neon) hv-neon-page-section @endif">
        <div class="hv-container">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <div class="hv-section-eyebrow justify-center">{{ landing_p('home.pricing_badge') }}</div>
                <h2 class="hv-section-title">{{ landing_p('home.pricing_title') }}</h2>
                <p class="hv-section-lead">{{ landing_p('home.pricing_lead') }}</p>
            </div>

            @if ($pricing)
            @php $proY = $pricing['pro']['monthly'] ?? ($pricing['pro']['yearly'] ?? ($pricing['pro']['lifetime'] ?? null)); @endphp
            <div class="mx-auto grid max-w-3xl gap-5 sm:grid-cols-2">
                {{-- Free --}}
                <div class="flex flex-col rounded-3xl border border-slate-200/90 bg-white/90 p-7 dark:border-slate-800 dark:bg-slate-900/60">
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $pricing['free']['name'] }}</h3>
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $tr ? 'Ücretsiz' : 'Free' }}</span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-1.5">
                        <span class="text-4xl font-bold text-slate-900 dark:text-slate-50">{{ $pricing['free']['price_label'] }}</span>
                        <span class="text-sm text-slate-500">{{ $pricing['free']['period_label'] }}</span>
                    </div>
                    <ul class="mt-6 flex-1 space-y-2 text-base text-slate-700 dark:text-slate-300">
                        @foreach (array_slice($pricing['free']['features'], 0, 4) as $line)
                            <li class="flex gap-2"><span class="mt-0.5 text-emerald-500">✓</span><span>{{ $line }}</span></li>
                        @endforeach
                    </ul>
                    <a href="{{ route('site.setup') }}" class="hv-btn-secondary mt-6 w-full justify-center py-3 text-sm font-semibold">
                        {{ $tr ? 'Ücretsiz kur' : 'Install free' }}
                    </a>
                </div>

                {{-- Pro --}}
                <div class="hv-card-pro flex flex-col rounded-3xl p-7">
                    <span class="hv-card-pro-badge">{{ $tr ? 'En popüler' : 'Most popular' }}</span>
                    <h3 class="pr-16 text-lg font-semibold text-slate-900 dark:text-slate-50">Panelze Pro</h3>
                    @if ($proY)
                        <div class="mt-4 flex items-baseline gap-1.5">
                            <span class="text-4xl font-bold hv-text-brand">{{ $proY['price_label'] }}</span>
                            <span class="text-sm text-slate-500 dark:text-slate-400">{{ $proY['period_label'] }}</span>
                        </div>
                        <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">
                            {{ $tr ? 'Aylık · yıllık · ömür boyu seçenekleri' : 'Monthly · yearly · lifetime options' }}
                        </p>
                    @endif
                    <ul class="mt-6 flex-1 space-y-2 text-base text-slate-800 dark:text-slate-200">
                        <li class="flex gap-2"><span class="mt-0.5 hv-text-brand">✓</span><span class="font-semibold">{{ $tr ? 'Community’deki her şey' : 'Everything in Community' }}</span></li>
                        <li class="flex gap-2"><span class="mt-0.5 hv-text-brand">✓</span><span>{{ $tr ? 'Gelişmiş güvenlik & yedekleme' : 'Advanced security & backups' }}</span></li>
                        <li class="flex gap-2"><span class="mt-0.5 hv-text-brand">✓</span><span>{{ $tr ? 'Sunucu izleme & PanelZeka AI' : 'Server monitoring & PanelZeka AI' }}</span></li>
                        <li class="flex gap-2"><span class="mt-0.5 hv-text-brand">✓</span><span>{{ $tr ? 'Sunucu başına 500 siteye kadar' : 'Up to 500 sites per server' }}</span></li>
                    </ul>
                    <a href="{{ route('site.pricing') }}" class="hv-btn-primary mt-6 w-full justify-center py-3 text-sm font-semibold">
                        {{ $tr ? 'Planları gör' : 'See plans' }}
                        <span class="text-sm opacity-90">→</span>
                    </a>
                </div>
            </div>
            @endif

            <p class="mt-8 text-center text-sm text-slate-500 dark:text-slate-500">
                {{ landing_p('home.pricing_page_cta') }}
                <a href="{{ route('site.pricing') }}" class="hv-link-quiet">{{ landing_p('home.pricing_page_link') }}</a>.
            </p>
        </div>
    </section>

    {{-- ============ KAPANIŞ CTA ============ --}}
    <section id="docs" class="relative mb-20 mt-20 lg:mt-28 @if ($neon) hv-neon-page-section @endif">
        <div class="hv-container">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200/90 bg-[rgb(var(--hv-brand-500)/0.06)] px-6 py-12 text-center sm:px-10 dark:border-slate-800">
                <div class="hv-bg-blob absolute -right-16 -top-16 h-56 w-56 rounded-full blur-3xl opacity-50" aria-hidden="true"></div>
                <div class="relative mx-auto max-w-2xl space-y-4">
                    <h2 class="hv-section-title">{{ landing_p('home.docs_title') }}</h2>
                    <p class="hv-section-lead">{{ landing_p('home.docs_lead') }}</p>
                    <div class="flex flex-col items-center justify-center gap-3 pt-2 sm:flex-row">
                        <a href="{{ route('site.setup') }}" class="hv-btn-primary gap-2 px-6 py-3.5 text-base">
                            {{ $tr ? 'Kurulum komutları' : 'Install commands' }}
                            <span class="text-sm opacity-90">→</span>
                        </a>
                        <a href="{{ route('docs.index') }}" class="hv-btn-secondary gap-2 px-6 py-3.5 text-base">
                            {{ $tr ? 'Dokümantasyon' : 'Documentation' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.landing>
