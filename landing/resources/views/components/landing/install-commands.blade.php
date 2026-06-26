@props([
    'variant' => 'full',
])

@php
    $locale = app()->getLocale();
    $sections = $variant === 'compact'
        ? \App\Services\InstallGuide::homeSectionsForLocale($locale)
        : \App\Services\InstallGuide::sectionsForLocale($locale);
@endphp

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @if ($variant === 'compact')
        @foreach ($sections as $section)
            <div class="rounded-2xl border border-slate-200/90 bg-slate-50/80 p-4 font-mono text-sm text-slate-800 dark:border-slate-800 dark:bg-slate-950/80 dark:text-slate-200">
                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $section['label'] }}</div>
                <div class="overflow-x-auto rounded-xl border border-slate-200/90 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-900/80">
                    <code class="whitespace-pre-wrap break-all text-[13px]">{{ $section['command'] }}</code>
                </div>
                @if (! empty($section['note']))
                    <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ $section['note'] }}</p>
                @endif
            </div>
        @endforeach
        <p class="text-sm text-slate-600 dark:text-slate-400">
            <a href="{{ route('site.setup') }}" class="hv-link font-medium">{{ $locale === 'tr' ? 'Güncelleme ve ayrıntılı kurulum →' : 'Updates and full install guide →' }}</a>
        </p>
    @else
        <div class="rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
            @if ($locale === 'tr')
                <strong>Önemli:</strong> Komutları yalnızca güvendiğiniz Debian/Ubuntu VPS üzerinde root/sudo ile çalıştırın.
            @else
                <strong>Important:</strong> Run these only on a trusted Debian/Ubuntu VPS as root/sudo.
            @endif
        </div>

        @foreach ($sections as $section)
            <div class="rounded-xl border border-slate-200/90 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $section['label'] }}</h3>
                @if (! empty($section['note']))
                    <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ $section['note'] }}</p>
                @endif
                <pre class="mt-3 overflow-x-auto rounded-lg border border-slate-200 bg-slate-950 px-3 py-3 text-xs leading-relaxed text-emerald-300 dark:border-slate-700"><code>{{ $section['command'] }}</code></pre>
            </div>
        @endforeach
    @endif
</div>
