@php
    $headers = $headers ?? [];
    $rows = $rows ?? [];
    $align = $align ?? [];
    $empty = $empty ?? 'Veri yok.';
    $flush = $flush ?? true;
    $compact = $compact ?? false;
    $maxHeight = $maxHeight ?? null;

    $defaultAlign = static function (int $index, int $total): string {
        if ($index === 0) {
            return 'start';
        }

        if ($index === $total - 1 && $total > 3) {
            return 'end';
        }

        return 'end';
    };
@endphp

<div @class([
    'fi-accounting-table',
    'fi-accounting-table--flush -mx-4 -mb-4 border-t border-gray-200/80 dark:border-white/10' => $flush,
    'overflow-hidden rounded-lg bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10' => ! $flush,
    'fi-accounting-table--compact' => $compact,
]) @if($maxHeight) style="max-height: {{ $maxHeight }}; overflow-y: auto;" @endif>
    <div class="overflow-x-auto">
        <table class="fi-accounting-table__grid w-full text-sm">
            <thead class="sticky top-0 z-[1]">
                <tr class="bg-gray-50/95 backdrop-blur-sm dark:bg-gray-950/90">
                    @foreach ($headers as $index => $header)
                        @php
                            $cellAlign = $align[$index] ?? $defaultAlign($index, count($headers));
                        @endphp
                        <th @class([
                            'fi-accounting-table__th text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400',
                            'px-3 py-2' => $compact,
                            'px-4 py-3' => ! $compact,
                            'text-start' => $cellAlign === 'start',
                            'text-end' => $cellAlign === 'end',
                            'text-center' => $cellAlign === 'center',
                        ])>
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse ($rows as $row)
                    <tr class="bg-white transition hover:bg-gray-50/70 dark:bg-gray-900 dark:hover:bg-white/[0.03]">
                        @foreach ($row as $index => $cell)
                            @php
                                $cellAlign = $align[$index] ?? $defaultAlign($index, count($headers));
                            @endphp
                            <td @class([
                                'fi-accounting-table__td align-middle text-gray-950 dark:text-white',
                                'px-3 py-2 text-xs' => $compact,
                                'px-4 py-3' => ! $compact,
                                'text-start' => $cellAlign === 'start',
                                'text-end tabular-nums' => $cellAlign === 'end',
                                'text-center tabular-nums' => $cellAlign === 'center',
                            ])>
                                {!! $cell !!}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ max(count($headers), 1) }}" @class(['text-center text-gray-500 dark:text-gray-400', 'px-3 py-6 text-xs' => $compact, 'px-4 py-8 text-sm' => ! $compact])>
                            {{ $empty }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
