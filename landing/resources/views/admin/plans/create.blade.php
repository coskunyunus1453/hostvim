<x-admin.layout title="Yeni plan">
    <form method="POST" action="{{ route('admin.plans.store') }}" class="mx-auto max-w-2xl space-y-5">
        @csrf

        <div>
            <label for="name" class="admin-label">Ad</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                   class="admin-field mt-1" />
            @error('name')
                <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="slug" class="admin-label">Slug</label>
            <input id="slug" name="slug" type="text" value="{{ old('slug') }}" required
                   class="admin-field mt-1" />
            @error('slug')
                <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="subtitle" class="admin-label">Alt başlık</label>
            <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle') }}"
                   class="admin-field mt-1" />
            @error('subtitle')
                <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="price_label" class="admin-label">Fiyat etiketi</label>
                <input id="price_label" name="price_label" type="text" value="{{ old('price_label') }}" required placeholder="₺0 /ay"
                       class="admin-field mt-1" />
                @error('price_label')
                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="price_note" class="admin-label">Fiyat notu</label>
                <input id="price_note" name="price_note" type="text" value="{{ old('price_note') }}" placeholder="sunucu başına"
                       class="admin-field mt-1" />
                @error('price_note')
                    <p class="mt-1 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="features_raw" class="admin-label">Özellikler (satır satır)</label>
            <div class="mt-1 mb-2 flex flex-wrap gap-2">
                <button type="button" class="admin-btn-outline px-3 py-1 text-xs" data-hv-plan-fill="community">Community listesini yükle</button>
                <button type="button" class="admin-btn-outline px-3 py-1 text-xs" data-hv-plan-fill="pro">Pro modül listesini yükle</button>
            </div>
            <textarea id="features_raw" name="features_raw" rows="10"
                      class="admin-field font-mono"
                      data-hv-plan-community="{{ $catalog_community ?? '' }}"
                      data-hv-plan-pro="{{ $catalog_pro ?? '' }}">{{ $features_raw }}</textarea>
            <p class="mt-1 text-xs text-slate-500">Panelze v0.1 gerçek özellik listesinden tek tıkla doldurabilirsiniz.</p>
        </div>

        <div class="flex flex-wrap items-center gap-6">
            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured')) class="admin-checkbox" />
                Öne çıkan
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="admin-checkbox" />
                Aktif (sitede göster)
            </label>
            <div class="flex items-center gap-2">
                <label for="sort_order" class="text-xs text-slate-600 dark:text-slate-400">Sıra</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', 0) }}"
                       class="admin-field w-24 px-3 py-1.5" />
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="admin-btn-emerald-lg">
                Kaydet
            </button>
            <a href="{{ route('admin.plans.index') }}" class="admin-btn-outline">
                İptal
            </a>
        </div>
    </form>
    <script>
        (function () {
            var ta = document.getElementById('features_raw');
            if (!ta) return;
            document.querySelectorAll('[data-hv-plan-fill]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var key = btn.getAttribute('data-hv-plan-fill');
                    var val = key === 'pro' ? ta.getAttribute('data-hv-plan-pro') : ta.getAttribute('data-hv-plan-community');
                    if (val) ta.value = val;
                });
            });
        })();
    </script>
</x-admin.layout>
