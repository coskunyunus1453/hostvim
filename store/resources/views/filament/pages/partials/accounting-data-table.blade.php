@php
    $headers = $headers ?? [];
    $rows = $rows ?? [];
    $align = $align ?? [];
    $empty = $empty ?? 'Veri yok.';
    $flush = $flush ?? true;

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
    'fi-accounting-table--flush -mx-6 -mb-6 border-t border-gray-200 dark:border-white/10' => $flush,
    'overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10' => ! $flush,
])>
    <div class="overflow-x-auto">
        <table class="fi-accounting-table__grid w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-white/5">
                    @foreach ($headers as $index => $header)
                        @php
                            $cellAlign = $align[$index] ?? $defaultAlign($index, count($headers));
                        @endphp
                        <th @class([
                            'fi-accounting-table__th px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400',
                            'text-start' => $cellAlign === 'start',
                            'text-end' => $cellAlign === 'end',
                            'text-center' => $cellAlign === 'center',
                        ])>
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @forelse ($rows as $row)
                    <tr class="bg-white transition hover:bg-gray-50/80 dark:bg-gray-900 dark:hover:bg-white/5">
                        @foreach ($row as $index => $cell)
                            @php
                                $cellAlign = $align[$index] ?? $defaultAlign($index, count($headers));
                            @endphp
                            <td @class([
                                'fi-accounting-table__td px-4 py-3 align-top text-gray-950 dark:text-white',
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
                        <td colspan="{{ max(count($headers), 1) }}" class="px-4 py-10 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center gap-2 text-gray-500 dark:text-gray-400">
                                <x-filament::icon
                                    icon="heroicon-o-chart-bar"
                                    class="h-8 w-8 opacity-40"
                                />
                                <span class="text-sm">{{ $empty }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
