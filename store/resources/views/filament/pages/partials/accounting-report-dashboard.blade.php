@php
    use App\Filament\Pages\AccountingReportsPage;

    $summary = $report['summary'];

    $profitCell = static fn (float $amount): string => '<span class="fi-accounting-money '
        .($amount >= 0 ? 'fi-accounting-money--profit' : 'fi-accounting-money--loss').'">'
        .AccountingReportsPage::money($amount).'</span>';

    $primaryStats = [
        [
            'label' => 'Gelir (satış)',
            'value' => AccountingReportsPage::money((float) $summary['revenue']),
            'tone' => 'success',
            'icon' => 'heroicon-o-arrow-trending-up',
        ],
        [
            'label' => 'Maliyet (COGS)',
            'value' => AccountingReportsPage::money((float) $summary['cogs']),
            'tone' => 'warning',
            'icon' => 'heroicon-o-shopping-cart',
        ],
        [
            'label' => 'Brüt kâr',
            'value' => AccountingReportsPage::money((float) $summary['gross_profit']),
            'tone' => 'primary',
            'icon' => 'heroicon-o-banknotes',
        ],
        [
            'label' => 'Net kâr',
            'value' => AccountingReportsPage::money((float) $summary['net_profit']),
            'tone' => ($summary['net_profit'] ?? 0) >= 0 ? 'success' : 'danger',
            'icon' => 'heroicon-o-calculator',
        ],
    ];

    $secondaryStats = [
        ['label' => 'Brüt marj', 'value' => AccountingReportsPage::percent($summary['gross_margin']), 'tone' => 'gray'],
        ['label' => 'İşletme giderleri', 'value' => AccountingReportsPage::money((float) $summary['expenses']), 'tone' => 'warning'],
        ['label' => 'Net marj', 'value' => AccountingReportsPage::percent($summary['net_margin']), 'tone' => 'primary'],
    ];
@endphp

