@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\SaasProductModule> $moduleRegistry */
    /** @var array<string, bool> $enabledModules */
@endphp
<div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Pro modüller (panel)</h3>
    <p class="mt-1 text-xs text-slate-500">İşaretli modüller bu ürün / lisans için panelde açılır. Hub validate yanıtına yansır.</p>
    <div class="mt-3 grid gap-2 sm:grid-cols-2">
        @foreach ($moduleRegistry as $mod)
            <label class="flex items-start gap-2 rounded-lg border border-slate-100 px-3 py-2 text-sm dark:border-slate-800">
                <input type="checkbox" name="modules[{{ $mod->key }}]" value="1"
                    @checked(old("modules.{$mod->key}", $enabledModules[$mod->key] ?? false))>
                <span>
                    <span class="font-mono font-medium">{{ $mod->key }}</span>
                    <span class="block text-xs text-slate-500">{{ $mod->label }}</span>
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
