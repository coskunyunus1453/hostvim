@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\SaasProductModule> $moduleRegistry */
    /** @var array<string, bool> $enabledModules */
    use App\Support\PanelFeatureCatalog;
@endphp
<div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Pro modüller (Panelze v{{ PanelFeatureCatalog::PANEL_VERSION }})</h3>
            <p class="mt-1 text-xs text-slate-500">İşaretli modüller bu ürün/lisans için panelde açılır. Hub <code class="text-[10px]">/api/v1/license/validate</code> yanıtına yansır.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="admin-btn-outline px-3 py-1.5 text-xs" data-hv-saas-select-all="1">Tüm Pro modülleri seç</button>
            <button type="button" class="admin-btn-outline px-3 py-1.5 text-xs" data-hv-saas-select-none="1">Tümünü kaldır</button>
        </div>
    </div>
    <div class="mt-3 grid gap-2 sm:grid-cols-2" data-hv-saas-module-grid>
        @foreach ($moduleRegistry as $mod)
            <label class="flex items-start gap-2 rounded-lg border border-slate-100 px-3 py-2 text-sm dark:border-slate-800">
                <input type="checkbox" name="modules[{{ $mod->key }}]" value="1" class="mt-0.5"
                    data-hv-saas-module
                    @checked(old("modules.{$mod->key}", $enabledModules[$mod->key] ?? false))>
                <span>
                    <span class="font-mono text-xs font-medium text-orange-700 dark:text-orange-200">{{ $mod->key }}</span>
                    <span class="block text-sm font-medium text-slate-800 dark:text-slate-100">{{ $mod->label }}</span>
                    @if ($mod->description)
                        <span class="mt-0.5 block text-xs text-slate-500">{{ $mod->description }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
    <details class="mt-3 text-xs text-slate-500">
        <summary class="cursor-pointer">Gelişmiş: JSON ile düzenle</summary>
        <textarea name="default_modules_raw" rows="5" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs dark:border-slate-700 dark:bg-slate-900">{{ $default_modules_raw ?? '{}' }}</textarea>
        <p class="mt-1">Checkbox ve JSON birlikte gönderilirse checkbox önceliklidir.</p>
    </details>
</div>
<script>
    (function () {
        var grid = document.querySelector('[data-hv-saas-module-grid]');
        if (!grid) return;
        var boxes = grid.querySelectorAll('[data-hv-saas-module]');
        var allBtn = document.querySelector('[data-hv-saas-select-all]');
        var noneBtn = document.querySelector('[data-hv-saas-select-none]');
        if (allBtn) allBtn.addEventListener('click', function () { boxes.forEach(function (b) { b.checked = true; }); });
        if (noneBtn) noneBtn.addEventListener('click', function () { boxes.forEach(function (b) { b.checked = false; }); });
    })();
</script>
