<x-admin.layout title="Lisans özeti">
    <x-admin.toolbar description="Müşteri panelleri LICENSE_SERVER_URL ile bu siteye bağlanıp lisans doğrular. API uç noktaları ve kurulum komutları aşağıda.">
        <x-slot:actions>
            <a href="{{ route('admin.saas.licenses.create') }}" class="admin-btn-emerald">Yeni lisans</a>
            <a href="{{ route('admin.saas.customers.create') }}" class="admin-btn-outline text-sm">Yeni müşteri</a>
        </x-slot:actions>
    </x-admin.toolbar>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat-card label="Müşteriler" :value="number_format($customers)" :href="route('admin.saas.customers.index')" accent="indigo" />
        <x-admin.stat-card label="Aktif lisans" :value="number_format($licenses_active)" :href="route('admin.saas.licenses.index')" accent="emerald" />
        <x-admin.stat-card label="Ürün (tier)" :value="number_format($products)" :href="route('admin.saas.products.index')" accent="violet" />
        <x-admin.stat-card label="Modül tanımı" :value="number_format($modules)" :href="route('admin.saas.modules.index')" accent="orange" />
    </div>

    <x-admin.section title="Lisans API (müşteri paneli)" description='POST JSON {"key":"hv_..."}'>
        <code class="admin-key block break-all rounded-xl bg-slate-100 p-3 text-xs dark:bg-slate-950">{{ $api_endpoint }}</code>
        <p class="admin-muted mt-2 text-xs">
            Panel tarafı: <code class="admin-key">LICENSE_SERVER_URL</code> bu siteye işaret etmeli.
            Bearer: <code class="admin-key">PANELZE_LICENSE_API_SECRET</code> = panel <code class="admin-key">LICENSE_SERVER_API_SECRET</code>
        </p>
    </x-admin.section>

    <x-admin.section title="Panel güncelleme API" description="GET ?current=1.0.0&amp;profile=customer&amp;channel=stable">
        <code class="admin-key block break-all rounded-xl bg-slate-100 p-3 text-xs dark:bg-slate-950">{{ $updates_endpoint }}</code>
        <p class="admin-muted mt-2 text-xs">
            Sürümleri <a href="{{ route('admin.panel-releases.index') }}" class="admin-link">Panel sürümleri</a> ekranından yayınlayın.
            Bearer: <code class="admin-key">PANELZE_PANEL_UPDATES_API_SECRET</code>
        </p>
    </x-admin.section>

    <x-admin.section title="Müşteri kurulum komutları" description="Ana sayfa ve /setup sayfasında gösterilen komutlar.">
        <code class="admin-key block break-all rounded-xl bg-slate-100 p-3 text-xs dark:bg-slate-950">{{ \App\Services\InstallGuide::oneLiner() }}</code>
        <p class="admin-muted mt-2 text-xs">
            <a href="{{ route('admin.appearance.index', ['tab' => 'install']) }}" class="admin-link">Görünüm → Kurulum komutları</a> sekmesinden düzenleyin.
        </p>
    </x-admin.section>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.billing-settings.edit') }}" class="admin-btn-outline text-sm">Ödeme ayarları</a>
        <a href="{{ route('admin.integrations-settings.edit') }}" class="admin-btn-outline text-sm">Entegrasyonlar</a>
        <a href="{{ route('admin.panel-releases.index') }}" class="admin-btn-outline text-sm">Panel sürümleri</a>
    </div>
</x-admin.layout>
