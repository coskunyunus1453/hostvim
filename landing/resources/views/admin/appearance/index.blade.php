<x-admin.layout title="Görünüm">
    <div class="mx-auto max-w-6xl space-y-6" x-data="{ tab: '{{ $activeTab ?? 'site' }}' }">
        <div>
            <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Görünüm</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Site ayarları, tema, ana sayfa içeriği ve kurulum komutlarını tek ekranda sekmelerle düzenleyin.
            </p>
        </div>

        <div class="admin-form-panel !shadow-none space-y-4">
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        class="admin-btn-outline px-4 py-2 text-xs"
                        :class="tab === 'site' ? '!border-orange-500 !text-orange-700 dark:!text-orange-200' : ''"
                        @click="tab = 'site'">
                    Site ayarları
                </button>
                <button type="button"
                        class="admin-btn-outline px-4 py-2 text-xs"
                        :class="tab === 'theme' ? '!border-orange-500 !text-orange-700 dark:!text-orange-200' : ''"
                        @click="tab = 'theme'">
                    Tema ayarları
                </button>
                <button type="button"
                        class="admin-btn-outline px-4 py-2 text-xs"
                        :class="tab === 'home' ? '!border-orange-500 !text-orange-700 dark:!text-orange-200' : ''"
                        @click="tab = 'home'">
                    Ana sayfa içeriği
                </button>
                <button type="button"
                        class="admin-btn-outline px-4 py-2 text-xs"
                        :class="tab === 'install' ? '!border-orange-500 !text-orange-700 dark:!text-orange-200' : ''"
                        @click="tab = 'install'">
                    Kurulum komutları
                </button>
            </div>

            <div x-show="tab === 'site'" x-cloak>
                @include('admin.site-settings.edit', ['embedded' => true])
            </div>

            <div x-show="tab === 'theme'" x-cloak>
                @include('admin.theme-settings.edit', ['embedded' => true])
            </div>

            <div x-show="tab === 'home'" x-cloak>
                @include('admin.public-home-content.edit', ['embedded' => true])
            </div>

            <div x-show="tab === 'install'" x-cloak>
                @include('admin.install-settings.edit', ['embedded' => true, 'installSettings' => $installSettings ?? \App\Services\InstallGuide::settings()])
            </div>
        </div>
    </div>
</x-admin.layout>