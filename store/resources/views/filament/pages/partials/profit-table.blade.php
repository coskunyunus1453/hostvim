@php
    use App\Filament\Pages\AccountingReportsPage;
@endphp

@include('filament.pages.partials.accounting-data-table', [
    'headers' => ['Ürün / Hizmet', 'Adet', 'Gelir', 'Maliyet', 'Kâr', 'Marj'],
    'align' => ['start', 'end', 'end', 'end', 'end', 'end'],
    'rows' => collect($rows)->map(fn ($row) => [
        '<div class="fi-accounting-cell-label font-medium text-gray-950 dark:text-white">'.e($row['label']).'</div>'
            .'<div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">'.e($row['item_type']).'</div>',
        '<span class="fi-accounting-badge">'.e((string) $row['quantity']).'</span>',
        '<span class="text-gray-700 dark:text-gray-300">'.AccountingReportsPage::money($row['revenue']).'</span>',
        '<span class="text-gray-700 dark:text-gray-300">'.AccountingReportsPage::money($row['cogs']).'</span>',
        '<span class="fi-accounting-money '.($row['profit'] >= 0 ? 'fi-accounting-money--profit' : 'fi-accounting-money--loss').'">'
            .AccountingReportsPage::money($row['profit']).'</span>',
        '<span class="fi-accounting-badge fi-accounting-badge--muted">'.AccountingReportsPage::percent($row['margin']).'</span>',
    ])->all(),
    'empty' => 'Veri yok.',
])
