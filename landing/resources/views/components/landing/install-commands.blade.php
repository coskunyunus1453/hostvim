@props([
    'variant' => 'full',
])

@php
    $locale = app()->getLocale();
    $sections = \App\Services\InstallGuide::sectionsForLocale($locale);
    $compact = $variant === 'compact';
@endphp

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @if ($compact)
        @php $primary = $sections[0]; @endphp
        <div class="rounded-2xl border border-slate-200/90 bg-slate-50/80 p-5 font-mono text-sm text-slate-800 dark:border-slate-800 dark:bg-slate-950/80 dark:text-slate-200">
            <div class="mb-2 flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-500">
                <span>{{ landing_p('home.docs_install_caption') }}</span>
                <span class="rounded-full bg-white px-2 py-0.5 text-[10px] dark:bg-slate-900">{{ landing_p('home.docs_install_prompt_user') }}</span>
            </div>
            <div class="overflow-x-auto rounded-xl border border-slate-200/90 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-900/80">
                <code class="whitespace-pre-wrap break-all text-[13px]">{{ $primary['command'] }}</code>
            </div>
            @if (! empty($primary['note']))
                <p class="mt-3 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ $primary['note'] }}</p>
            @endif
        </div>
        <details class="rounded-xl border border-slate-200/90 bg-white/80 p-4 text-sm dark:border-slate-800 dark:bg-slate-900/50">
            <summary class="cursor-pointer font-medium text-slate-800 dark:text-slate-200">
                {{ $locale === 'tr' ? 'Diğer kurulum komutları (Community, Pro, elle kurulum…)' : 'More install commands (Community, Pro, manual…)' }}
            </summary>
            <div class="mt-4 space-y-4">
                @foreach (array_slice($sections, 1, 4) as $section)
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $section['label'] }}</div>
                        <pre class="mt-1 overflow-x-auto rounded-lg border border-slate-200 bg-slate-950 px-3 py-2 text-xs text-emerald-300 dark:border-slate-700"><code>{{ $section['command'] }}</code></pre>
                    </div>
                @endforeach
                <a href="{{ route('site.setup') }}" class="inline-flex text-sm font-medium text-[rgb(var(--hv-brand-600)/1)] hover:underline dark:text-[rgb(var(--hv-brand-400)/1)]">
                    {{ $locale === 'tr' ? 'Tüm komutlar → Kurulum rehberi' : 'Full command reference → Installation guide' }}
                </a>
            </div>
        </details>
    @else
        <div class="rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100">
            @if ($locale === 'tr')
                <strong>Önemli:</strong> Komutları yalnızca güvendiğiniz Debian/Ubuntu VPS üzerinde root/sudo ile çalıştırın. Satır başına <code class="rounded bg-amber-100 px-1 dark:bg-amber-900/50">*</code> veya madde işareti eklemeyin — kabuk komutu bozar.
            @else
                <strong>Important:</strong> Run these only on a trusted Debian/Ubuntu VPS as root/sudo. Do not prefix lines with <code class="rounded bg-amber-100 px-1 dark:bg-amber-900/50">*</code> or bullets when pasting into a shell.
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
