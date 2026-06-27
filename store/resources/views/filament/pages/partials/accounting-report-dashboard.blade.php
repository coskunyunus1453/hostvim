@php
    use App\Filament\Pages\AccountingReportsPage;

    $summary = $report['summary'];
@endphp

<div class="fi-accounting-report space-y-6">
    @if(($summary['unknown_cost_lines'] ?? 0) > 0)
        <div class="fi-banner flex gap-3 rounded-xl bg-warning-50 p-4 text-sm text-warning-800 ring-1 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-200 dark:ring-warning-400/20">
            <div>
                {{ $summary['unknown_cost_lines'] }} sipariş satırında alış maliyeti girilmemiş.
                Ürün formlarından alış fiyatlarını tanımlayın veya domain varsayılan maliyetini ayarlayın.
            </div>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Gelir (satış)', $summary['revenue'], 'text-success-600 dark:text-success-400'],
            ['Maliyet (COGS)', $summary['cogs'], 'text-warning-600 dark:text-warning-400'],
            ['Brüt kâr', $summary['gross_profit'], 'text-primary-600 dark:text-primary-400'],
            ['Net kâr', $summary['net_profit'], ($summary['net_profit'] ?? 0) >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'],
        ] as [$label, $value, $valueClass])
            <div class="fi-accounting-stat rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="mt-1 text-2xl font-semibold tracking-tight {{ $valueClass }}">
                    {{ AccountingReportsPage::money((float) $value) }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach([
            ['Brüt marj', AccountingReportsPage::percent($summary['gross_margin']), 'text-gray-950 dark:text-white'],
            ['İşletme giderleri', AccountingReportsPage::money((float) $summary['expenses']), 'text-warning-600 dark:text-warning-400'],
            ['Net marj', AccountingReportsPage::percent($summary['net_margin']), 'text-primary-600 dark:text-primary-400'],
        ] as [$label, $value, $valueClass])
            <div class="fi-accounting-stat rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="mt-1 text-xl font-semibold {{ $valueClass }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-filament::section heading="Hizmet tipine göre kâr">
            @include('filament.pages.partials.accounting-data-table', [
                'headers' => ['Tip', 'Gelir', 'Maliyet', 'Kâr', 'Marj'],
                'rows' => collect($report['byType'])->map(fn ($row) => [
                    '<span class="font-medium">'.e($row['label']).'</span>',
                    AccountingReportsPage::money($row['revenue']),
                    AccountingReportsPage::money($row['cogs']),
                    '<span class="'.($row['profit'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400').'">'.AccountingReportsPage::money($row['profit']).'</span>',
                    AccountingReportsPage::percent($row['margin']),
                ])->all(),
                'empty' => 'Bu dönemde veri yok.',
            ])
        </x-filament::section>

        <x-filament::section heading="Gider kategorileri">
            @include('filament.pages.partials.accounting-data-table', [
                'headers' => ['Kategori', 'Tutar', 'Adet'],
                'rows' => collect($report['expenses'])->map(fn ($row) => [
                    e($row['label']),
                    AccountingReportsPage::money($row['amount']),
                    (string) $row['count'],
                ])->all(),
                'empty' => 'Gider kaydı yok. Gider Kayıtları menüsünden ekleyin.',
            ])
        </x-filament::section>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-filament::section heading="En kârlı ürünler / hizmetler">
            @include('filament.pages.partials.profit-table', ['rows' => $report['topProfit']])
        </x-filament::section>

        <x-filament::section heading="En az kâr bırakanlar">
            @include('filament.pages.partials.profit-table', ['rows' => $report['lowProfit']])
        </x-filament::section>
    </div>

    <x-filament::section heading="En kârlı müşteriler">
        @include('filament.pages.partials.accounting-data-table', [
            'headers' => ['Müşteri', 'Sipariş', 'Gelir', 'Kâr', 'Marj', 'Son sipariş'],
            'rows' => collect($report['customers'])->map(fn ($row) => [
                '<div class="font-medium">'.e($row['name']).'</div><div class="text-xs text-gray-500 dark:text-gray-400">'.e($row['email']).'</div>',
                (string) $row['order_count'],
                AccountingReportsPage::money($row['revenue']),
                '<span class="'.($row['profit'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400').'">'.AccountingReportsPage::money($row['profit']).'</span>',
                AccountingReportsPage::percent($row['margin']),
                e($row['last_order_at']),
            ])->all(),
            'empty' => 'Müşteri verisi yok.',
        ])
    </x-filament::section>

    <x-filament::section heading="Pasif müşteriler (90+ gün sipariş yok)">
        @include('filament.pages.partials.accounting-data-table', [
            'headers' => ['Müşteri', 'Son sipariş', 'Pasif gün', 'Toplam gelir', 'Toplam kâr'],
            'rows' => collect($report['inactive'])->map(fn ($row) => [
                '<div class="font-medium">'.e($row['name']).'</div><div class="text-xs text-gray-500 dark:text-gray-400">'.e($row['email']).'</div>',
                e($row['last_order_at']),
                (string) $row['days_inactive'],
                AccountingReportsPage::money($row['lifetime_revenue']),
                AccountingReportsPage::money($row['lifetime_profit']),
            ])->all(),
            'empty' => 'Pasif müşteri bulunamadı.',
        ])
    </x-filament::section>

    <x-filament::section heading="Son 30 gün — günlük brüt kâr trendi">
        @include('filament.pages.partials.accounting-data-table', [
            'headers' => ['Tarih', 'Gelir', 'Maliyet', 'Kâr'],
            'rows' => collect(array_slice($report['trend'], -14))->map(fn ($row) => [
                \Carbon\Carbon::parse($row['date'])->format('d.m.Y'),
                AccountingReportsPage::money($row['revenue']),
                AccountingReportsPage::money($row['cogs']),
                '<span class="'.($row['profit'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400').'">'.AccountingReportsPage::money($row['profit']).'</span>',
            ])->all(),
            'empty' => 'Trend verisi yok.',
        ])
    </x-filament::section>
</div>
