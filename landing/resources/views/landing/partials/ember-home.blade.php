{{-- Ember · Modern ana sayfa. Değişkenler landing/home.blade.php içinden gelir: $tr, $pricing, $oneLiner, $brand --}}
@php
    use App\Support\PanelFeatureCatalog;

    $proModules = ($pricing['pro_modules'] ?? null) ?: PanelFeatureCatalog::proModuleDefs();
    $proModuleCount = count(PanelFeatureCatalog::proModuleDefs());
    $proBullets = PanelFeatureCatalog::proModuleMarketingBullets($tr ? 'tr' : 'en');
    $modIcon = ['phpmyadmin_sso' => 'database', 'security_pro' => 'shield', 'backups_pro' => 'layers', 'monitoring_advanced' => 'cpu', 'ai_advisor' => 'rocket', 'curious_tools' => 'terminal', 'stripe_billing' => 'layers', 'vendor_panel' => 'users'];

    $freePlan = $pricing['free'] ?? null;
    $freeFeatures = $freePlan['features'] ?? PanelFeatureCatalog::communityPlanFeatures($tr ? 'tr' : 'en');
    $freeMaxSites = (int) ($freePlan['max_sites'] ?? 5);
    $proPeriods = array_filter([
        'monthly' => $pricing['pro']['monthly'] ?? null,
        'yearly' => $pricing['pro']['yearly'] ?? null,
        'lifetime' => $pricing['pro']['lifetime'] ?? null,
    ]);
    $proDefaultPeriod = isset($proPeriods['yearly']) ? 'yearly' : (array_key_first($proPeriods) ?? 'monthly');
    $proMaxSites = (int) (collect($proPeriods)->first()['max_sites'] ?? 500);
    $periodLabels = $tr
        ? ['monthly' => 'Aylık', 'yearly' => 'Yıllık', 'lifetime' => 'Ömür boyu']
        : ['monthly' => 'Monthly', 'yearly' => 'Yearly', 'lifetime' => 'Lifetime'];
    $proFeatures = $pricing['pro_features'] ?? PanelFeatureCatalog::proPlanFeatures($tr ? 'tr' : 'en');

    $stack = ['Nginx', 'Apache', 'OpenLiteSpeed', 'PHP 7.4 – 8.4', 'MySQL / MariaDB', 'PostgreSQL', "Let's Encrypt", 'BIND9 DNS', 'Roundcube', 'Node.js · PM2', 'Git deploy', 'fail2ban', 'ModSecurity', 'ClamAV', 'Go Engine'];

    $mockNav = $tr
        ? ['Kontrol Paneli', 'Alan Adları', 'Veritabanları', 'E-posta', 'Dosya Yöneticisi', 'SSL', 'Yedekler', 'Uygulama Yükleyici']
        : ['Dashboard', 'Domains', 'Databases', 'Email', 'File Manager', 'SSL', 'Backups', 'App Installer'];
    $mockTools = $tr
        ? [['Alan Adları', 'Site ekleyin, PHP sürümünü seçin'], ['SSL', 'Ücretsiz sertifika kurun'], ['Dosya Yöneticisi', 'Yükleyin, düzenleyin, arşivleyin'], ['Veritabanları', 'MySQL veritabanı oluşturun'], ['E-posta', 'Alan adınıza özel hesaplar'], ['Yedekler', 'Yedek alın, geri yükleyin']]
        : [['Domains', 'Add sites, pick a PHP version'], ['SSL', 'Install free certificates'], ['File Manager', 'Upload, edit and archive'], ['Databases', 'Create MySQL databases'], ['Email', 'Accounts on your domain'], ['Backups', 'Back up and restore']];

    $easyItems = $tr
        ? ['Alan Adları', 'Veritabanları', 'E-posta', 'Dosya Yöneticisi', 'SSL', 'Yedekler', 'Uygulama Yükleyici']
        : ['Domains', 'Databases', 'Email', 'File Manager', 'SSL', 'Backups', 'App Installer'];
    $advancedItems = $tr
        ? ['DNS', 'Yönlendirmeler', 'FTP', 'Zamanlanmış Görevler', 'Güvenlik', 'İzleme', 'Node.js uygulamaları', 'Deploy Yöneticisi']
        : ['DNS', 'Redirects', 'FTP', 'Cron jobs', 'Security', 'Monitoring', 'Node.js apps', 'Deploy manager'];

    $roles = $tr ? [
        ['icon' => 'cpu', 'title' => 'Sunucu yöneticisi', 'body' => 'Web sunucusu, PHP, DNS ve posta yığını ayarları, tarayıcı terminali, loglar, kullanıcılar, paketler ve lisans ekranı.'],
        ['icon' => 'users', 'title' => 'Bayi', 'body' => 'Kendi müşterileriniz için paket, kullanıcı ve rol yönetimi; logonuz ve renklerinizle white-label panel, WHMCS modülü.'],
        ['icon' => 'globe', 'title' => 'Müşteri', 'body' => 'Siteler, e-posta, veritabanı ve yedekler; Kolay mod ile teknik bilgi gerektirmeyen, açıklamalı araçlar.'],
    ] : [
        ['icon' => 'cpu', 'title' => 'Server admin', 'body' => 'Web server, PHP, DNS and mail stack settings, browser terminal, logs, users, packages and the license screen.'],
        ['icon' => 'users', 'title' => 'Reseller', 'body' => 'Packages, users and roles for your own customers; a white-label panel with your logo and colours, plus a WHMCS module.'],
        ['icon' => 'globe', 'title' => 'Customer', 'body' => 'Sites, email, databases and backups; Easy mode keeps every tool explained and free of jargon.'],
    ];

    $steps = $tr ? [
        ['Sunucunuzu hazırlayın', 'Boş bir Debian veya Ubuntu VPS; root ya da sudo erişimi yeterli.'],
        ['Tek komutu çalıştırın', 'Web sunucusu, PHP, veritabanı, Go Engine ve panel otomatik kurulur.'],
        ['Panele girin', 'Otomatik oluşturulan yönetici bilgisiyle girin, ilk sitenizi dakikalar içinde açın.'],
    ] : [
        ['Prepare a server', 'A fresh Debian or Ubuntu VPS; root or sudo access is enough.'],
        ['Run one command', 'Web server, PHP, database, the Go Engine and the panel install automatically.'],
        ['Sign in', 'Use the generated admin login and launch your first site within minutes.'],
    ];

    $faqs = $tr ? [
        ['Community sürümü gerçekten ücretsiz mi?', 'Evet. Community lisansı kalıcı olarak ücretsizdir ve sunucu başına '.$freeMaxSites.' siteye kadar kullanılabilir; kart bilgisi istenmez.'],
        ['Hangi işletim sistemlerinde çalışır?', 'Debian ve Ubuntu sunucularda çalışır. Kurulum komutu gerekli tüm bileşenleri otomatik kurar.'],
        ['Web sunucusunu seçebilir miyim?', 'Evet. Nginx, Apache ve OpenLiteSpeed desteklenir; her site için PHP 7.4 ile 8.4 arasında sürüm seçebilirsiniz.'],
        ['cPanel, Plesk veya aaPanel’den taşıyabilir miyim?', 'Evet. Panelze’nin taşıma eklentileri mevcut sunucunuzdaki siteleri aktarmanıza yardımcı olur.'],
        ['Pro’ya nasıl geçerim?', 'Bir Pro lisansı satın alın ve lisans anahtarınızı girin. Kurulumunuzu baştan yapmanız gerekmez; modüller anında açılır.'],
    ] : [
        ['Is Community really free?', 'Yes. The Community license is free forever and covers up to '.$freeMaxSites.' sites per server; no card required.'],
        ['Which operating systems are supported?', 'Debian and Ubuntu servers. The install command sets up every required component automatically.'],
        ['Can I choose the web server?', 'Yes. Nginx, Apache and OpenLiteSpeed are supported, with a per-site PHP version from 7.4 to 8.4.'],
        ['Can I migrate from cPanel, Plesk or aaPanel?', 'Yes. Panelze migration plugins help you move sites over from your existing server.'],
        ['How do I upgrade to Pro?', 'Buy a Pro license and enter your key. No reinstall needed; modules unlock instantly.'],
    ];
