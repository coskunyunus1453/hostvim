@php
    $activeTab = $activeTab ?? request('tab', 'site');
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

        <div class="admin-form-panel !shadow-none space-y-4">
            <nav class="flex flex-wrap gap-2" aria-label="Görünüm sekmeleri">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('admin.appearance.index', ['tab' => $key]) }}"
                       class="admin-btn-outline px-4 py-2 text-xs {{ $activeTab === $key ? '!border-orange-500 !text-orange-700 dark:!text-orange-200' : '' }}"
                       @if ($activeTab === $key) aria-current="page" @endif>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            @if ($activeTab === 'site')
                @include('admin.site-settings.edit', ['embedded' => true])
            @elseif ($activeTab === 'theme')
                @include('admin.theme-settings.edit', ['embedded' => true])
            @elseif ($activeTab === 'home')
                @include('admin.public-home-content.edit', ['embedded' => true])
            @elseif ($activeTab === 'install')
                @include('admin.install-settings.edit', [
                    'embedded' => true,
                    'installSettings' => $installSettings ?? \App\Services\InstallGuide::settings(),
                ])
            @endif
        </div>
    </div>
</x-admin.layout>
