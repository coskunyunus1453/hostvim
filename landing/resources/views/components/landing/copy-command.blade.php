@props([
    'command',
    'label' => null,
    'note' => null,
])
@php
    $multiline = str_contains($command, "\n");
    $copyLabel = app()->getLocale() === 'tr' ? 'Kopyala' : 'Copy';
    $copiedLabel = app()->getLocale() === 'tr' ? 'Kopyalandı' : 'Copied';
@endphp

<div x-data="{ copied: false }" {{ $attributes }}>
    @if ($label)
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</div>
    @endif

    @if ($multiline)
        <div class="overflow-hidden rounded-xl border border-slate-700 bg-slate-950 shadow-sm">
            <div class="flex items-center justify-between gap-2 border-b border-white/10 px-4 py-2">
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-400/80"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400/80"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400/80"></span>
                </div>
                <button type="button"
                        @click="navigator.clipboard.writeText(@js($command)); copied = true; setTimeout(() => copied = false, 1500)"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-1.5 text-xs font-semibold text-slate-100 transition hover:bg-white/20">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>
                    <span x-show="!copied">{{ $copyLabel }}</span>
                    <span x-show="copied" x-cloak>{{ $copiedLabel }}</span>
                </button>
            </div>
            <pre class="overflow-x-auto px-4 py-3.5 font-mono text-[13px] leading-relaxed text-emerald-300"><code>{{ $command }}</code></pre>
        </div>
    @else
        <div class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950 p-2 pl-4 shadow-sm">
            <span class="select-none font-mono text-sm text-emerald-400">$</span>
            <code class="min-w-0 flex-1 overflow-x-auto whitespace-nowrap font-mono text-[13px] text-slate-100">{{ $command }}</code>
            <button type="button"
                    @click="navigator.clipboard.writeText(@js($command)); copied = true; setTimeout(() => copied = false, 1500)"
                    class="inline-flex flex-none items-center gap-1.5 rounded-lg bg-white/10 px-3 py-2 text-xs font-semibold text-slate-100 transition hover:bg-white/20">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>
                <span x-show="!copied">{{ $copyLabel }}</span>
                <span x-show="copied" x-cloak>{{ $copiedLabel }}</span>
            </button>
        </div>
    @endif

    @if ($note)
        <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ $note }}</p>
    @endif
</div>