@endphp

{{-- ============================== HERO ============================== --}}
<section class="relative overflow-hidden">
    <div class="em-grid-fade pointer-events-none absolute inset-0 -z-10" aria-hidden="true"></div>

    <div class="hv-container pt-14 text-center sm:pt-20 lg:pt-24">
        <a href="{{ route('site.demo') }}" class="em-eyebrow em-rise transition hover:brightness-105">
            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-60"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-orange-500"></span></span>
            {{ $tr ? 'Yeni arayüz: Kolay & Gelişmiş mod — canlı demoyu deneyin' : 'New interface: Easy & Advanced mode — try the live demo' }}
            <span aria-hidden="true">→</span>
        </a>

        <h1 class="em-rise mx-auto mt-6 max-w-4xl text-4xl font-extrabold leading-[1.06] tracking-tight text-slate-900 sm:text-5xl lg:text-[4rem] dark:text-white">
            @if ($tr)
                Kendi sunucunuzda <span class="em-gradient-text">modern</span> bir hosting paneli.
            @else
                A <span class="em-gradient-text">modern</span> hosting panel, on your own server.
            @endif
        </h1>

        <p class="em-rise mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600 dark:text-slate-400">
            {{ $tr
                ? $brand.', boş bir Debian/Ubuntu sunucusunu tek komutla siteleri, e-postayı, veritabanlarını, SSL, DNS ve yedekleri yöneten eksiksiz bir kontrol paneline dönüştürür.'
                : $brand.' turns a blank Debian/Ubuntu server into a complete control panel for sites, email, databases, SSL, DNS and backups — with a single command.' }}
        </p>

        <div class="em-rise mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('site.setup') }}" class="btn-primary !px-7 !py-3.5 !text-base">
                {{ $tr ? 'Ücretsiz kurun' : 'Install for free' }}
                <span aria-hidden="true">→</span>
            </a>
            <a href="{{ route('site.demo') }}" class="inline-flex items-center gap-2 rounded-[0.875rem] border border-slate-200 bg-white/80 px-7 py-3.5 text-base font-semibold text-slate-800 shadow-sm transition hover:border-orange-300 hover:text-orange-700 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-100 dark:hover:border-orange-500/40 dark:hover:text-orange-300">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72a1 1 0 0 0 1.5.86l11-6.86a1 1 0 0 0 0-1.72l-11-6.86A1 1 0 0 0 8 5.14z"/></svg>
                {{ $tr ? 'Canlı demo' : 'Live demo' }}
            </a>
        </div>

        <div x-data="{ copied: false }" class="em-rise mx-auto mt-7 flex max-w-xl items-center gap-2 rounded-2xl border border-slate-200 bg-white/90 p-1.5 pl-4 text-left shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
            <span class="select-none font-mono text-sm font-semibold text-orange-600 dark:text-orange-400">$</span>
            <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap font-mono text-[13px] text-slate-700 dark:text-slate-200">{{ $oneLiner }}</code>
            <button type="button"
                    @click="navigator.clipboard.writeText(@js($oneLiner)); copied = true; setTimeout(() => copied = false, 1500)"
                    class="inline-flex flex-none items-center gap-1.5 rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-700 dark:bg-white/10 dark:hover:bg-white/15">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                <span x-show="!copied">{{ $tr ? 'Kopyala' : 'Copy' }}</span>
                <span x-show="copied" x-cloak>{{ $tr ? 'Kopyalandı' : 'Copied' }}</span>
            </button>
        </div>

        <ul class="em-rise mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-slate-500 dark:text-slate-400">
            @foreach ([
                $tr ? 'Community kalıcı olarak ücretsiz' : 'Community free forever',
                $tr ? 'Kart bilgisi gerekmez' : 'No card required',
                $tr ? 'Verileriniz kendi sunucunuzda' : 'Your data stays on your server',
            ] as $trust)
                <li class="flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    {{ $trust }}
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Panel önizlemesi --}}
    <div class="hv-container em-rise-2 mt-14 sm:mt-16">
        <div class="relative">
            <div class="pointer-events-none absolute -inset-x-10 -top-10 bottom-10 -z-10 rounded-[3rem] bg-[radial-gradient(60%_60%_at_50%_30%,rgb(249_115_22/0.22),transparent_70%)] blur-2xl" aria-hidden="true"></div>
            <div class="em-mock select-none" aria-hidden="true">
                <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-800 dark:bg-slate-900">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                    <div class="mx-auto flex items-center gap-1.5 rounded-lg bg-white px-3 py-1 font-mono text-[11px] text-slate-500 ring-1 ring-slate-200 dark:bg-slate-950 dark:text-slate-400 dark:ring-slate-800">
                        <svg class="h-3 w-3 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        panel.{{ $tr ? 'sunucunuz' : 'your-server' }}.com
                    </div>
                    <span class="w-10"></span>
                </div>

                <div class="grid md:grid-cols-[210px_minmax(0,1fr)]">
                    <div class="em-ink relative hidden flex-col overflow-hidden p-3 md:flex">
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(80%_100%_at_0%_0%,rgb(249_115_22/0.25),transparent_70%)]"></div>
                        <div class="relative flex items-center gap-2 px-1.5 py-1.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-400 to-orange-600 text-white">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 7.5h.01M7 16.5h.01"/></svg>
                            </span>
                            <span class="text-left">
                                <span class="block text-[13px] font-bold leading-tight">{{ $brand }}</span>
                                <span class="block text-[10px] text-slate-400">{{ $tr ? 'Hosting kontrol paneli' : 'Hosting control panel' }}</span>
                            </span>
                        </div>
                        <div class="relative mt-4 space-y-0.5 text-left">
                            @foreach ($mockNav as $i => $item)
                                <div @class([
                                    'relative flex items-center gap-2.5 rounded-lg px-2.5 py-[7px] text-[12px]',
                                    'bg-white/[0.07] font-semibold text-white ring-1 ring-inset ring-white/10' => $i === 0,
                                    'text-slate-400' => $i !== 0,
                                ])>
                                    @if ($i === 0)<span class="absolute -left-3 top-1.5 h-5 w-1 rounded-r bg-orange-500 shadow-[0_0_12px_rgb(249_115_22)]"></span>@endif
                                    <span @class(['h-3.5 w-3.5 rounded', 'bg-orange-400' => $i === 0, 'bg-white/15' => $i !== 0])></span>
                                    {{ $item }}
                                </div>
                            @endforeach
                        </div>
                        <div class="relative mt-auto flex items-center gap-2 rounded-lg px-1.5 pt-6">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-orange-600 text-[10px] font-bold">AY</span>
                            <span class="text-left">
                                <span class="block text-[11px] font-semibold">Ayşe Yılmaz</span>
                                <span class="block text-[10px] text-slate-400">{{ $tr ? 'Kolay Mod' : 'Easy mode' }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="em-mock-main p-4 text-left sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[10px] text-slate-400">{{ $brand }} › {{ $mockNav[0] }}</div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $mockNav[0] }}</div>
                            </div>
                            <div class="hidden items-center gap-0.5 rounded-lg bg-slate-100 p-0.5 text-[10px] font-semibold sm:flex dark:bg-slate-800">
                                <span class="rounded-md bg-white px-2 py-1 text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white">{{ $tr ? 'Kolay Mod' : 'Easy' }}</span>
                                <span class="px-2 py-1 text-slate-500">{{ $tr ? 'Gelişmiş Mod' : 'Advanced' }}</span>
                            </div>
                        </div>

                        <div class="em-ink relative mt-4 overflow-hidden rounded-2xl p-5">
                            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(90%_120%_at_100%_0%,rgb(249_115_22/0.45),transparent_55%)]"></div>
                            <div class="relative flex flex-wrap items-end justify-between gap-4">
                                <div>
                                    <div class="text-lg font-bold sm:text-xl">{{ $tr ? 'İyi günler, Ayşe' : 'Good afternoon, Ayşe' }}</div>
                                    <div class="mt-1 text-[11px] text-slate-300">{{ $tr ? 'Sık kullanılan işlemler aşağıda sizi bekliyor.' : 'Your most used actions are right below.' }}</div>
                                    <div class="mt-3 flex gap-2">
                                        <span class="rounded-lg bg-gradient-to-r from-orange-400 to-orange-600 px-3 py-1.5 text-[11px] font-semibold">+ {{ $tr ? 'Site Oluştur' : 'Create site' }}</span>
                                        <span class="rounded-lg bg-white/10 px-3 py-1.5 text-[11px] font-semibold ring-1 ring-white/15">{{ $mockNav[4] }}</span>
                                    </div>
                                </div>
                                <div class="hidden rounded-xl bg-white/[0.07] p-3 ring-1 ring-white/10 sm:block">
                                    <div class="text-[9px] font-semibold uppercase tracking-wider text-slate-400">{{ $tr ? 'Paketiniz' : 'Your plan' }}</div>
                                    <div class="text-sm font-bold">Pro</div>
                                    <div class="mt-2 h-1 w-28 rounded-full bg-white/10"><div class="h-1 w-2/5 rounded-full bg-gradient-to-r from-amber-300 to-orange-500"></div></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                            @foreach ([[$tr ? 'Alan adı' : 'Domains', '12', 'bg-sky-400'], [$tr ? 'Veritabanı' : 'Databases', '8', 'bg-emerald-400'], [$tr ? 'E-posta' : 'Email', '24', 'bg-violet-400'], [$tr ? 'Disk' : 'Disk', '38%', 'bg-orange-400']] as $st)
                                <div class="em-mock-tile p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="h-5 w-5 rounded-md {{ $st[2] }} opacity-80"></span>
                                        <span class="text-base font-bold text-slate-900 dark:text-white">{{ $st[1] }}</span>
                                    </div>
                                    <div class="mt-1.5 text-[11px] text-slate-500">{{ $st[0] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="em-mock-tile mt-4 hidden p-3 sm:block">
                            <div class="text-[12px] font-semibold text-slate-900 dark:text-white">{{ $tr ? 'Araçlar' : 'Tools' }}</div>
                            <div class="mt-2 grid grid-cols-2 gap-2 lg:grid-cols-3">
                                @foreach ($mockTools as $tool)
                                    <div class="flex items-start gap-2 rounded-lg bg-slate-50 p-2 dark:bg-white/[0.03]">
                                        <span class="mt-0.5 h-6 w-6 flex-none rounded-md bg-orange-100 ring-1 ring-orange-200 dark:bg-orange-500/15 dark:ring-orange-500/25"></span>
                                        <span>
                                            <span class="block text-[11px] font-semibold text-slate-800 dark:text-slate-100">{{ $tool[0] }}</span>
                                            <span class="block text-[10px] text-slate-500">{{ $tool[1] }}</span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================== YIĞIN + SAYILAR ============================== --}}
