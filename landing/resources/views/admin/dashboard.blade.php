@php
    use Illuminate\Support\Str;
    $k = $kpis;
    $pendingTotal = ($k['community_pending_topics'] ?? 0) + ($k['community_pending_posts'] ?? 0);
@endphp
<x-admin.layout title="Özet">
    <div class="space-y-8">
        <div class="admin-hero">
            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="admin-kicker text-orange-700/90 dark:text-orange-300/90">Panelze yönetim</p>
                    <h1 class="admin-hero__title mt-1">
                        Merhaba, {{ Str::limit(auth()->user()->name, 32) }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-400">
                        Lisans satışı, site içeriği, topluluk ve panel sürümleri tek panelden yönetilir. Aşağıda alanlara göre özet ve son hareketler var.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ route('landing.home') }}" target="_blank" rel="noopener noreferrer" class="admin-btn-outline text-sm">
                        Siteyi aç
                    </a>
                    <a href="{{ route('admin.appearance.index') }}" class="admin-btn-outline text-sm">
                        Görünüm
                    </a>
                    @if ($has_community && $pendingTotal > 0)
                        <a href="{{ route('admin.community.moderation.index') }}" class="admin-btn-primary">
                            Moderasyon ({{ $pendingTotal }})
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if ($has_community && $pendingTotal > 0)
            <x-admin.section variant="warning" title="Onay bekleyen içerik" :description="$k['community_pending_topics'].' konu ve '.$k['community_pending_posts'].' yanıt moderasyon kuyruğunda.'">
                <a href="{{ route('admin.community.moderation.index') }}" class="admin-btn-primary text-sm">Kuyruğu aç</a>
            </x-admin.section>
        @endif

        @if ($has_saas)
            <section class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="admin-kicker">Lisans & satış</p>
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">SaaS özeti</h2>
                    </div>
                    <a href="{{ route('admin.saas.dashboard') }}" class="admin-link-emerald text-sm">Lisans paneli →</a>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-admin.stat-card label="Müşteriler" :value="number_format($k['saas_customers'])" hint="kayıtlı müşteri" :href="route('admin.saas.customers.index')" accent="indigo" />
                    <x-admin.stat-card label="Aktif lisans" :value="number_format($k['saas_licenses_active'])" hint="doğrulanabilir lisans" :href="route('admin.saas.licenses.index')" accent="emerald" />
                    <x-admin.stat-card label="Planlar" :value="number_format($k['plans'])" hint="fiyat / paket" :href="route('admin.plans.index')" accent="amber" />
                    <x-admin.stat-card label="Panel sürümü" :value="number_format($k['panel_releases'])" hint="yayında" :href="route('admin.panel-releases.index')" accent="violet" />
                </div>
            </section>
        @endif

        <section class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="admin-kicker">Site & içerik</p>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">Yayınlanan içerik</h2>
                </div>
                <a href="{{ route('admin.appearance.index') }}" class="admin-link-emerald text-sm">Görünüm ayarları →</a>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-admin.stat-card label="Blog" :value="number_format($k['blog_published'])" :hint="$k['blog_drafts'].' taslak'" :href="route('admin.blog-posts.index')" accent="orange">
                    <x-slot:icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                    </x-slot:icon>
                </x-admin.stat-card>
                <x-admin.stat-card label="Dokümanlar" :value="number_format($k['docs_published'])" hint="yayınlanan sayfa" :href="route('admin.doc-pages.index')" accent="sky">
                    <x-slot:icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    </x-slot:icon>
                </x-admin.stat-card>
                <x-admin.stat-card label="Site sayfaları" :value="number_format($k['site_pages'])" :hint="'menü '.$k['nav_items'].' öğe'" accent="violet">
                    <x-slot:icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                    </x-slot:icon>
                    <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs">
                        <a href="{{ route('admin.site-pages.index') }}" class="admin-link-emerald">Sayfalar</a>
                        <a href="{{ route('admin.nav-menu.index') }}" class="admin-link">Menüler</a>
                    </div>
                </x-admin.stat-card>
                @unless ($has_saas)
                    <x-admin.stat-card label="Planlar" :value="number_format($k['plans'])" hint="fiyat / paket kaydı" :href="route('admin.plans.index')" accent="emerald" />
                @endunless
            </div>
        </section>

        @if ($has_community)
            <section class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="admin-kicker">Topluluk</p>
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">Forum özeti</h2>
                    </div>
                    <a href="{{ route('admin.community.topics.index') }}" class="admin-link-emerald text-sm">Tüm konular →</a>
                </div>
                <div class="admin-card grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="admin-inner">
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($k['community_topics']) }}</p>
                        <p class="text-xs text-slate-500">yayında konu</p>
                    </div>
                    <div class="admin-inner">
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($k['community_posts']) }}</p>
                        <p class="text-xs text-slate-500">yanıt</p>
                    </div>
                    <div class="admin-inner">
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($k['community_members']) }}</p>
                        <p class="text-xs text-slate-500">aktif üye</p>
                    </div>
                    <div class="admin-inner">
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($k['community_views_sum']) }}</p>
                        <p class="text-xs text-slate-500">görüntülenme</p>
                    </div>
                </div>
            </section>
        @endif

        @if (($has_community && count($community_series) > 0) || count($blog_series) > 0)
            <div class="grid gap-6 lg:grid-cols-2">
                @if ($has_community && count($community_series) > 0)
                    <div class="admin-card">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Yeni konular (14 gün)</h2>
                            <span class="text-xs text-slate-500">günlük</span>
                        </div>
                        <div class="mt-6 flex h-44 items-end justify-between gap-1 sm:gap-1.5">
                            @foreach ($community_series as $bar)
                                <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-1" title="{{ $bar['count'] }} konu">
                                    <span class="text-[10px] font-medium tabular-nums text-slate-500">{{ $bar['count'] > 0 ? $bar['count'] : '' }}</span>
                                    <div class="flex h-28 w-full items-end justify-center">
                                        <div class="w-[72%] max-w-9 rounded-t-md bg-gradient-to-t from-orange-600 to-orange-400/90" style="height: {{ $bar['height_px'] }}px; min-height: 4px"></div>
                                    </div>
                                    <span class="truncate text-[10px] text-slate-400">{{ $bar['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if (count($blog_series) > 0)
                    <div class="admin-card">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Blog — yeni yazı (14 gün)</h2>
                            <span class="text-xs text-slate-500">oluşturulma</span>
                        </div>
                        <div class="mt-6 flex h-44 items-end justify-between gap-1 sm:gap-1.5">
                            @foreach ($blog_series as $bar)
                                <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-1" title="{{ $bar['count'] }} yazı">
                                    <span class="text-[10px] font-medium tabular-nums text-slate-500">{{ $bar['count'] > 0 ? $bar['count'] : '' }}</span>
                                    <div class="flex h-28 w-full items-end justify-center">
                                        <div class="w-[72%] max-w-9 rounded-t-md bg-gradient-to-t from-sky-600 to-sky-400/90" style="height: {{ $bar['height_px'] }}px; min-height: 4px"></div>
                                    </div>
                                    <span class="truncate text-[10px] text-slate-400">{{ $bar['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="admin-card xl:col-span-1">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Hızlı erişim</h2>
                <p class="mt-1 text-xs text-slate-500">Sık kullanılan yönetim ekranları</p>
                <ul class="mt-4 space-y-1">
                    <li><a href="{{ route('admin.appearance.index') }}" class="admin-quick-link"><span>Görünüm (site, tema, ana sayfa)</span><span class="text-slate-400">→</span></a></li>
                    <li><a href="{{ route('admin.billing-settings.edit') }}" class="admin-quick-link"><span>Ödeme (PayTR / Stripe)</span><span class="text-slate-400">→</span></a></li>
                    <li><a href="{{ route('admin.integrations-settings.edit') }}" class="admin-quick-link"><span>Panel entegrasyonları</span><span class="text-slate-400">→</span></a></li>
                    <li><a href="{{ route('admin.locale-settings.edit') }}" class="admin-quick-link"><span>Dil ayarları</span><span class="text-slate-400">→</span></a></li>
                    <li><a href="{{ route('admin.system.logs.index') }}" class="admin-quick-link"><span>Sistem logları</span><span class="text-slate-400">→</span></a></li>
                    @if ($has_saas)
                        <li><a href="{{ route('admin.saas.licenses.index') }}" class="admin-quick-link"><span>Lisanslar</span><span class="text-slate-400">→</span></a></li>
                    @endif
                    @if ($has_community)
                        <li><a href="{{ route('admin.community.settings.edit') }}" class="admin-quick-link"><span>Topluluk SEO</span><span class="text-slate-400">→</span></a></li>
                    @endif
                </ul>
            </div>

            @if ($has_community && $recent_topics->isNotEmpty())
                <div class="admin-card overflow-hidden p-0 xl:col-span-1">
                    <div class="border-b border-slate-200/80 px-5 py-4 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Son konular</h2>
                        <p class="text-xs text-slate-500">Son aktiviteye göre</p>
                    </div>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach ($recent_topics as $topic)
                            <li>
                                <a href="{{ route('admin.community.topics.edit', $topic) }}" class="flex flex-col gap-0.5 px-5 py-3 text-sm hover:bg-slate-50 dark:hover:bg-slate-900/40">
                                    <span class="font-medium text-slate-900 dark:text-slate-100">{{ Str::limit($topic->title, 52) }}</span>
                                    <span class="text-xs text-slate-500">{{ $topic->category?->name ?? '—' }} · {{ $topic->last_activity_at?->diffForHumans() ?? '—' }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($recent_blog->isNotEmpty())
                <div class="admin-card overflow-hidden p-0 xl:col-span-1">
                    <div class="border-b border-slate-200/80 px-5 py-4 dark:border-slate-800">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Son blog düzenlemeleri</h2>
                        <p class="text-xs text-slate-500">Güncellenme tarihi</p>
                    </div>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach ($recent_blog as $post)
                            <li>
                                <a href="{{ route('admin.blog-posts.edit', $post) }}" class="flex flex-col gap-0.5 px-5 py-3 text-sm hover:bg-slate-50 dark:hover:bg-slate-900/40">
                                    <span class="font-medium text-slate-900 dark:text-slate-100">{{ Str::limit($post->title, 52) }}</span>
                                    <span class="text-xs text-slate-500">{{ strtoupper($post->locale ?? '') }} · {{ $post->updated_at?->diffForHumans() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-admin.layout>
