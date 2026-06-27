@php
    use App\Filament\Pages\AccountingReportsPage;
@endphp

@include('filament.pages.partials.accounting-data-table', [
    'headers' => ['Ürün / Hizmet', 'Adet', 'Gelir', 'Maliyet', 'Kâr', 'Marj'],
    'rows' => collect($rows)->map(fn ($row) => [
        '<div class="font-medium">'.e($row['label']).'</div><div class="text-xs text-gray-500 dark:text-gray-400">'.e($row['item_type']).'</div>',
        (string) $row['quantity'],
        AccountingReportsPage::money($row['revenue']),
        AccountingReportsPage::money($row['cogs']),
        '<span class="'.($row['profit'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400').'">'.AccountingReportsPage::money($row['profit']).'</span>',
        AccountingReportsPage::percent($row['margin']),
    ])->all(),
    'empty' => 'Veri yok.',
])