<section class="relative mt-20">
    <div class="hv-container">
        <p class="text-center text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
            {{ $tr ? 'Güvendiğiniz açık kaynak yığın üzerinde çalışır' : 'Built on the open-source stack you already trust' }}
        </p>
        <div class="mt-5 flex flex-wrap justify-center gap-2">
            @foreach ($stack as $tech)
                <span class="rounded-full border border-slate-200 bg-white/70 px-3.5 py-1.5 text-sm font-medium text-slate-600 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-300">{{ $tech }}</span>
            @endforeach
        </div>

        <div class="em-card mt-12 grid grid-cols-2 divide-slate-200 overflow-hidden sm:grid-cols-3 lg:grid-cols-5 lg:divide-x dark:divide-slate-800">
            @foreach ([
                ['1', $tr ? 'komutla kurulum' : 'command to install'],
                [(string) $freeMaxSites, $tr ? 'site Community’de ücretsiz' : 'sites free on Community'],
                [(string) $proMaxSites, $tr ? 'site / sunucu (Pro)' : 'sites per server (Pro)'],
                [(string) $proModuleCount, $tr ? 'Pro modülü' : 'Pro modules'],
                ['5', $tr ? 'panel dili' : 'panel languages'],
            ] as $stat)
                <div class="px-5 py-6 text-center">
                    <div class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $stat[0] }}</div>
                    <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $stat[1] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== ÖZELLİKLER ============================== --}}
