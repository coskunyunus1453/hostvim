@php
    $headers = $headers ?? [];
    $rows = $rows ?? [];
    $empty = $empty ?? 'Veri yok.';
@endphp

<div class="fi-ta-ctn overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="overflow-x-auto">
        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
            <thead class="divide-y divide-gray-200 dark:divide-white/5">
                <tr class="bg-gray-50 dark:bg-white/5">
                    @foreach($headers as $header)
                        <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                @forelse($rows as $row)
                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                        @foreach($row as $cell)
                            <td class="px-4 py-3 text-sm text-gray-950 dark:text-white">{!! $cell !!}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ max(count($headers), 1) }}" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ $empty }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
