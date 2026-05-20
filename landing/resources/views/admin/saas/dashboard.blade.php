<x-admin.layout title="Panelze SaaS — özet">
    <p class="admin-muted mb-6">Müşteri sunucularındaki panel <code class="rounded bg-slate-200 px-1 text-xs dark:bg-slate-800">LICENSE_SERVER_URL</code> ile bu siteye bağlanıp lisans doğrulayabilir.</p>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/90 bg-white/90 p-4 dark:border-slate-800 dark:bg-slate-900/60">
            <div class="text-xs text-slate-500">Müşteriler</div>
            <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $customers }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200/90 bg-white/90 p-4 dark:border-slate-800 dark:bg-slate-900/60">
            <div class="text-xs text-slate-500">Aktif lisans</div>
            <div class="text-2xl font-semibold text-emerald-700 dark:text-emerald-400">{{ $licenses_active }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200/90 bg-white/90 p-4 dark:border-slate-800 dark:bg-slate-900/60">
            <div class="text-xs text-slate-500">Ürün (tier)</div>
            <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $products }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200/90 bg-white/90 p-4 dark:border-slate-800 dark:bg-slate-900/60">
            <div class="text-xs text-slate-500">Modül tanımı</div>
            <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $modules }}</div>
        </div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200/90 bg-white/90 p-4 text-sm dark:border-slate-800 dark:bg-slate-900/60">
        <div class="font-medium text-slate-900 dark:text-slate-100">Lisans API (müşteri paneli)</div>
        <p class="mt-1 text-slate-600 dark:text-slate-400">POST JSON <code class="text-xs">{"key":"hv_..."}</code></p>
        <code class="mt-2 block break-all rounded-lg bg-slate-100 p-3 text-xs dark:bg-slate-950">{{ $api_endpoint }}</code>
        <p class="mt-2 text-xs text-slate-500">Panel tarafı: <code>LICENSE_SERVER_URL</code> bu siteye işaret etmeli. Bearer: landing <code>HOSTVIM_LICENSE_API_SECRET</code> = panel <code>LICENSE_SERVER_API_SECRET</code> (veya <code>HOSTVIM_LICENSE_API_SECRET</code>).</p>
    </div>

    <div class="mt-4 rounded-2xl border border-slate-200/90 bg-white/90 p-4 text-sm dark:border-slate-800 dark:bg-slate-900/60">
        <div class="font-medium text-slate-900 dark:text-slate-100">Panel güncelleme API</div>
        <p class="mt-1 text-slate-600 dark:text-slate-400">GET <code class="text-xs">?current=1.0.0&amp;profile=customer&amp;channel=stable</code></p>
        <code class="mt-2 block break-all rounded-lg bg-slate-100 p-3 text-xs dark:bg-slate-950">{{ $updates_endpoint }}</code>
        <p class="mt-2 text-xs text-slate-500">Sürümleri <a href="{{ route('admin.panel-releases.index') }}" class="text-sky-600 underline">Panel sürümleri</a> ekranından yayınlayın. Bearer: <code>HOSTVIM_PANEL_UPDATES_API_SECRET</code> (boşsa lisans secret’ı kullanılır).</p>
    </div>

    <div class="mt-4 flex flex-wrap gap-3 text-sm">
        <a href="{{ route('admin.saas.licenses.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Lisanslar</a>
        <a href="{{ route('admin.panel-releases.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Panel sürümleri</a>
    </div>
</x-admin.layout>