<section id="features" class="relative mt-24 scroll-mt-24 lg:mt-32">
    <div class="hv-container">
        <div class="mx-auto max-w-2xl text-center">
            <span class="em-eyebrow">{{ landing_p('home.features_badge') }}</span>
            <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">{{ landing_p('home.features_title') }}</h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-400">{{ landing_p('home.features_lead') }}</p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (($landingFeatureCards ?? []) as $card)
                <article class="em-card em-card-hover p-6">
                    <span class="em-icon"><x-landing.feature-icon :name="$card['icon'] ?? 'layers'" class="h-5 w-5" /></span>
                    <h3 class="mt-5 text-lg font-bold text-slate-900 dark:text-white">{{ $card['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $card['body'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('docs.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-orange-700 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300">
                {{ $tr ? 'Tüm yetenekleri dokümantasyonda inceleyin' : 'Explore every capability in the docs' }} <span aria-hidden="true">→</span>
            </a>
        </div>
    </div>
</section>

{{-- ============================== KOLAY / GELİŞMİŞ MOD ============================== --}}
<section class="relative mt-24 lg:mt-32">
    <div class="hv-container">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
                <span class="em-eyebrow">{{ $tr ? 'Herkes için tek panel' : 'One panel for everyone' }}</span>
                <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                    {{ $tr ? 'Yeni başlayana sade, uzmana eksiksiz.' : 'Simple for beginners, complete for experts.' }}
                </h2>
                <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ $tr
                        ? 'Kolay mod yalnızca temel araçları gösterir ve her birinin ne işe yaradığını açıklar. Gelişmiş moda tek tıkla geçtiğinizde DNS, FTP, cron, güvenlik, Node.js ve deploy araçlarının tamamı açılır.'
                        : 'Easy mode shows only the essential tools and explains what each one does. Switch to Advanced mode with one click to unlock DNS, FTP, cron, security, Node.js and deploy tooling.' }}
                </p>
                <ul class="mt-6 space-y-3 text-sm text-slate-700 dark:text-slate-300">
                    @foreach ($tr ? [
                        'Açık ve koyu tema, tüm cihazlarda uyumlu arayüz',
                        'Türkçe, English, Deutsch, Français ve Español panel dili',
                        'İki adımlı doğrulama (2FA) ve rol tabanlı yetkiler',
                    ] : [
                        'Light and dark themes, responsive on every device',
                        'Panel in Turkish, English, German, French and Spanish',
                        'Two-factor authentication (2FA) and role-based permissions',
                    ] as $line)
                        <li class="flex gap-3">
                            <span class="mt-0.5 flex h-5 w-5 flex-none items-center justify-center rounded-full bg-orange-500/15 text-orange-600 dark:text-orange-400">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            {{ $line }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <div x-data="{ mode: 'easy' }" class="em-card p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $tr ? 'Menü' : 'Menu' }}</span>
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 text-xs font-semibold dark:bg-slate-800" role="tablist">
                        <button type="button" @click="mode = 'easy'" :class="mode === 'easy' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500'" class="rounded-lg px-3 py-1.5 transition">{{ $tr ? 'Kolay Mod' : 'Easy mode' }}</button>
                        <button type="button" @click="mode = 'advanced'" :class="mode === 'advanced' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500'" class="rounded-lg px-3 py-1.5 transition">{{ $tr ? 'Gelişmiş Mod' : 'Advanced' }}</button>
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-2">
                    @foreach ($easyItems as $item)
                        <div class="flex items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-700 dark:border-slate-800 dark:text-slate-200">
                            <span class="h-2 w-2 rounded-full bg-orange-500"></span>{{ $item }}
                        </div>
                    @endforeach
                    @foreach ($advancedItems as $item)
                        <div x-show="mode === 'advanced'" x-cloak
                             x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0 scale-95"
                             class="flex items-center gap-2.5 rounded-xl border border-dashed border-orange-300 bg-orange-50/60 px-3 py-2.5 text-sm font-medium text-slate-700 dark:border-orange-500/30 dark:bg-orange-500/[0.06] dark:text-slate-200">
                            <span class="h-2 w-2 rounded-full bg-slate-400"></span>{{ $item }}
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                    <span x-show="mode === 'easy'">{{ $tr ? 'Kolay mod: temel araçlar, her biri açıklamalı.' : 'Easy mode: essential tools, each one explained.' }}</span>
                    <span x-show="mode === 'advanced'" x-cloak>{{ $tr ? 'Gelişmiş mod: tüm teknik araçlar görünür.' : 'Advanced mode: every technical tool is visible.' }}</span>
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ============================== ROLLER + TAŞIMA ============================== --}}
<section class="relative mt-24 lg:mt-32">
    <div class="hv-container">
        <div class="mx-auto max-w-2xl text-center">
            <span class="em-eyebrow">{{ $tr ? 'Rol tabanlı yapı' : 'Role-based by design' }}</span>
            <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                {{ $tr ? 'Yöneticiden müşteriye, herkes doğru ekranda.' : 'From admin to customer, everyone sees the right screen.' }}
            </h2>
        </div>
        <div class="mt-12 grid gap-5 md:grid-cols-3">
            @foreach ($roles as $role)
                <div class="em-card em-card-hover p-6">
                    <span class="em-icon"><x-landing.feature-icon :name="$role['icon']" class="h-5 w-5" /></span>
                    <h3 class="mt-5 text-lg font-bold text-slate-900 dark:text-white">{{ $role['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $role['body'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="em-card mt-5 flex flex-col items-start justify-between gap-5 p-6 sm:flex-row sm:items-center">
            <div class="flex items-start gap-4">
                <span class="em-icon">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3l4 4-4 4"/><path d="M3 7h18"/><path d="M7 21l-4-4 4-4"/><path d="M21 17H3"/></svg>
                </span>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $tr ? 'Başka panelden mi geliyorsunuz?' : 'Coming from another panel?' }}</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $tr ? 'cPanel, Plesk ve aaPanel taşıma eklentileriyle sitelerinizi Panelze’ye aktarın.' : 'Move your sites over with the cPanel, Plesk and aaPanel migration plugins.' }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (['cPanel', 'Plesk', 'aaPanel'] as $p)
                    <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $p }}</span>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============================== NASIL ÇALIŞIR ============================== --}}
