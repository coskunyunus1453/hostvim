@php
    $uiPaths = old('ui_paths_raw', isset($module) ? implode("\n", $module->ui_paths ?? []) : '');
    $apiPrefixes = old('api_route_prefixes_raw', isset($module) ? implode("\n", $module->api_route_prefixes ?? []) : '');
@endphp
<div>
    <label class="block text-sm font-medium">Panel UI yolları</label>
    <p class="text-xs text-slate-500">Her satır bir path (ör. /curious). Boş bırakılırsa varsayılan eşleme kullanılır.</p>
    <textarea name="ui_paths_raw" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs dark:border-slate-700 dark:bg-slate-900">{{ $uiPaths }}</textarea>
</div>
<div>
    <label class="block text-sm font-medium">Panel API önekleri</label>
    <p class="text-xs text-slate-500">Virgül veya satır ile (ör. curious, ai-assistant)</p>
    <textarea name="api_route_prefixes_raw" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs dark:border-slate-700 dark:bg-slate-900">{{ $apiPrefixes }}</textarea>
</div>
