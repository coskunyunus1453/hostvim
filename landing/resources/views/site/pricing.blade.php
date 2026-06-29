@php
    $tr = app()->getLocale() === 'tr';
    $pageTitle = landing_t('pricing_page.title');
    $meta = $intro?->meta_description ?? landing_t('pricing_page.meta_default');
    $free = $pricing['free'];
    $pro = $pricing['pro'];
    $defaultCycle = isset($pro['yearly']) ? 'yearly' : (isset($pro['monthly']) ? 'monthly' : 'lifetime');

    // Pro modülleri locale'e göre tek listede normalize et (label + açıklama).
    $bullets = \App\Support\PanelFeatureCatalog::proModuleMarketingBullets($tr ? 'tr' : 'en');
    $modList = [];
    foreach ($pricing['pro_modules'] as $mod) {
        $b = $bullets[$mod['key']] ?? ($mod['label'].' — '.$mod['description']);
        [$lbl, $desc] = array_pad(explode(' — ', $b, 2), 2, '');
        $modList[] = ['key' => $mod['key'], 'label' => $lbl, 'description' => $desc];
    }
@endphp

<x-site.layout :title="$pageTitle" :description="$meta" :canonical-url="$seoCanonical ?? null">
    <div class="hv-container">
        {{-- Başlık --}}
        <div class="mx-auto mb-12 max-w-3xl text-center">
            <div class="mb-4 flex justify-center">
                <span class="hv-pill">
                    <span class="hv-accent-dot h-2 w-2 rounded-full"></span>
                    {{ $tr ? 'Şeffaf fiyatlandırma · Gizli ücret yok' : 'Transparent pricing · no hidden fees' }}
                </span>
            </div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl lg:leading-tight dark:text-slate-50">
                {{ $tr ? 'Ücretsiz başla, hazır olduğunda Pro’ya geç' : 'Start free, upgrade to Pro when you are ready' }}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg leading-relaxed text-slate-600 dark:text-slate-400">
                {{ $tr
                    ? 'Panelze’yi kendi sunucunuza kurun. Community sürümü kalıcı ücretsiz; Pro tek lisansla tüm gelişmiş modülleri açar — sunucu başına 500 siteye kadar.'
                    : 'Install Panelze on your own server. Community is free forever; a single Pro license unlocks every advanced module — up to 500 sites per server.' }}
            </p>
        </div>

        <div x-data="{ cycle: '{{ $defaultCycle }}' }" x-cloak>
            {{-- Faturalama döngüsü seçici --}}
            @if (count($pro) > 1)
                <div class="mb-10 flex justify-center">
                    <div class="inline-flex flex-wrap items-center justify-center gap-1 rounded-full border border-slate-200/90 bg-white/80 p-1 text-sm font-semibold shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
                        @isset($pro['monthly'])
                            <button type="button" @click="cycle='monthly'"
                                    :class="cycle==='monthly' ? 'bg-[rgb(var(--hv-brand-600)/1)] text-white shadow' : 'text-slate-600 dark:text-slate-300'"
                                    class="rounded-full px-4 py-2 transition">{{ $tr ? 'Aylık' : 'Monthly' }}</button>
                        @endisset
                        @isset($pro['yearly'])
                            <button type="button" @click="cycle='yearly'"
                                    :class="cycle==='yearly' ? 'bg-[rgb(var(--hv-brand-600)/1)] text-white shadow' : 'text-slate-600 dark:text-slate-300'"
                                    class="relative rounded-full px-4 py-2 transition">
                                {{ $tr ? 'Yıllık' : 'Yearly' }}
                                @if (! empty($pricing['yearly_savings_pct']))
                                    <span class="ml-1.5 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[0.7rem] font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                        −%{{ $pricing['yearly_savings_pct'] }}
                                    </span>
                                @endif
                            </button>
                        @endisset
                        @isset($pro['lifetime'])
                            <button type="button" @click="cycle='lifetime'"
                                    :class="cycle==='lifetime' ? 'bg-[rgb(var(--hv-brand-600)/1)] text-white shadow' : 'text-slate-600 dark:text-slate-300'"
                                    class="rounded-full px-4 py-2 transition">{{ $tr ? 'Ömür Boyu' : 'Lifetime' }}</button>
                        @endisset
                    </div>
                </div>
            @endif

            {{-- Planlar --}}
            <div class="mx-auto grid max-w-4xl gap-6 lg:grid-cols-2">
                {{-- Community (Ücretsiz) --}}
                <div class="relative flex flex-col rounded-3xl border border-slate-200/90 bg-white/90 p-7 dark:border-slate-800 dark:bg-slate-900/60">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $free['name'] }}</h2>
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $tr ? 'Ücretsiz' : 'Free' }}</span>
                    </div>
                    <p class="mt-2 text-base text-slate-600 dark:text-slate-400">
                        {{ $tr ? 'Tek sunucuda çekirdek hosting paneli. Kart gerekmez.' : 'The core hosting panel on one server. No card required.' }}
                    </p>
                    <div class="mt-5 flex items-baseline gap-1.5">
                        <span class="text-4xl font-bold text-slate-900 dark:text-slate-50">{{ $free['price_label'] }}</span>
                        <span class="text-sm text-slate-500">{{ $free['period_label'] }}</span>
                    </div>
                    <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">{{ $tr ? 'Sunucu başına 5 siteye kadar' : 'Up to 5 sites per server' }}</p>

                    <ul class="mt-6 flex-1 space-y-2.5 text-base text-slate-700 dark:text-slate-300">
                        @foreach ($free['features'] as $line)
                            <li class="flex gap-2.5">
                                <svg class="mt-0.5 h-5 w-5 flex-none text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span>{{ $line }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('site.setup') }}" class="hv-btn-secondary mt-7 w-full justify-center py-3 text-base">
                        {{ $tr ? 'Ücretsiz kur' : 'Install free' }}
                    </a>
                </div>

                {{-- Pro --}}
                <div class="hv-card-pro flex flex-col rounded-3xl p-7">
                    <span class="hv-card-pro-badge">{{ $tr ? 'En popüler' : 'Most popular' }}</span>
                    <div class="flex items-center gap-2 pr-20">
                        <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50">Panelze Pro</h2>
                    </div>
                    <p class="mt-2 text-base text-slate-600 dark:text-slate-300">
                        {{ $tr ? 'Tüm Pro modüller, gelişmiş güvenlik, yedekleme ve AI — tek lisansla.' : 'Every Pro module, advanced security, backups, and AI — in one license.' }}
                    </p>

                    @if (count($pro) === 0)
                        <p class="mt-6 rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-slate-700">
                            {{ $tr ? 'Fiyatlar çok yakında.' : 'Pricing coming soon.' }}
                        </p>
                    @else
                        {{-- Döngüye göre fiyat --}}
                        @foreach ($pro as $bucket => $tier)
                            <div x-show="cycle==='{{ $bucket }}'" x-transition.opacity class="mt-5">
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-4xl font-bold hv-text-brand">{{ $tier['price_label'] }}</span>
                                    <span class="text-sm text-slate-500 dark:text-slate-400">{{ $tier['period_label'] }}</span>
                                </div>
                                <p class="mt-1 h-5 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                    @if ($bucket === 'yearly' && ! empty($tier['monthly_equiv_label']))
                                        {{ $tr ? 'Ayda yalnızca '.$tier['monthly_equiv_label'].' · %'.($pricing['yearly_savings_pct'] ?? 0).' tasarruf' : 'Just '.$tier['monthly_equiv_label'].'/mo · save '.($pricing['yearly_savings_pct'] ?? 0).'%' }}
                                    @elseif ($bucket === 'lifetime')
                                        {{ $tr ? 'Tek ödeme · ömür boyu güncelleme' : 'One payment · lifetime updates' }}
                                    @else
                                        {{ $tr ? 'İstediğin zaman iptal et' : 'Cancel anytime' }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                        <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">{{ $tr ? 'Sunucu başına 500 siteye kadar' : 'Up to 500 sites per server' }}</p>
                    @endif

                    <ul class="mt-6 flex-1 space-y-2.5 text-base text-slate-800 dark:text-slate-200">
                        <li class="flex gap-2.5">
                            <svg class="mt-0.5 h-5 w-5 flex-none text-[rgb(var(--hv-brand-600)/1)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span class="font-semibold">{{ $tr ? 'Community’deki her şey dahil' : 'Everything in Community' }}</span>
                        </li>
                        @foreach ($modList as $mod)
                            <li class="flex gap-2.5">
                                <svg class="mt-0.5 h-5 w-5 flex-none text-[rgb(var(--hv-brand-600)/1)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span>{{ $mod['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('site.buy') }}" class="hv-btn-primary mt-7 w-full justify-center py-3 text-base">
                        {{ $tr ? 'Pro’ya geç' : 'Get Pro' }}
                        <span class="text-sm opacity-90">→</span>
                    </a>
                    <p class="mt-3 text-center text-xs text-slate-500 dark:text-slate-500">
                        {{ $tr ? 'Türkiye’de PayTR · Global Stripe ile güvenli ödeme' : 'Secure checkout via Stripe (PayTR in Türkiye)' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Pro ile açılan modüller --}}
        <div class="mt-24">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <div class="hv-section-eyebrow justify-center">{{ $tr ? 'Pro modüller' : 'Pro modules' }}</div>
                <h2 class="hv-section-title">{{ $tr ? 'Lisansla açılan gelişmiş yetenekler' : 'Advanced capabilities unlocked by your license' }}</h2>
                <p class="hv-section-lead">{{ $tr ? 'Pro lisans anahtarını panele girdiğiniz an aşağıdaki modüllerin tümü etkinleşir.' : 'The moment you enter your Pro key in the panel, all of the modules below light up.' }}</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($modList as $mod)
                    <article class="hv-glass flex flex-col gap-2 rounded-2xl p-6">
                        <div class="hv-card-icon">
                            <x-landing.feature-icon :name="match($mod['key']) {
                                'phpmyadmin_sso' => 'database',
                                'security_pro' => 'shield',
                                'backups_pro' => 'layers',
                                'monitoring_advanced' => 'cpu',
                                'ai_advisor' => 'rocket',
                                'curious_tools' => 'terminal',
                                'stripe_billing' => 'layers',
                                'vendor_panel' => 'users',
                                default => 'layers',
                            }" />
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $mod['label'] }}</h3>
                        <p class="text-base leading-relaxed text-slate-600 dark:text-slate-400">{{ $mod['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>

        {{-- Karşılaştırma --}}
        <div class="mt-24">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <div class="hv-section-eyebrow justify-center">{{ $tr ? 'Karşılaştırma' : 'Compare' }}</div>
                <h2 class="hv-section-title">Community {{ $tr ? 've' : 'vs' }} Pro</h2>
            </div>
            <div class="mx-auto grid max-w-4xl gap-6 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200/90 bg-white/80 p-6 dark:border-slate-800 dark:bg-slate-900/50">
                    <h3 class="mb-4 text-base font-semibold text-slate-900 dark:text-slate-100">{{ $tr ? 'Community (Ücretsiz)' : 'Community (Free)' }}</h3>
                    <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        @foreach ($free['features'] as $line)
                            <li class="flex gap-2"><span class="text-slate-400">•</span><span>{{ $line }}</span></li>
                        @endforeach
                    </ul>
                </div>
                <div class="rounded-2xl border border-[rgb(var(--hv-brand-500)/0.4)] bg-[rgb(var(--hv-brand-500)/0.04)] p-6">
                    <h3 class="mb-4 text-base font-semibold hv-text-brand">{{ $tr ? 'Pro (+ ek olarak)' : 'Pro (+ adds)' }}</h3>
                    <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
                        @foreach ($modList as $mod)
                            <li class="flex gap-2"><span class="hv-text-brand">+</span><span><span class="font-medium">{{ $mod['label'] }}</span> — {{ $mod['description'] }}</span></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- SSS --}}
        <div class="mx-auto mt-24 max-w-3xl">
            <div class="mb-8 text-center">
                <div class="hv-section-eyebrow justify-center">{{ $tr ? 'Sıkça sorulanlar' : 'FAQ' }}</div>
                <h2 class="hv-section-title">{{ $tr ? 'Aklınızdaki sorular' : 'Questions, answered' }}</h2>
            </div>
            <div class="space-y-3" x-data="{ open: 0 }">
                @php
                    $faqs = $tr ? [
                        ['Lisans nasıl çalışıyor?', 'Pro lisans anahtarınızı panelin lisans ekranına girersiniz; tüm Pro modüller anında açılır. Anahtar sunucu/domaine bağlı çevrimdışı imzalı (Ed25519) doğrulanır, internet kesintisinde bile çalışır.'],
                        ['Aylık, yıllık ve ömür boyu arasındaki fark ne?', 'Aylık ve yıllık abonelik otomatik yenilenir, istediğiniz zaman iptal edebilirsiniz. Ömür boyu tek ödemedir ve gelecekteki güncellemeleri kapsar.'],
                        ['Kaç site/sunucu kullanabilirim?', 'Community sunucu başına 5 site, Pro ise sunucu başına 500 siteye kadar destekler. Her sunucu için bir lisans gerekir.'],
                        ['Türkiye’den nasıl ödeme yaparım?', 'Türkiye’de PayTR ile kredi kartı/taksit, global müşteriler için Stripe ile ödeme alınır. Fiyatlar bölgenize göre TL veya döviz olarak gösterilir.'],
                        ['Community’den Pro’ya yükseltince verilerim ne olur?', 'Hiçbir şey kaybolmaz. Aynı kurulumda lisans anahtarını girmeniz yeterli; siteleriniz ve ayarlarınız olduğu gibi kalır.'],
                    ] : [
                        ['How does licensing work?', 'You paste your Pro license key into the panel’s license screen and every Pro module unlocks instantly. Keys are offline-signed (Ed25519) and bound to your server/domain, so they keep working even without internet.'],
                        ['Monthly vs yearly vs lifetime?', 'Monthly and yearly are auto-renewing subscriptions you can cancel anytime. Lifetime is a single payment that includes future updates.'],
                        ['How many sites/servers can I run?', 'Community allows 5 sites per server; Pro supports up to 500 sites per server. One license is required per server.'],
                        ['How do I pay?', 'Customers in Türkiye check out with PayTR (cards/installments); global customers use Stripe. Prices are shown in your local currency.'],
                        ['What happens to my data when I upgrade?', 'Nothing is lost. Just enter the key on the same install — your sites and settings stay exactly as they are.'],
                    ];
                @endphp
                @foreach ($faqs as $i => $faq)
                    <div class="rounded-2xl border border-slate-200/90 bg-white/80 dark:border-slate-800 dark:bg-slate-900/50">
                        <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-base font-semibold text-slate-900 dark:text-slate-100">
                            <span>{{ $faq[0] }}</span>
                            <svg class="h-5 w-5 flex-none text-slate-400 transition" :class="open === {{ $i }} ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div x-show="open === {{ $i }}" x-transition x-cloak class="px-5 pb-4 text-base leading-relaxed text-slate-600 dark:text-slate-400">
                            {{ $faq[1] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="mt-16 text-center text-sm text-slate-500 dark:text-slate-500">
            <a href="{{ route('landing.home') }}" class="hv-link-quiet font-semibold">{{ landing_t('pricing_page.back_home') }}</a>
        </p>
    </div>
</x-site.layout>
