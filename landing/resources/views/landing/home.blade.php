@php
    use App\Services\InstallGuide;

    $tr = app()->getLocale() === 'tr';
    $neon = \App\Services\LandingAppearance::isNeonTheme();
    $pricing = $pricing ?? null;
    $oneLiner = InstallGuide::oneLiner();
    $brand = landing_p('brand.name');

    $termLines = $tr ? [
        ['out', '→ Sistem hazırlanıyor (Debian/Ubuntu)…'],
        ['ok', 'Nginx · PHP 8.3 · MariaDB kuruldu'],
        ['ok', 'Panelze Engine (Go) çalışıyor'],
        ['ok', 'SSL + firewall yapılandırıldı'],
        ['link', 'Panel hazır → https://sunucunuz/panel'],
    ] : [
        ['out', '→ Preparing system (Debian/Ubuntu)…'],
        ['ok', 'Installed Nginx · PHP 8.3 · MariaDB'],
        ['ok', 'Panelze Engine (Go) is running'],
        ['ok', 'SSL + firewall configured'],
        ['link', 'Panel ready → https://your-server/panel'],
    ];
@endphp
<x-layouts.landing>
    @if ($neon)
        @include('landing.partials.neon-hero')
        @include('landing.partials.neon-stack')
        @include('landing.partials.neon-grid')
    @endif

    @unless ($neon)
    {{-- ============================================================ --}}
    {{-- HERO — asimetrik, editorial + canlı terminal                --}}
    {{-- ============================================================ --}}
    <section class="relative overflow-hidden">
        {{-- ince ızgara dokusu --}}
        <div class="pointer-events-none absolute inset-0 -z-10 opacity-[0.035] dark:opacity-[0.06]"
             style="background-image:linear-gradient(rgb(var(--hv-brand-600)/1) 1px,transparent 1px),linear-gradient(90deg,rgb(var(--hv-brand-600)/1) 1px,transparent 1px);background-size:42px 42px"></div>

        <div class="hv-container pt-12 sm:pt-16 lg:pt-20">
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-10">
                {{-- sol: metin --}}
                <div class="lg:col-span-7">
                    <p class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[rgb(var(--hv-brand-600)/1)] dark:text-[rgb(var(--hv-brand-400)/1)]">
                        // {{ $tr ? 'kendi sunucunda barındırılan hosting paneli' : 'self-hosted hosting control panel' }}
                    </p>

                    <h1 class="mt-5 text-4xl font-bold leading-[1.05] tracking-tight text-slate-900 sm:text-5xl lg:text-6xl dark:text-white">
                        {{ $tr ? 'Sunucun.' : 'Your server.' }}<br>
                        {{ $tr ? 'Tek komut.' : 'One command.' }}
                        <span class="relative inline-block">
                            <span class="hv-text-brand">{{ $tr ? 'Tam panel.' : 'Full panel.' }}</span>
                            <svg class="absolute -bottom-2 left-0 w-full" height="10" viewBox="0 0 200 10" preserveAspectRatio="none" aria-hidden="true">
                                <path d="M2 7 C 50 2, 150 2, 198 6" fill="none" stroke="rgb(var(--hv-brand-500)/1)" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </h1>

                    <p class="mt-7 max-w-xl text-lg leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ $tr
                            ? $brand.', boş bir Linux sunucusunu; site, veritabanı, e-posta, SSL ve DNS yöneten tam bir hosting paneline çevirir. Aboneliğe değil, kendi sunucuna bağlısın.'
                            : $brand.' turns a blank Linux box into a complete hosting panel — sites, databases, email, SSL and DNS. You depend on your own server, not a subscription.' }}
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ route('site.setup') }}" class="inline-flex items-center gap-2 rounded-xl bg-[rgb(var(--hv-brand-600)/1)] px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-[rgb(var(--hv-brand-600)/0.25)] transition hover:translate-y-px hover:bg-[rgb(var(--hv-brand-700)/1)]">
                            {{ $tr ? 'Kuruluma başla' : 'Start install' }}
                            <span aria-hidden="true">→</span>
                        </a>
                        <a href="{{ route('site.pricing') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-6 py-3.5 text-base font-semibold text-slate-800 transition hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-900">
                            {{ $tr ? 'Fiyatlar' : 'Pricing' }}
                        </a>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-2 font-mono text-xs text-slate-500 dark:text-slate-500">
                        @foreach ([
                            $tr ? 'community kalıcı ücretsiz' : 'community free forever',
                            $tr ? 'kendi sunucunda' : 'self-hosted',
                            $tr ? 'kart gerekmez' : 'no card required',
                        ] as $i => $trust)
                            @if ($i > 0)<span class="text-slate-300 dark:text-slate-700">/</span>@endif
                            <span>{{ $trust }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- sağ: terminal penceresi --}}
                <div class="lg:col-span-5">
                    <div x-data="{ copied: false }" class="relative">
                        <div class="absolute -inset-3 -z-10 rounded-3xl bg-[rgb(var(--hv-brand-500)/0.10)] blur-2xl" aria-hidden="true"></div>
                        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-[#0b1020] shadow-2xl ring-1 ring-black/5">
                            <div class="flex items-center gap-2 border-b border-white/10 px-4 py-3">
                                <span class="h-3 w-3 rounded-full bg-rose-400/90"></span>
                                <span class="h-3 w-3 rounded-full bg-amber-400/90"></span>
                                <span class="h-3 w-3 rounded-full bg-emerald-400/90"></span>
                                <span class="ml-2 font-mono text-xs text-slate-400">root@server: ~</span>
                                <button type="button"
                                        @click="navigator.clipboard.writeText(@js($oneLiner)); copied = true; setTimeout(() => copied = false, 1500)"
                                        class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-slate-200 transition hover:bg-white/15">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>
                                    <span x-show="!copied">{{ $tr ? 'Kopyala' : 'Copy' }}</span>
                                    <span x-show="copied" x-cloak>{{ $tr ? 'Kopyalandı' : 'Copied' }}</span>
                                </button>
                            </div>
                            <div class="space-y-1.5 px-4 py-4 font-mono text-[13px] leading-relaxed">
                                <div class="flex gap-2">
                                    <span class="select-none text-emerald-400">$</span>
                                    <span class="text-slate-100">{{ $oneLiner }}</span>
                                </div>
                                @foreach ($termLines as $line)
                                    @if ($line[0] === 'ok')
                                        <div class="flex gap-2 text-slate-300"><span class="text-emerald-400">✓</span><span>{{ $line[1] }}</span></div>
                                    @elseif ($line[0] === 'link')
                                        <div class="pl-5 font-semibold text-[rgb(var(--hv-brand-400)/1)]">{{ $line[1] }}</div>
                                    @else
                                        <div class="pl-5 text-slate-500">{{ $line[1] }}</div>
                                    @endif
                                @endforeach
                                <div class="flex gap-2 pt-1"><span class="text-emerald-400">$</span><span class="inline-block h-4 w-2 animate-pulse bg-slate-300/80"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- istatistik şeridi — mono ledger --}}
            <div class="mt-16 flex flex-wrap items-stretch gap-y-4 border-y border-slate-200 py-5 dark:border-slate-800">
                @foreach ([
                    ['1', $tr ? 'komutla kurulum' : 'command to install'],
                    ['500', $tr ? 'site / sunucu' : 'sites / server'],
                    ['7+', $tr ? 'Pro modül' : 'Pro modules'],
                    ['Go', $tr ? 'tabanlı motor' : 'based engine'],
                    ['7.4–8.4', 'PHP · Nginx/Apache/OLS'],
                ] as $i => $stat)
                    <div class="flex min-w-[8rem] flex-1 items-baseline gap-2 px-2 @if($i>0) border-l border-slate-200 dark:border-slate-800 @endif">
                        <span class="font-mono text-2xl font-bold text-slate-900 dark:text-white">{{ $stat[0] }}</span>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $stat[1] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- 01 · ÖZELLİKLER — bento ızgara                              --}}
    {{-- ============================================================ --}}
    <section id="features" class="relative mt-24 lg:mt-32">
        <div class="hv-container">
            <div class="flex flex-col gap-3 border-l-2 border-[rgb(var(--hv-brand-500)/1)] pl-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">01 — {{ landing_p('home.features_badge') }}</p>
                    <h2 class="mt-2 max-w-2xl text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl dark:text-white">{{ landing_p('home.features_title') }}</h2>
                </div>
                <p class="max-w-sm text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ landing_p('home.features_lead') }}</p>
            </div>

            <div class="mt-10 grid auto-rows-[1fr] grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (($landingFeatureCards ?? []) as $idx => $card)
                    <article @class([
                        'group relative flex flex-col overflow-hidden rounded-2xl border bg-white/80 p-6 transition hover:-translate-y-1 dark:bg-slate-900/60',
                        'border-[rgb(var(--hv-brand-500)/0.5)] sm:col-span-2 lg:row-span-2 lg:p-8 ring-1 ring-[rgb(var(--hv-brand-500)/0.15)]' => $idx === 0,
                        'border-slate-200/90 dark:border-slate-800' => $idx !== 0,
                    ])>
                        <span class="absolute right-5 top-5 font-mono text-xs text-slate-300 dark:text-slate-700">{{ sprintf('%02d', $idx + 1) }}</span>
                        <div @class([
                            'flex items-center justify-center rounded-xl bg-[rgb(var(--hv-brand-500)/0.12)] text-[rgb(var(--hv-brand-600)/1)] dark:text-[rgb(var(--hv-brand-400)/1)]',
                            'h-14 w-14' => $idx === 0,
                            'h-11 w-11' => $idx !== 0,
                        ])>
                            <x-landing.feature-icon :name="$card['icon'] ?? 'layers'" />
                        </div>
                        <h3 @class([
                            'mt-5 font-semibold text-slate-900 dark:text-white',
                            'text-2xl' => $idx === 0,
                            'text-lg' => $idx !== 0,
                        ])>{{ $card['title'] }}</h3>
                        <p @class([
                            'mt-2 leading-relaxed text-slate-600 dark:text-slate-400',
                            'text-base max-w-md' => $idx === 0,
                            'text-sm' => $idx !== 0,
                        ])>{{ $card['body'] }}</p>
                        @if ($idx === 0)
                            <div class="mt-auto pt-6">
                                <a href="{{ route('docs.index') }}" class="hv-link text-sm font-semibold">{{ $tr ? 'Tüm yetenekler →' : 'All capabilities →' }}</a>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- 02 · NASIL ÇALIŞIR — konsol günlüğü                         --}}
    {{-- ============================================================ --}}
    <section class="relative mt-24 lg:mt-32">
        <div class="hv-container">
            <div class="border-l-2 border-[rgb(var(--hv-brand-500)/1)] pl-5">
                <p class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">02 — {{ $tr ? 'nasıl çalışır' : 'how it works' }}</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl dark:text-white">{{ $tr ? 'Sıfırdan yayına üç adım' : 'From zero to live in three steps' }}</h2>
            </div>

            <div class="mt-10 grid gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200/70 sm:grid-cols-3 dark:border-slate-800 dark:bg-slate-800/60">
                @php
                    $how = $tr ? [
                        ['Sunucu aç', 'Boş bir Debian/Ubuntu VPS; root veya sudo erişimi yeterli.'],
                        ['Komutu çalıştır', 'Tek satır kurulum web sunucusu, PHP, veritabanı ve paneli kurar.'],
                        ['Panele gir', 'Otomatik üretilen yönetici bilgisiyle gir, ilk siteni oluştur.'],
                    ] : [
                        ['Open a server', 'A fresh Debian/Ubuntu VPS; root or sudo access is enough.'],
                        ['Run the command', 'The one-liner installs web server, PHP, database and the panel.'],
                        ['Sign in', 'Log in with the auto-generated admin and create your first site.'],
                    ];
                @endphp
                @foreach ($how as $i => $h)
                    <div class="bg-white p-7 dark:bg-slate-950/70">
                        <div class="font-mono text-sm font-bold text-[rgb(var(--hv-brand-600)/1)] dark:text-[rgb(var(--hv-brand-400)/1)]">0{{ $i + 1 }}</div>
                        <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">{{ $h[0] }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $h[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- 03 · PRO MODÜLLER — spec-sheet liste                        --}}
    {{-- ============================================================ --}}
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
    <section class="relative mt-24 lg:mt-32">
        <div class="hv-container">
            <div class="border-l-2 border-[rgb(var(--hv-brand-500)/1)] pl-5">
                <p class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">03 — Panelze Pro</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl dark:text-white">{{ $tr ? 'Lisansla açılan modüller' : 'Modules unlocked by your license' }}</h2>
            </div>

            <div class="mt-10 grid gap-x-10 gap-y-2 sm:grid-cols-2">
                @foreach ($homeMods as $m)
                    <div class="group flex items-start gap-4 border-b border-dashed border-slate-200 py-5 dark:border-slate-800">
                        <div class="mt-0.5 flex h-10 w-10 flex-none items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition group-hover:bg-[rgb(var(--hv-brand-500)/0.14)] group-hover:text-[rgb(var(--hv-brand-600)/1)] dark:bg-slate-800 dark:text-slate-300 dark:group-hover:text-[rgb(var(--hv-brand-400)/1)]">
                            <x-landing.feature-icon :name="$modIcon[$m['key']] ?? 'layers'" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $m['label'] }}</h3>
                                <code class="hidden rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-500 sm:inline dark:bg-slate-800 dark:text-slate-400">{{ $m['key'] }}</code>
                            </div>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $m['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
    @endunless

    {{-- ============================================================ --}}
    {{-- 04 · FİYATLANDIRMA — fiş/etiket stili                       --}}
    {{-- ============================================================ --}}
    <section id="pricing" class="relative mt-24 lg:mt-32 @if ($neon) hv-neon-page-section @endif">
        <div class="hv-container">
            <div class="border-l-2 border-[rgb(var(--hv-brand-500)/1)] pl-5">
                <p class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">04 — {{ landing_p('home.pricing_badge') }}</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl dark:text-white">{{ landing_p('home.pricing_title') }}</h2>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ landing_p('home.pricing_lead') }}</p>
            </div>

            @if ($pricing)
            @php $proY = $pricing['pro']['monthly'] ?? ($pricing['pro']['yearly'] ?? ($pricing['pro']['lifetime'] ?? null)); @endphp
            <div class="mt-10 grid max-w-4xl gap-5 lg:grid-cols-2">
                {{-- Free --}}
                <div class="flex flex-col rounded-2xl border border-slate-200 bg-white/80 p-8 dark:border-slate-800 dark:bg-slate-900/60">
                    <div class="flex items-baseline justify-between">
                        <h3 class="font-mono text-sm font-bold uppercase tracking-wider text-slate-500">{{ $pricing['free']['name'] }}</h3>
                        <span class="rounded-full border border-emerald-500/30 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ $tr ? 'Ücretsiz' : 'Free' }}</span>
                    </div>
                    <div class="mt-5 flex items-baseline gap-1.5">
                        <span class="text-5xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $pricing['free']['price_label'] }}</span>
                        <span class="text-sm text-slate-500">{{ $pricing['free']['period_label'] }}</span>
                    </div>
                    <div class="my-6 border-t border-dashed border-slate-200 dark:border-slate-800"></div>
                    <ul class="flex-1 space-y-3 text-sm text-slate-700 dark:text-slate-300">
                        @foreach (array_slice($pricing['free']['features'], 0, 4) as $line)
                            <li class="flex gap-2.5"><span class="mt-0.5 text-emerald-500">✓</span><span>{{ $line }}</span></li>
                        @endforeach
                    </ul>
                    <a href="{{ route('site.setup') }}" class="mt-7 inline-flex w-full items-center justify-center rounded-xl border border-slate-300 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-900">
                        {{ $tr ? 'Ücretsiz kur' : 'Install free' }}
                    </a>
                </div>

                {{-- Pro --}}
                <div class="relative flex flex-col overflow-hidden rounded-2xl border-2 border-[rgb(var(--hv-brand-500)/0.65)] bg-white p-8 shadow-xl shadow-[rgb(var(--hv-brand-500)/0.10)] dark:bg-slate-900/80">
                    <div class="absolute right-0 top-0 rounded-bl-xl bg-[rgb(var(--hv-brand-600)/1)] px-3 py-1 text-xs font-semibold text-white">{{ $tr ? 'En popüler' : 'Most popular' }}</div>
                    <h3 class="font-mono text-sm font-bold uppercase tracking-wider text-[rgb(var(--hv-brand-600)/1)] dark:text-[rgb(var(--hv-brand-400)/1)]">Panelze Pro</h3>
                    @if ($proY)
                        <div class="mt-5 flex items-baseline gap-1.5">
                            <span class="text-5xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $proY['price_label'] }}</span>
                            <span class="text-sm text-slate-500 dark:text-slate-400">{{ $proY['period_label'] }}</span>
                        </div>
                        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $tr ? 'aylık · yıllık · ömür boyu' : 'monthly · yearly · lifetime' }}</p>
                    @endif
                    <div class="my-6 border-t border-dashed border-slate-300 dark:border-slate-700"></div>
                    <ul class="flex-1 space-y-3 text-sm text-slate-800 dark:text-slate-200">
                        <li class="flex gap-2.5"><span class="mt-0.5 hv-text-brand">✓</span><span class="font-semibold">{{ $tr ? 'Community’deki her şey' : 'Everything in Community' }}</span></li>
                        <li class="flex gap-2.5"><span class="mt-0.5 hv-text-brand">✓</span><span>{{ $tr ? 'Gelişmiş güvenlik & yedekleme' : 'Advanced security & backups' }}</span></li>
                        <li class="flex gap-2.5"><span class="mt-0.5 hv-text-brand">✓</span><span>{{ $tr ? 'Sunucu izleme & PanelZeka AI' : 'Server monitoring & PanelZeka AI' }}</span></li>
                        <li class="flex gap-2.5"><span class="mt-0.5 hv-text-brand">✓</span><span>{{ $tr ? 'Sunucu başına 500 siteye kadar' : 'Up to 500 sites per server' }}</span></li>
                    </ul>
                    <a href="{{ route('site.pricing') }}" class="mt-7 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[rgb(var(--hv-brand-600)/1)] py-3 text-sm font-semibold text-white transition hover:bg-[rgb(var(--hv-brand-700)/1)]">
                        {{ $tr ? 'Planları gör' : 'See plans' }}<span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
            @endif

            <p class="mt-7 font-mono text-xs text-slate-500 dark:text-slate-500">
                {{ landing_p('home.pricing_page_cta') }}
                <a href="{{ route('site.pricing') }}" class="hv-link-quiet">{{ landing_p('home.pricing_page_link') }}</a>.
            </p>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- KAPANIŞ — koyu terminal bandı                               --}}
    {{-- ============================================================ --}}
    <section id="docs" class="relative mb-24 mt-24 lg:mt-32">
        <div class="hv-container">
            <div class="relative overflow-hidden rounded-3xl bg-[#0b1020] px-6 py-14 sm:px-12">
                <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
                     style="background-image:linear-gradient(rgb(var(--hv-brand-400)/1) 1px,transparent 1px),linear-gradient(90deg,rgb(var(--hv-brand-400)/1) 1px,transparent 1px);background-size:38px 38px"></div>
                <div class="relative mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ landing_p('home.docs_title') }}</h2>
                    <p class="mt-4 text-base leading-relaxed text-slate-300">{{ landing_p('home.docs_lead') }}</p>

                    <div x-data="{ copied: false }" class="mx-auto mt-8 flex max-w-lg items-center gap-2 rounded-xl border border-white/10 bg-black/40 p-2 pl-4 text-left">
                        <span class="select-none font-mono text-sm text-emerald-400">$</span>
                        <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap font-mono text-sm text-slate-100">{{ $oneLiner }}</code>
                        <button type="button"
                                @click="navigator.clipboard.writeText(@js($oneLiner)); copied = true; setTimeout(() => copied = false, 1500)"
                                class="inline-flex flex-none items-center gap-1.5 rounded-lg bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20">
                            <span x-show="!copied">{{ $tr ? 'Kopyala' : 'Copy' }}</span>
                            <span x-show="copied" x-cloak>{{ $tr ? 'Kopyalandı' : 'Copied' }}</span>
                        </button>
                    </div>

                    <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('site.setup') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3.5 text-base font-semibold text-slate-900 transition hover:bg-slate-100">
                            {{ $tr ? 'Kurulum komutları' : 'Install commands' }}<span aria-hidden="true">→</span>
                        </a>
                        <a href="{{ route('docs.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-6 py-3.5 text-base font-semibold text-white transition hover:bg-white/10">
                            {{ $tr ? 'Dokümantasyon' : 'Documentation' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.landing>
