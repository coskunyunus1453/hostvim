@props([
    'compact' => false,
])

<div @class([
    'flex items-center gap-2',
    'h-14 border-b border-slate-200/90 px-3 dark:border-slate-800/80' => ! $compact,
    'min-w-0' => $compact,
])>
    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/15 ring-1 ring-orange-500/35 dark:bg-orange-500/10 dark:ring-orange-400/40">
        <span class="text-sm font-bold text-orange-600 dark:text-orange-400">H</span>
    </div>
    <div class="min-w-0 leading-tight">
        <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">Panelze</div>
        @unless ($compact)
            <div class="text-[10px] text-slate-500">Yönetim</div>
        @endunless
    </div>
</div>