@once
    <style>
            .fi-accounting-report {
                --fi-accounting-stat-ring: rgb(3 7 18 / 0.05);
            }

            .dark .fi-accounting-report {
                --fi-accounting-stat-ring: rgb(255 255 255 / 0.1);
            }

            .fi-accounting-stat {
                background: #fff;
                border-radius: 0.75rem;
                box-shadow: 0 1px 2px rgb(3 7 18 / 0.04);
                padding: 1rem 1.125rem;
            }

            .dark .fi-accounting-stat {
                background: rgb(17 24 39);
            }

            .fi-accounting-stat__icon {
                align-items: center;
                border-radius: 0.5rem;
                display: inline-flex;
                height: 2.25rem;
                justify-content: center;
                margin-bottom: 0.75rem;
                width: 2.25rem;
            }

            .fi-accounting-stat__icon--primary { background: rgb(194 65 12 / 0.1); color: #c2410c; }
            .fi-accounting-stat__icon--success { background: rgb(22 101 52 / 0.1); color: #166534; }
            .fi-accounting-stat__icon--warning { background: rgb(234 88 12 / 0.1); color: #ea580c; }
            .fi-accounting-stat__icon--danger { background: rgb(220 38 38 / 0.1); color: #dc2626; }
            .fi-accounting-stat__icon--gray { background: rgb(107 114 128 / 0.12); color: #6b7280; }

            .fi-accounting-stat__value--primary { color: #c2410c; }
            .fi-accounting-stat__value--success { color: #166534; }
            .fi-accounting-stat__value--warning { color: #ea580c; }
            .fi-accounting-stat__value--danger { color: #dc2626; }
            .fi-accounting-stat__value--gray { color: rgb(17 24 39); }

            .dark .fi-accounting-stat__value--primary { color: #fb923c; }
            .dark .fi-accounting-stat__value--success { color: #4ade80; }
            .dark .fi-accounting-stat__value--warning { color: #fdba74; }
            .dark .fi-accounting-stat__value--danger { color: #f87171; }
            .dark .fi-accounting-stat__value--gray { color: #fff; }

            .fi-accounting-table__grid {
                border-collapse: separate;
                border-spacing: 0;
                min-width: 100%;
            }

            .fi-accounting-table__th {
                border-bottom: 1px solid rgb(229 231 235);
                white-space: nowrap;
            }

            .dark .fi-accounting-table__th {
                border-bottom-color: rgb(255 255 255 / 0.1);
            }

            .fi-accounting-table__td {
                line-height: 1.45;
                vertical-align: top;
            }

            .fi-accounting-cell-label {
                max-width: 16rem;
                overflow-wrap: anywhere;
            }

            .fi-accounting-money {
                font-variant-numeric: tabular-nums;
                font-weight: 600;
                white-space: nowrap;
            }

            .fi-accounting-money--profit { color: #166534; }
            .fi-accounting-money--loss { color: #dc2626; }

            .dark .fi-accounting-money--profit { color: #4ade80; }
            .dark .fi-accounting-money--loss { color: #f87171; }

            .fi-accounting-badge {
                background: rgb(243 244 246);
                border-radius: 9999px;
                color: rgb(55 65 81);
                display: inline-block;
                font-size: 0.75rem;
                font-variant-numeric: tabular-nums;
                font-weight: 600;
                line-height: 1;
                min-width: 2rem;
                padding: 0.25rem 0.5rem;
                text-align: center;
            }

            .fi-accounting-badge--muted {
                background: rgb(249 250 251);
                color: rgb(75 85 99);
            }

            .dark .fi-accounting-badge {
                background: rgb(255 255 255 / 0.08);
                color: rgb(229 231 235);
            }

            .dark .fi-accounting-badge--muted {
                background: rgb(255 255 255 / 0.05);
                color: rgb(156 163 175);
            }

            @media (max-width: 640px) {
                .fi-accounting-table__th,
                .fi-accounting-table__td {
                    padding-left: 0.75rem;
                    padding-right: 0.75rem;
                }

                .fi-accounting-cell-label {
                    max-width: 11rem;
                }
            }
    </style>
@endonce

<div class="fi-accounting-report space-y-6">
    @if (($summary['unknown_cost_lines'] ?? 0) > 0)
        <div class="flex gap-3 rounded-xl bg-warning-50 p-4 text-sm text-warning-900 ring-1 ring-warning-600/15 dark:bg-warning-400/10 dark:text-warning-100 dark:ring-warning-400/25">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-warning-600 dark:text-warning-400" />
            <div>
                <p class="font-medium">Eksik maliyet bilgisi</p>
                <p class="mt-1 text-warning-800/90 dark:text-warning-100/80">
                    {{ $summary['unknown_cost_lines'] }} sipariş satırında alış maliyeti girilmemiş.
                    Ürün formlarından alış fiyatlarını tanımlayın veya domain varsayılan maliyetini ayarlayın.
                </p>
            </div>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($primaryStats as $stat)
            <div class="fi-accounting-stat ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="fi-accounting-stat__icon fi-accounting-stat__icon--{{ $stat['tone'] }}">
                    <x-filament::icon :icon="$stat['icon']" class="h-5 w-5" />
                </div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                <div class="fi-accounting-stat__value--{{ $stat['tone'] }} mt-1 text-2xl font-semibold tracking-tight tabular-nums">
                    {{ $stat['value'] }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach ($secondaryStats as $stat)
            <div class="fi-accounting-stat ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                <div class="fi-accounting-stat__value--{{ $stat['tone'] }} mt-1 text-xl font-semibold tabular-nums">
                    {{ $stat['value'] }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-filament::section heading="Hizmet tipine göre kâr" compact>
            @include('filament.pages.partials.accounting-data-table', [
                'headers' => ['Tip', 'Gelir', 'Maliyet', 'Kâr', 'Marj'],
                'align' => ['start', 'end', 'end', 'end', 'end'],
                'rows' => collect($report['byType'])->map(fn ($row) => [
                    '<span class="font-medium text-gray-950 dark:text-white">'.e($row['label']).'</span>',
                    AccountingReportsPage::money($row['revenue']),
                    AccountingReportsPage::money($row['cogs']),
                    $profitCell((float) $row['profit']),
                    '<span class="fi-accounting-badge fi-accounting-badge--muted">'.AccountingReportsPage::percent($row['margin']).'</span>',
                ])->all(),
                'empty' => 'Bu dönemde veri yok.',
            ])
        </x-filament::section>

        <x-filament::section heading="Gider kategorileri" compact>
            @include('filament.pages.partials.accounting-data-table', [
                'headers' => ['Kategori', 'Tutar', 'Adet'],
                'align' => ['start', 'end', 'end'],
                'rows' => collect($report['expenses'])->map(fn ($row) => [
                    '<span class="font-medium text-gray-950 dark:text-white">'.e($row['label']).'</span>',
                    AccountingReportsPage::money($row['amount']),
                    '<span class="fi-accounting-badge">'.e((string) $row['count']).'</span>',
                ])->all(),
                'empty' => 'Gider kaydı yok. Gider Kayıtları menüsünden ekleyin.',
            ])
        </x-filament::section>
    </div>

    <x-filament::section heading="Ödeme yöntemine göre gelir & komisyon" compact>
        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
            Komisyon oranları "Ödeme Yöntemleri" ekranından her yöntem için girilir. Net = Gelir − tahmini komisyon.
        </p>
        @include('filament.pages.partials.accounting-data-table', [
            'headers' => ['Yöntem', 'Sipariş', 'Gelir', 'Komisyon %', 'Tah. Komisyon', 'Net'],
            'align' => ['start', 'end', 'end', 'end', 'end', 'end'],
            'rows' => collect($report['byPaymentMethod'])->map(fn ($row) => [
                '<span class="font-medium text-gray-950 dark:text-white">'.e($row['label']).'</span>',
                '<span class="fi-accounting-badge">'.e((string) $row['order_count']).'</span>',
                AccountingReportsPage::money($row['revenue']),
                '<span class="fi-accounting-badge fi-accounting-badge--muted">'.AccountingReportsPage::percent($row['commission_rate']).'</span>',
                '<span class="fi-accounting-money fi-accounting-money--loss">'.AccountingReportsPage::money($row['commission']).'</span>',
                '<span class="fi-accounting-money fi-accounting-money--profit">'.AccountingReportsPage::money($row['net']).'</span>',
            ])->all(),
            'empty' => 'Bu dönemde ödeme kaydı yok.',
        ])
    </x-filament::section>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-filament::section heading="En kârlı ürünler / hizmetler" compact>
            @include('filament.pages.partials.profit-table', ['rows' => $report['topProfit']])
        </x-filament::section>

        <x-filament::section heading="En az kâr bırakanlar" compact>
            @include('filament.pages.partials.profit-table', ['rows' => $report['lowProfit']])
        </x-filament::section>
    </div>

    <x-filament::section heading="En kârlı müşteriler" compact>
        @include('filament.pages.partials.accounting-data-table', [
            'headers' => ['Müşteri', 'Sipariş', 'Gelir', 'Kâr', 'Marj', 'Son sipariş'],
            'align' => ['start', 'end', 'end', 'end', 'end', 'end'],
            'rows' => collect($report['customers'])->map(fn ($row) => [
                '<div class="fi-accounting-cell-label font-medium text-gray-950 dark:text-white">'.e($row['name']).'</div>'
                    .'<div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">'.e($row['email']).'</div>',
                '<span class="fi-accounting-badge">'.e((string) $row['order_count']).'</span>',
                AccountingReportsPage::money($row['revenue']),
                $profitCell((float) $row['profit']),
                '<span class="fi-accounting-badge fi-accounting-badge--muted">'.AccountingReportsPage::percent($row['margin']).'</span>',
                '<span class="text-gray-600 dark:text-gray-300">'.e($row['last_order_at']).'</span>',
            ])->all(),
            'empty' => 'Müşteri verisi yok.',
        ])
    </x-filament::section>

    <x-filament::section heading="Pasif müşteriler (90+ gün sipariş yok)" compact>
        @include('filament.pages.partials.accounting-data-table', [
            'headers' => ['Müşteri', 'Son sipariş', 'Pasif gün', 'Toplam gelir', 'Toplam kâr'],
            'align' => ['start', 'end', 'end', 'end', 'end'],
            'rows' => collect($report['inactive'])->map(fn ($row) => [
                '<div class="fi-accounting-cell-label font-medium text-gray-950 dark:text-white">'.e($row['name']).'</div>'
                    .'<div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">'.e($row['email']).'</div>',
                '<span class="text-gray-600 dark:text-gray-300">'.e($row['last_order_at']).'</span>',
                '<span class="fi-accounting-badge">'.e((string) $row['days_inactive']).'</span>',
                AccountingReportsPage::money($row['lifetime_revenue']),
                $profitCell((float) $row['lifetime_profit']),
            ])->all(),
            'empty' => 'Pasif müşteri bulunamadı.',
        ])
    </x-filament::section>

    <x-filament::section heading="Son 30 gün — günlük brüt kâr trendi" compact>
        @include('filament.pages.partials.accounting-data-table', [
            'headers' => ['Tarih', 'Gelir', 'Maliyet', 'Kâr'],
            'align' => ['start', 'end', 'end', 'end'],
            'rows' => collect(array_slice($report['trend'], -14))->map(fn ($row) => [
                '<span class="font-medium text-gray-950 dark:text-white">'.\Carbon\Carbon::parse($row['date'])->format('d.m.Y').'</span>',
                AccountingReportsPage::money($row['revenue']),
                AccountingReportsPage::money($row['cogs']),
                $profitCell((float) $row['profit']),
            ])->all(),
            'empty' => 'Trend verisi yok.',
        ])
    </x-filament::section>
</div>
