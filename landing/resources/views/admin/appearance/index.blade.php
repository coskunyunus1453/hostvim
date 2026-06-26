@php
    $tabKeys = ['site', 'theme', 'home', 'install'];
    $activeTab = $activeTab ?? request('tab', 'site');
    if (! in_array($activeTab, $tabKeys, true)) {
        $activeTab = 'site';
    }
    $tabs = [
        'site' => 'Site ayarları',
        'theme' => 'Tema ayarları',
        'home' => 'Ana sayfa içeriği',
        'install' => 'Kurulum komutları',
    ];
@endphp
<x-admin.layout title="Görünüm">
    <div class="mx-auto max-w-6xl space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Görünüm</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Site ayarları, tema, ana sayfa içeriği ve kurulum komutlarını tek ekranda sekmelerle düzenleyin.
            </p>
        </div>

        <div class="admin-form-panel hv-appearance-tabs !shadow-none space-y-4">
            @foreach ($tabKeys as $key)
                <input type="radio"
                       name="hv-appearance-tab"
                       id="hv-appearance-tab-{{ $key }}"
                       class="hv-tab-input"
                       @checked($activeTab === $key)>
            @endforeach

            <nav class="hv-tab-nav flex flex-wrap gap-2" aria-label="Görünüm sekmeleri">
                @foreach ($tabs as $key => $label)
                    <label for="hv-appearance-tab-{{ $key }}"
                           class="admin-btn-outline hv-tab-label cursor-pointer px-4 py-2 text-xs">
                        {{ $label }}
                    </label>
                @endforeach
            </nav>

            <div class="hv-tab-panel hv-tab-panel-site">
                @include('admin.site-settings.edit', [
                    'embedded' => true,
                    'siteName' => $siteName,
                    'siteTagline' => $siteTagline,
                    'logoUrl' => $logoUrl,
                    'faviconUrl' => $faviconUrl,
                    'contactEmail' => $contactEmail,
                    'socialTwitter' => $socialTwitter,
                    'socialGithub' => $socialGithub,
                    'socialLinkedin' => $socialLinkedin,
                    'analyticsGa4' => $analyticsGa4,
                    'analyticsHeadCode' => $analyticsHeadCode,
                    'analyticsBodyCode' => $analyticsBodyCode,
                    'footerExtraNote' => $footerExtraNote,
                    'logoMaxHeightPx' => $logoMaxHeightPx,
                    'logoMaxWidthPx' => $logoMaxWidthPx,
                    'logoFooterMaxHeightPx' => $logoFooterMaxHeightPx,
                    'logoFooterMaxWidthPx' => $logoFooterMaxWidthPx,
                    'headerBrandMode' => $headerBrandMode,
                ])
            </div>

            <div class="hv-tab-panel hv-tab-panel-theme">
                @include('admin.theme-settings.edit', [
                    'embedded' => true,
                    'activeTheme' => $activeTheme,
                    'graphicMotif' => $graphicMotif,
                    'primaryHex' => $primaryHex,
                    'themes' => $themes,
                    'motifs' => $motifs,
                    'featureIcons' => $featureIcons,
                    'neonTop' => $neonTop,
                    'neonStackSection' => $neonStackSection,
                    'neonStackItems' => $neonStackItems,
                    'neonGridSection' => $neonGridSection,
                    'neonGridItems' => $neonGridItems,
                ])
            </div>

            <div class="hv-tab-panel hv-tab-panel-home">
                @include('admin.public-home-content.edit', [
                    'embedded' => true,
                    'groups' => $groups,
                    'allowedKeys' => $allowedKeys,
                    'overrides' => $overrides,
                    'featureCards' => $featureCards,
                    'heroImageUrl' => $heroImageUrl,
                    'heroImageAlt' => $heroImageAlt,
                    'heroImageCaption' => $heroImageCaption,
                    'icons' => $icons,
                ])
            </div>

            <div class="hv-tab-panel hv-tab-panel-install">
                @include('admin.install-settings.edit', [
                    'embedded' => true,
                    'installSettings' => $installSettings,
                ])
            </div>
        </div>
    </div>
</x-admin.layout>
