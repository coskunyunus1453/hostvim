@php
    use App\Services\InstallGuide;

    $tr = app()->getLocale() === 'tr';
    $oneLiner = InstallGuide::oneLiner();
    $community = InstallGuide::community();
    $pro = InstallGuide::pro($tr ? 'hv_ANAHTARINIZ' : 'hv_YOUR_KEY');
    $adminCmd = 'sudo cat ' . rtrim((string) (InstallGuide::settings()['admin_login_file'] ?? '/root/panelze-admin-login.txt'));

    $steps = $tr ? [
        ['1', 'Sunucu hazırla', 'Boş bir Debian/Ubuntu VPS açın ve root ya da sudo erişiminizin olduğundan emin olun.'],
        ['2', 'Komutu çalıştır', 'Aşağıdaki tek satır komutu sunucuda çalıştırın; web sunucusu, PHP, veritabanı ve panel otomatik kurulur.'],
        ['3', 'Panele giriş yap', 'Kurulum sonunda oluşturulan yönetici bilgisiyle panele girin ve ilk sitenizi oluşturun.'],
    ] : [
        ['1', 'Prepare a server', 'Spin up a fresh Debian/Ubuntu VPS and make sure you have root or sudo access.'],
        ['2', 'Run the command', 'Run the one-line command on the server; web server, PHP, database and panel install automatically.'],
        ['3', 'Sign in', 'Log in with the admin credentials generated at the end of the install and create your first site.'],
    ];

    $reqs = $tr ? [
        ['os', 'İşletim sistemi', 'Debian / Ubuntu (22.04 LTS önerilir)'],
        ['cpu', 'Donanım', '2 vCPU · 4 GB RAM (başlangıç)'],
        ['key', 'Erişim', 'root veya sudo yetkisi'],
        ['globe', 'DNS', 'Alan adının A kaydı sunucuya'],
    ] : [
        ['os', 'Operating system', 'Debian / Ubuntu (22.04 LTS recommended)'],
        ['cpu', 'Hardware', '2 vCPU · 4 GB RAM (to start)'],
        ['key', 'Access', 'root or sudo privileges'],
        ['globe', 'DNS', 'Domain A record pointing to server'],
    ];

    $advanced = collect(InstallGuide::sectionsForLocale(app()->getLocale()))
        ->whereIn('label', $tr
            ? ['Güvenli güncelleme (Community)', 'Güvenli güncelleme (Pro)', 'Uzak kurulum (git + bootstrap)', 'Elle kurulum (operatör)', 'Panel güncelleme (git pull sonrası)', 'Engine yeniden derleme', 'Kurulum sonrası onarım']
            : ['Safe update (Community)', 'Safe update (Pro)', 'Remote install (git + bootstrap)', 'Manual (operator)', 'Panel update (after git pull)', 'Rebuild Engine binary', 'Post-install repair']
        )
        ->values();
@endphp

<x-site.layout
    :title="$page->effectiveMetaTitle() . ' · ' . landing_p('brand.name')"
    :description="$seoDescription"
    :canonical-url="$seoCanonical"
    :og-title="$page->effectiveMetaTitle()"
    :og-description="$seoDescription"
    :og-image="$seoOgImage"
    :robots-content="$seoRobots ?: null"
    :schema-json-ld="$seoSchema"