<section class="relative mt-24 lg:mt-32">
    <div class="hv-container">
        <div class="mx-auto max-w-2xl text-center">
            <span class="em-eyebrow">{{ $tr ? 'Kurulum' : 'Setup' }}</span>
            <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">{{ $tr ? 'Sıfırdan yayına üç adım' : 'From zero to live in three steps' }}</h2>
        </div>
        <div class="relative mt-12">
            <div class="pointer-events-none absolute left-[16%] right-[16%] top-7 hidden h-px bg-gradient-to-r from-transparent via-orange-300 to-transparent md:block dark:via-orange-500/40" aria-hidden="true"></div>
        <ol class="grid gap-5 md:grid-cols-3">
            @foreach ($steps as $i => $step)
                <li class="relative text-center">
                    <span class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-orange-600 text-lg font-extrabold text-white shadow-[0_12px_28px_-10px_rgb(234_88_12/0.8)]">{{ $i + 1 }}</span>
                    <h3 class="mt-5 text-lg font-bold text-slate-900 dark:text-white">{{ $step[0] }}</h3>
                    <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $step[1] }}</p>
                </li>
            @endforeach
        </ol>
        </div>
    </div>
</section>

{{-- ============================== PRO MODÜLLER ============================== --}}
<section class="relative mt-24 lg:mt-32">
    <div class="hv-container">
        <div class="em-ink relative overflow-hidden rounded-[2rem] px-6 py-14 sm:px-12">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_100%_0%,rgb(249_115_22/0.35),transparent_60%),radial-gradient(50%_60%_at_0%_100%,rgb(251_191_36/0.12),transparent_60%)]" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-xl">
                    <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-amber-200 ring-1 ring-inset ring-white/15">Panelze Pro</span>
                    <h2 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $tr ? 'Lisansla açılan güçlü modüller' : 'Powerful modules, unlocked by your license' }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-300">{{ $tr ? 'Kurulumunuzu değiştirmeden lisans anahtarınızı girin; modüller aynı panelde anında açılır.' : 'Enter your license key without touching your setup; modules unlock instantly in the same panel.' }}</p>
                </div>
                <a href="{{ route('site.pricing') }}" class="btn-primary self-start sm:self-auto">{{ $tr ? 'Pro planları' : 'Pro plans' }} <span aria-hidden="true">→</span></a>
            </div>
            <div class="relative mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($proModules as $m)
                    @php
                        [$mLabel, $mDesc] = array_pad(explode(' — ', $proBullets[$m['key']] ?? (($m['label'] ?? $m['key']).' — '.($m['description'] ?? '')), 2), 2, '');
                    @endphp
                    <div class="rounded-2xl bg-white/[0.05] p-5 ring-1 ring-inset ring-white/10 transition hover:bg-white/[0.08]">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-amber-200 ring-1 ring-inset ring-white/15">
                            <x-landing.feature-icon :name="$modIcon[$m['key']] ?? 'layers'" class="h-5 w-5" />
                        </span>
                        <h3 class="mt-4 text-sm font-bold">{{ $mLabel }}</h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-slate-400">{{ $mDesc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============================== FİYATLANDIRMA ============================== --}}
