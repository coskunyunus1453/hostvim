@php
    use App\Filament\Pages\AccountingReportsPage;
@endphp

@include('filament.pages.partials.accounting-data-table', [
    'headers' => ['Ürün', 'Adet', 'Gelir', 'Maliyet', 'Kâr', 'Marj'],
    'align' => ['start', 'end', 'end', 'end', 'end', 'end'],
    'compact' => true,
    'maxHeight' => '14rem',
    'rows' => collect($rows)->map(fn ($row) => [
        '<div class="fi-acct-label">'.e($row['label']).'</div>'
            .'<div class="fi-acct-sub">'.e($row['item_type']).'</div>',
        '<span class="fi-acct-pill">'.e((string) $row['quantity']).'</span>',
        '<span class="fi-acct-num">'.AccountingReportsPage::money($row['revenue']).'</span>',
        '<span class="fi-acct-num">'.AccountingReportsPage::money($row['cogs']).'</span>',
        '<span class="fi-acct-num '.($row['profit'] >= 0 ? 'fi-acct-num--up' : 'fi-acct-num--down').'">'
            .AccountingReportsPage::money($row['profit']).'</span>',
        '<span class="fi-acct-pill fi-acct-pill--muted">'.AccountingReportsPage::percent($row['margin']).'</span>',
    ])->all(),
    'empty' => 'Veri yok.',
])