>
    {{-- ===================== HERO ===================== --}}
    <section class="relative overflow-hidden">
        <div class="hv-container">
            <div class="mx-auto max-w-3xl text-center">
                <div class="mb-5 flex justify-center">
                    <span class="hv-pill">
                        <span class="hv-accent-dot h-2 w-2 rounded-full shadow-[0_0_0_4px_rgb(var(--hv-brand-500)/0.25)]"></span>
                        {{ $tr ? 'Tek komutla kurulum' : 'One-command install' }}
                    </span>
                </div>

                <h1 class="text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-50">
                    {{ $tr ? 'Kurulum' : 'Installation' }}
                </h1>
                <p class="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ $tr
                        ? 'Boş bir Debian/Ubuntu sunucuda tek satır komutla panelinizi dakikalar içinde yayına alın.'
                        : 'Bring your panel live in minutes with a single line on a fresh Debian/Ubuntu server.' }}
                </p>

                <div class="mx-auto mt-8 max-w-xl">
                    <x-landing.copy-command :command="$oneLiner" />
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-500">
                        {{ $tr ? 'Debian/Ubuntu sunucuda root/sudo · dakikalar içinde yayında' : 'On a Debian/Ubuntu server as root/sudo · live in minutes' }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== 3 ADIM ===================== --}}
    <section class="relative mt-16 sm:mt-20">
        <div class="hv-container">
            <div class="mx-auto grid max-w-5xl gap-4 sm:grid-cols-3">
                @foreach ($steps as $i => $step)
                    <div class="relative rounded-2xl border border-slate-200/90 bg-white/80 p-6 dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[rgb(var(--hv-brand-600)/1)] text-lg font-bold text-white shadow-sm">{{ $step[0] }}</div>
                        <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-slate-100">{{ $step[1] }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $step[2] }}</p>
                        @unless ($loop->last)
                            <span class="absolute -right-2.5 top-1/2 z-10 hidden h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 sm:flex dark:border-slate-700 dark:bg-slate-900">→</span>
                        @endunless
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== GEREKSİNİMLER ===================== --}}
    <section class="relative mt-16 sm:mt-20">
        <div class="hv-container">
            <div class="mx-auto max-w-5xl">
                <h2 class="text-center text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">{{ $tr ? 'Gereksinimler' : 'Requirements' }}</h2>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($reqs as $req)
                        <div class="rounded-2xl border border-slate-200/90 bg-white/80 p-5 dark:border-slate-800 dark:bg-slate-900/60">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[rgb(var(--hv-brand-500)/0.12)] text-[rgb(var(--hv-brand-600)/1)] dark:text-[rgb(var(--hv-brand-400)/1)]">
                                @switch($req[0])
                                    @case('os')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4" stroke-linecap="round"/></svg>
                                        @break
                                    @case('cpu')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="6" y="6" width="12" height="12" rx="2"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M1 9h3M1 15h3M20 9h3M20 15h3" stroke-linecap="round"/></svg>
                                        @break
                                    @case('key')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="8" r="4"/><path d="M11 11l9 9M17 17l2-2M14 14l2-2" stroke-linecap="round"/></svg>
                                        @break
                                    @default
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18M12 3c-2.5 2.5-2.5 15 0 18"/></svg>
                                @endswitch
                            </div>
                            <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $req[1] }}</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $req[2] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== KURULUM SEÇENEKLERİ ===================== --}}
    <section class="relative mt-16 sm:mt-20">
        <div class="hv-container">
            <div class="mx-auto max-w-5xl">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">{{ $tr ? 'Kurulum komutları' : 'Install commands' }}</h2>
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
                        {{ $tr ? 'İhtiyacınıza göre ücretsiz Community veya lisanslı Pro sürümünü kurun.' : 'Install the free Community or the licensed Pro edition, depending on your needs.' }}
                    </p>
                </div>

                <div class="mt-8 grid gap-5 lg:grid-cols-2">
                    {{-- Community --}}
                    <div class="flex flex-col rounded-2xl border border-slate-200/90 bg-white/80 p-6 dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Community</h3>
                            <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ $tr ? 'Ücretsiz' : 'Free' }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $tr ? 'Sunucu başına 5 siteye kadar, kalıcı ücretsiz.' : 'Up to 5 sites per server, free forever.' }}</p>
                        <div class="mt-4">
                            <x-landing.copy-command :command="$community" />
                        </div>
                    </div>

                    {{-- Pro --}}
                    <div class="flex flex-col rounded-2xl border-2 border-[rgb(var(--hv-brand-500)/0.6)] bg-white/90 p-6 shadow-lg shadow-[rgb(var(--hv-brand-500)/0.08)] dark:bg-slate-900/70">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Pro</h3>
                            <span class="rounded-full bg-[rgb(var(--hv-brand-500)/0.14)] px-2.5 py-1 text-xs font-semibold text-[rgb(var(--hv-brand-600)/1)] dark:text-[rgb(var(--hv-brand-400)/1)]">{{ $tr ? 'Lisanslı' : 'Licensed' }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $tr ? 'Sınırsız site + tüm Pro modüller. Anahtarı komuta ekleyin.' : 'Unlimited sites + all Pro modules. Add your key to the command.' }}</p>
                        <div class="mt-4">
                            <x-landing.copy-command :command="$pro" :note="$tr ? 'hv_ANAHTARINIZ yerine Panelze hesabınızdaki veya satın alma e-postanızdaki anahtarı yazın.' : 'Replace hv_YOUR_KEY with the key from your Panelze account or purchase email.'" />
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('site.pricing') }}" class="hv-link text-sm font-semibold">{{ $tr ? 'Pro fiyatlarını gör →' : 'See Pro pricing →' }}</a>
                        </div>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
                    @if ($tr)
                        <strong>Önemli:</strong> Komutları yalnızca güvendiğiniz bir Debian/Ubuntu VPS üzerinde root/sudo ile çalıştırın.
                    @else
                        <strong>Important:</strong> Run these only on a trusted Debian/Ubuntu VPS as root/sudo.
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== KURULUM SONRASI ===================== --}}
    <section class="relative mt-16 sm:mt-20">
        <div class="hv-container">
            <div class="mx-auto max-w-3xl rounded-2xl border border-slate-200/90 bg-gradient-to-br from-white to-slate-50/60 p-6 sm:p-8 dark:border-slate-800 dark:from-slate-900/70 dark:to-slate-950/40">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">{{ $tr ? 'Kurulumdan sonra' : 'After install' }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    {{ $tr ? 'Kurulum bitince ilk yönetici bilgilerini şu komutla görüntüleyin, panele girin ve parolanızı değiştirin.' : 'When install finishes, view the first admin credentials with the command below, sign in and change your password.' }}
                </p>
                <div class="mt-4">
                    <x-landing.copy-command :command="$adminCmd" />
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== GELİŞMİŞ (açılır) ===================== --}}
    @if ($advanced->isNotEmpty())
    <section class="relative mt-16 sm:mt-20">
        <div class="hv-container">
            <div class="mx-auto max-w-3xl" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                        class="flex w-full items-center justify-between rounded-2xl border border-slate-200/90 bg-white/80 px-5 py-4 text-left dark:border-slate-800 dark:bg-slate-900/60">
                    <span class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $tr ? 'Gelişmiş: güncelleme, manuel ve onarım komutları' : 'Advanced: update, manual and repair commands' }}</span>
                    <svg class="h-5 w-5 text-slate-400 transition" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>

                <div x-show="open" x-cloak x-transition class="mt-4 space-y-4">
                    @foreach ($advanced as $section)
                        <div class="rounded-2xl border border-slate-200/90 bg-white/70 p-5 dark:border-slate-800 dark:bg-slate-900/50">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $section['label'] }}</h3>
                            @if (! empty($section['note']))
                                <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ $section['note'] }}</p>
                            @endif
                            <div class="mt-3">
                                <x-landing.copy-command :command="$section['command']" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif
</x-site.layout>
