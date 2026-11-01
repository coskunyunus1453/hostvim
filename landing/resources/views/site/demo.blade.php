@php
    $pageTitle = $tr ? 'Canlı Demo · Panelze' : 'Live Demo · Panelze';
    $meta = $tr
        ? 'Kart gerekmez. Örnek sunucu ve örnek sitelerle Panelze’yi test edin — gerçek müşteri verisi yok.'
        : 'No card required. Try Panelze on a sample server with sample sites — no real customer data.';
@endphp

<x-site.layout :title="$pageTitle" :description="$meta" :canonical-url="$seoCanonical ?? null">
    <div class="hv-container py-10 sm:py-14">
        <div class="mx-auto max-w-3xl text-center">
            <span class="hv-pill inline-flex items-center gap-2">
                <span class="hv-accent-dot h-2 w-2 rounded-full"></span>
                {{ $tr ? 'Public sandbox · izole örnek veri' : 'Public sandbox · isolated sample data' }}
            </span>
            <h1 class="mt-5 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl dark:text-slate-50">
                {{ $tr ? 'Panelze’yi satın almadan deneyin' : 'Try Panelze before you buy' }}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-slate-600 sm:text-lg dark:text-slate-400">
                {{ $tr
                    ? 'Paylaşımlı demo hesabı yalnızca örnek siteleri gösterir. Gerçek müşteri siteleri bu hesapta görünmez; yazma işlemleri kısıtlıdır ve ortam birkaç saatte bir sıfırlanır.'
                    : 'The shared demo account only shows sample sites. Real customer sites are never visible here; writes are limited and the sandbox resets every few hours.' }}
            </p>
        </div>

        <div class="mx-auto mt-10 grid max-w-4xl gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-3xl border border-slate-200/90 bg-white/90 p-7 dark:border-slate-800 dark:bg-slate-900/60">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    {{ $tr ? 'Demo giriş bilgileri' : 'Demo credentials' }}
                </h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="font-medium text-slate-500 dark:text-slate-400">{{ $tr ? 'E-posta' : 'Email' }}</dt>
                        <dd class="mt-1 break-all font-mono text-base text-slate-900 dark:text-slate-100">{{ $email }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500 dark:text-slate-400">{{ $tr ? 'Şifre' : 'Password' }}</dt>
                        <dd class="mt-1 font-mono text-base text-slate-900 dark:text-slate-100">{{ $password }}</dd>
                    </div>
                </dl>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $loginUrl }}"
                       class="inline-flex items-center justify-center rounded-xl bg-[rgb(var(--hv-brand-600)/1)] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:brightness-110"
                       rel="noopener">
                        {{ $tr ? 'Demo panele gir →' : 'Open demo panel →' }}
                    </a>
                    <a href="{{ route('site.pricing') }}"
                       class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-[rgb(var(--hv-brand-500)/0.45)] dark:border-slate-700 dark:text-slate-200">
                        {{ $tr ? 'Fiyatlandırma' : 'Pricing' }}
                    </a>
                </div>
                <p class="mt-4 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    {{ $tr
                        ? 'Buton alanları otomatik doldurur. Giriş sonrası yalnızca demo-shop, demo-blog ve demo-app örnek sitelerini görürsünüz.'
                        : 'The button pre-fills the login form. After sign-in you only see the demo-shop, demo-blog and demo-app sample sites.' }}
                </p>
            </div>

            <div class="rounded-3xl border border-slate-200/90 bg-slate-50/80 p-7 dark:border-slate-800 dark:bg-slate-950/40">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    {{ $tr ? 'Örnek sunucuda neler var?' : 'What’s on the sample server?' }}
                </h2>
                <ul class="mt-5 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                    @forelse ($sites as $site)
                        <li class="flex gap-3">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-[rgb(var(--hv-brand-500)/1)]"></span>
                            <span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $site['title'] ?? $site['name'] }}</span>
                                <span class="block text-slate-500 dark:text-slate-400">{{ $site['name'] ?? '' }}</span>
                                @if (! empty($site['blurb']))
                                    <span class="mt-0.5 block text-slate-500 dark:text-slate-400">{{ $site['blurb'] }}</span>
                                @endif
                            </span>
                        </li>
                    @empty
                        <li>{{ $tr ? 'Demo Shop, Demo Blog, Demo App örnek siteleri' : 'Demo Shop, Demo Blog, Demo App sample sites' }}</li>
                    @endforelse
                </ul>
                <div class="mt-6 rounded-2xl border border-amber-500/25 bg-amber-500/10 px-4 py-3 text-sm text-amber-950 dark:text-amber-100">
                    {{ $tr
                        ? 'Güvenlik: admin, kullanıcı yönetimi, lisans ve entegrasyon API’leri demo hesabında kapalıdır.'
                        : 'Security: admin, user management, license and integration APIs are disabled on the demo account.' }}
                </div>
            </div>
        </div>

        <div class="mx-auto mt-12 max-w-3xl text-center">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ $tr ? 'Beğendiyseniz kendi sunucunuza kurun — Community ücretsizdir.' : 'Like it? Install on your own server — Community is free.' }}
            </p>
            <a href="{{ route('site.setup') }}" class="mt-3 inline-flex text-sm font-semibold text-[rgb(var(--hv-brand-600)/1)] hover:underline dark:text-[rgb(var(--hv-brand-400)/1)]">
                {{ $tr ? 'Kurulum rehberine git' : 'Go to installation guide' }}
            </a>
        </div>
    </div>
</x-site.layout>