<section id="pricing" class="relative mt-24 scroll-mt-24 lg:mt-32">
    <div class="hv-container">
        <div class="mx-auto max-w-2xl text-center">
            <span class="em-eyebrow">{{ landing_p('home.pricing_badge') }}</span>
            <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">{{ landing_p('home.pricing_title') }}</h2>
            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-400">{{ landing_p('home.pricing_lead') }}</p>
        </div>

        <div x-data="{ period: @js($proDefaultPeriod) }" class="mx-auto mt-12 max-w-4xl">
            @if (count($proPeriods) > 1)
                <div class="mb-8 flex justify-center">
                    <div class="inline-flex rounded-2xl bg-slate-100 p-1 text-sm font-semibold dark:bg-slate-800/80" role="tablist">
                        @foreach ($proPeriods as $key => $plan)
                            <button type="button" @click="period = @js($key)"
                                    :class="period === @js($key) ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
                                    class="flex items-center gap-2 rounded-xl px-4 py-2 transition">
                                {{ $periodLabels[$key] }}
                                @if (! empty($plan['badge']) && $key === 'yearly')
                                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-400">{{ $plan['badge'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid gap-5 lg:grid-cols-2">
                <div class="em-card flex flex-col p-8">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $freePlan['name'] ?? 'Community' }}</h3>
                        <span class="rounded-full bg-emerald-500/12 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-500/25 dark:text-emerald-400">{{ $tr ? 'Kalıcı ücretsiz' : 'Free forever' }}</span>
                    </div>
                    <div class="mt-5 flex items-baseline gap-1.5">
                        <span class="text-5xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $freePlan['price_label'] ?? ($tr ? '₺0' : '$0') }}</span>
                        <span class="text-sm text-slate-500">{{ $freePlan['period_label'] ?? '' }}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $tr ? 'Sunucu başına '.$freeMaxSites.' siteye kadar' : 'Up to '.$freeMaxSites.' sites per server' }}</p>
                    <ul class="mt-7 flex-1 space-y-3 text-sm text-slate-700 dark:text-slate-300">
                        @foreach ($freeFeatures as $line)
                            <li class="flex gap-3"><svg class="mt-0.5 h-4 w-4 flex-none text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>{{ $line }}</span></li>
                        @endforeach
                    </ul>
                    <a href="{{ route('site.setup') }}" class="mt-8 inline-flex w-full items-center justify-center rounded-[0.875rem] border border-slate-300 py-3 text-sm font-semibold text-slate-800 transition hover:border-orange-300 hover:bg-orange-50/50 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-800">
                        {{ $tr ? 'Ücretsiz kurun' : 'Install for free' }}
                    </a>
                </div>

                <div class="relative flex flex-col overflow-hidden rounded-[1.25rem] p-8 text-white shadow-[0_30px_60px_-24px_rgb(234_88_12/0.55)]" style="background: linear-gradient(160deg, #2a1c12 0%, #15110e 60%);">
                    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(80%_60%_at_100%_0%,rgb(249_115_22/0.4),transparent_60%)]" aria-hidden="true"></div>
                    <div class="pointer-events-none absolute inset-0 rounded-[1.25rem] ring-1 ring-inset ring-orange-400/40" aria-hidden="true"></div>
                    <div class="relative flex items-center justify-between">
                        <h3 class="text-lg font-bold">Panelze Pro</h3>
                        <span class="rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-2.5 py-0.5 text-xs font-bold text-white">{{ $tr ? 'En popüler' : 'Most popular' }}</span>
                    </div>
                    @if ($proPeriods)
                        @foreach ($proPeriods as $key => $plan)
                            <div x-show="period === @js($key)" @if ($key !== $proDefaultPeriod) x-cloak @endif class="relative">
                                <div class="mt-5 flex items-baseline gap-1.5">
                                    <span class="text-5xl font-extrabold tracking-tight">{{ $plan['price_label'] ?? '' }}</span>
                                    <span class="text-sm text-slate-300">{{ $plan['period_label'] ?? '' }}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-300">
                                    @if (! empty($plan['monthly_equiv_label']))
                                        {{ $tr ? 'Aylık '.$plan['monthly_equiv_label'].' karşılığı' : $plan['monthly_equiv_label'].' per month' }}
                                    @elseif ($key === 'lifetime')
                                        {{ $tr ? 'Tek ödeme, süresiz lisans' : 'One payment, perpetual license' }}
                                    @else
                                        {{ $tr ? 'İstediğiniz zaman iptal edin' : 'Cancel any time' }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    @endif
                    <ul class="relative mt-7 flex-1 space-y-3 text-sm text-slate-200">
                        @foreach (array_slice($proFeatures, 0, 7) as $i => $line)
                            <li class="flex gap-3"><svg class="mt-0.5 h-4 w-4 flex-none text-orange-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span @class(['font-semibold text-white' => $i === 0])>{{ $line }}</span></li>
                        @endforeach
                    </ul>
                    <a href="{{ route('site.pricing') }}" class="btn-primary relative mt-8 w-full !py-3">
                        {{ $tr ? 'Pro’ya geçin' : 'Get Pro' }} <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>

            <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
                {{ landing_p('home.pricing_page_cta') }}
                <a href="{{ route('site.pricing') }}" class="font-semibold text-orange-700 hover:underline dark:text-orange-400">{{ landing_p('home.pricing_page_link') }}</a>.
            </p>
        </div>
    </div>
</section>

{{-- ============================== SSS ============================== --}}
<section class="relative mt-24 lg:mt-32">
    <div class="hv-container">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
            <div>
                <span class="em-eyebrow">{{ $tr ? 'SSS' : 'FAQ' }}</span>
                <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">{{ $tr ? 'Sık sorulan sorular' : 'Frequently asked questions' }}</h2>
                <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ $tr ? 'Aradığınızı bulamadınız mı? Topluluğa sorun ya da dokümantasyona göz atın.' : 'Can’t find what you need? Ask the community or browse the docs.' }}
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('community.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-orange-300 hover:text-orange-700 dark:border-slate-800 dark:text-slate-200 dark:hover:text-orange-300">{{ $tr ? 'Topluluk' : 'Community' }}</a>
                    <a href="{{ route('docs.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-orange-300 hover:text-orange-700 dark:border-slate-800 dark:text-slate-200 dark:hover:text-orange-300">{{ $tr ? 'Dokümantasyon' : 'Documentation' }}</a>
                </div>
            </div>
            <div x-data="{ open: 0 }" class="space-y-3">
                @foreach ($faqs as $i => $faq)
                    <div class="em-card overflow-hidden">
                        <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left" :aria-expanded="open === {{ $i }}">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $faq[0] }}</span>
                            <svg class="h-4 w-4 flex-none text-slate-400 transition-transform" :class="open === {{ $i }} && 'rotate-45 text-orange-500'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        </button>
                        <div x-show="open === {{ $i }}" @if ($i !== 0) x-cloak @endif x-transition.opacity class="px-5 pb-5 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $faq[1] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============================== KAPANIŞ ============================== --}}
<section id="docs" class="relative mb-24 mt-24 lg:mt-32">
    <div class="hv-container">
        <div class="em-ink relative overflow-hidden rounded-[2rem] px-6 py-16 text-center sm:px-12">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_90%_at_50%_0%,rgb(249_115_22/0.4),transparent_65%)]" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full border border-white/10" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full border border-white/10" aria-hidden="true"></div>
            <div class="relative mx-auto max-w-2xl">
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ landing_p('home.docs_title') }}</h2>
                <p class="mt-4 text-base leading-relaxed text-slate-300">{{ landing_p('home.docs_lead') }}</p>

                <div x-data="{ copied: false }" class="mx-auto mt-8 flex max-w-lg items-center gap-2 rounded-2xl bg-white/[0.06] p-1.5 pl-4 text-left ring-1 ring-inset ring-white/10">
                    <span class="select-none font-mono text-sm font-semibold text-orange-400">$</span>
                    <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap font-mono text-[13px] text-slate-100">{{ $oneLiner }}</code>
                    <button type="button"
                            @click="navigator.clipboard.writeText(@js($oneLiner)); copied = true; setTimeout(() => copied = false, 1500)"
                            class="inline-flex flex-none items-center gap-1.5 rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20">
                        <span x-show="!copied">{{ $tr ? 'Kopyala' : 'Copy' }}</span>
                        <span x-show="copied" x-cloak>{{ $tr ? 'Kopyalandı' : 'Copied' }}</span>
                    </button>
                </div>

                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('site.setup') }}" class="btn-primary !px-7 !py-3.5 !text-base">{{ $tr ? 'Kurulum rehberi' : 'Setup guide' }} <span aria-hidden="true">→</span></a>
                    <a href="{{ route('site.demo') }}" class="inline-flex items-center gap-2 rounded-[0.875rem] bg-white/10 px-7 py-3.5 text-base font-semibold text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/15">{{ $tr ? 'Canlı demo' : 'Live demo' }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
