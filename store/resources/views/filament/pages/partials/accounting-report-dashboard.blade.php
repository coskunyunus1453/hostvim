@php
    use App\Filament\Pages\AccountingReportsPage;

    $summary = $report['summary'];

    $profitCell = static fn (float $amount): string => '<span class="fi-acct-num '
        .($amount >= 0 ? 'fi-acct-num--up' : 'fi-acct-num--down').'">'
        .AccountingReportsPage::money($amount).'</span>';

    $kpis = [
        ['label' => 'Gelir', 'value' => AccountingReportsPage::money((float) $summary['revenue']), 'tone' => 'success', 'icon' => 'heroicon-o-arrow-trending-up'],
        ['label' => 'Maliyet', 'value' => AccountingReportsPage::money((float) $summary['cogs']), 'tone' => 'warning', 'icon' => 'heroicon-o-shopping-cart'],
        ['label' => 'Brüt kâr', 'value' => AccountingReportsPage::money((float) $summary['gross_profit']), 'tone' => 'primary', 'icon' => 'heroicon-o-banknotes'],
        ['label' => 'Net kâr', 'value' => AccountingReportsPage::money((float) $summary['net_profit']), 'tone' => ($summary['net_profit'] ?? 0) >= 0 ? 'success' : 'danger', 'icon' => 'heroicon-o-calculator'],
        ['label' => 'Brüt marj', 'value' => AccountingReportsPage::percent($summary['gross_margin']), 'tone' => 'gray', 'icon' => 'heroicon-o-chart-pie'],
        ['label' => 'Giderler', 'value' => AccountingReportsPage::money((float) $summary['expenses']), 'tone' => 'warning', 'icon' => 'heroicon-o-receipt-percent'],
        ['label' => 'Net marj', 'value' => AccountingReportsPage::percent($summary['net_margin']), 'tone' => 'primary', 'icon' => 'heroicon-o-scale'],
    ];
@endphp

@once
    <style>
        .fi-acct {
            --fi-acct-accent: #c2410c;
            --fi-acct-up: #166534;
            --fi-acct-down: #dc2626;
            max-width: 72rem;
        }

        .dark .fi-acct {
            --fi-acct-accent: #fb923c;
            --fi-acct-up: #4ade80;
            --fi-acct-down: #f87171;
        }

        .fi-acct-card {
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgb(3 7 18 / 0.04);
            overflow: hidden;
        }

        .dark .fi-acct-card {
            background: rgb(17 24 39);
        }

        .fi-acct-card__head {
            align-items: center;
            border-bottom: 1px solid rgb(229 231 235 / 0.9);
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
            padding: 0.625rem 0.875rem;
        }

        .dark .fi-acct-card__head {
            border-bottom-color: rgb(255 255 255 / 0.08);
        }

        .fi-acct-card__title {
            font-size: 0.8125rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            color: rgb(17 24 39);
        }

        .dark .fi-acct-card__title {
            color: #fff;
        }

        .fi-acct-card__body {
            padding: 0.25rem 0.5rem 0.5rem;
        }

        .fi-acct-kpi {
            background: #fff;
            border-radius: 0.625rem;
            padding: 0.625rem 0.75rem;
            min-height: 4.5rem;
        }

        .dark .fi-acct-kpi {
            background: rgb(17 24 39);
        }

        .fi-acct-kpi__row {
            align-items: center;
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
        }

        .fi-acct-kpi__icon {
            align-items: center;
            border-radius: 0.375rem;
            display: inline-flex;
            flex-shrink: 0;
            height: 1.75rem;
            justify-content: center;
            width: 1.75rem;
        }

        .fi-acct-kpi__icon--primary { background: rgb(194 65 12 / 0.12); color: #c2410c; }
        .fi-acct-kpi__icon--success { background: rgb(22 101 52 / 0.12); color: #166534; }
        .fi-acct-kpi__icon--warning { background: rgb(234 88 12 / 0.12); color: #ea580c; }
        .fi-acct-kpi__icon--danger { background: rgb(220 38 38 / 0.12); color: #dc2626; }
        .fi-acct-kpi__icon--gray { background: rgb(107 114 128 / 0.14); color: #6b7280; }

        .fi-acct-kpi__label {
            color: rgb(107 114 128);
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .dark .fi-acct-kpi__label { color: rgb(156 163 175); }

        .fi-acct-kpi__value {
            font-size: 0.9375rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-top: 0.25rem;
            font-variant-numeric: tabular-nums;
        }

        .fi-acct-kpi__value--primary { color: var(--fi-acct-accent); }
        .fi-acct-kpi__value--success { color: var(--fi-acct-up); }
        .fi-acct-kpi__value--warning { color: #ea580c; }
        .fi-acct-kpi__value--danger { color: var(--fi-acct-down); }
        .fi-acct-kpi__value--gray { color: rgb(17 24 39); }
        .dark .fi-acct-kpi__value--gray { color: #fff; }

        .fi-acct-label {
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.3;
            overflow-wrap: anywhere;
            color: rgb(17 24 39);
        }

        .dark .fi-acct-label { color: #fff; }

        .fi-acct-sub {
            color: rgb(107 114 128);
            font-size: 0.625rem;
            line-height: 1.3;
            margin-top: 0.125rem;
        }

        .dark .fi-acct-sub { color: rgb(156 163 175); }

        .fi-acct-num {
            font-size: 0.75rem;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            white-space: nowrap;
        }

        .fi-acct-num--up { color: var(--fi-acct-up); }
        .fi-acct-num--down { color: var(--fi-acct-down); }

        .fi-acct-pill {
            background: rgb(243 244 246);
            border-radius: 9999px;
            color: rgb(55 65 81);
            display: inline-block;
            font-size: 0.6875rem;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            line-height: 1;
            min-width: 1.5rem;
            padding: 0.2rem 0.45rem;
            text-align: center;
        }

        .fi-acct-pill--muted {
            background: rgb(249 250 251);
            color: rgb(75 85 99);
        }

        .dark .fi-acct-pill {
            background: rgb(255 255 255 / 0.08);
            color: rgb(229 231 235);
        }

        .dark .fi-acct-pill--muted {
            background: rgb(255 255 255 / 0.05);
            color: rgb(156 163 175);
        }

        .fi-acct-alert {
            align-items: flex-start;
            background: rgb(255 251 235);
            border-radius: 0.625rem;
            display: flex;
            font-size: 0.75rem;
            gap: 0.5rem;
            padding: 0.625rem 0.75rem;
            ring: 1px solid rgb(234 179 8 / 0.25);
        }

        .dark .fi-acct-alert {
            background: rgb(234 179 8 / 0.08);
        }
    </style>
@endonce

<div class="fi-acct mx-auto w-full space-y-3">
    @if (($summary['unknown_cost_lines'] ?? 0) > 0)
        <div class="fi-acct-alert text-amber-900 dark:text-amber-100">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
            <p>
                <strong>{{ $summary['unknown_cost_lines'] }}</strong> satırda maliyet eksik —
                ürün alış fiyatlarını veya domain varsayılan maliyetini güncelleyin.
            </p>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
        @foreach ($kpis as $kpi)
            <div class="fi-acct-kpi ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="fi-acct-kpi__row">
                    <span class="fi-acct-kpi__label">{{ $kpi['label'] }}</span>
                    <span class="fi-acct-kpi__icon fi-acct-kpi__icon--{{ $kpi['tone'] }}">
                        <x-filament::icon :icon="$kpi['icon']" class="h-3.5 w-3.5" />
                    </span>
                </div>
                <div class="fi-acct-kpi__value fi-acct-kpi__value--{{ $kpi['tone'] }}">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-3 lg:grid-cols-2">
        <div class="fi-acct-card ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="fi-acct-card__head">
                <span class="fi-acct-card__title">Hizmet tipine göre kâr</span>
            </div>
            <div class="fi-acct-card__body">
                @include('filament.pages.partials.accounting-data-table', [
                    'headers' => ['Tip', 'Gelir', 'Maliyet', 'Kâr', 'Marj'],
                    'align' => ['start', 'end', 'end', 'end', 'end'],
                    'compact' => true,
                    'maxHeight' => '14rem',
                    'rows' => collect($report['byType'])->map(fn ($row) => [
                        '<span class="fi-acct-label">'.e($row['label']).'</span>',
                        '<span class="fi-acct-num">'.AccountingReportsPage::money($row['revenue']).'</span>',
                        '<span class="fi-acct-num">'.AccountingReportsPage::money($row['cogs']).'</span>',
                        $profitCell((float) $row['profit']),
                        '<span class="fi-acct-pill fi-acct-pill--muted">'.AccountingReportsPage::percent($row['margin']).'</span>',
                    ])->all(),
                    'empty' => 'Bu dönemde veri yok.',
                ])
            </div>
        </div>

        <div class="fi-acct-card ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="fi-acct-card__head">
                <span class="fi-acct-card__title">Gider kategorileri</span>
            </div>
            <div class="fi-acct-card__body">
                @include('filament.pages.partials.accounting-data-table', [
                    'headers' => ['Kategori', 'Tutar', 'Adet'],
                    'align' => ['start', 'end', 'end'],
                    'compact' => true,
                    'maxHeight' => '14rem',
                    'rows' => collect($report['expenses'])->map(fn ($row) => [
                        '<span class="fi-acct-label">'.e($row['label']).'</span>',
                        '<span class="fi-acct-num">'.AccountingReportsPage::money($row['amount']).'</span>',
                        '<span class="fi-acct-pill">'.e((string) $row['count']).'</span>',
                    ])->all(),
                    'empty' => 'Gider kaydı yok.',
                ])
            </div>
        </div>

        <div class="fi-acct-card ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="fi-acct-card__head">
                <span class="fi-acct-card__title">En kârlı ürünler</span>
            </div>
            <div class="fi-acct-card__body">
                @include('filament.pages.partials.profit-table', ['rows' => $report['topProfit']])
            </div>
        </div>

        <div class="fi-acct-card ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="fi-acct-card__head">
                <span class="fi-acct-card__title">Düşük kârlılık</span>
            </div>
            <div class="fi-acct-card__body">
                @include('filament.pages.partials.profit-table', ['rows' => $report['lowProfit']])
            </div>
        </div>

        <div class="fi-acct-card ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="fi-acct-card__head">
                <span class="fi-acct-card__title">Ödeme yöntemleri</span>
            </div>
            <div class="fi-acct-card__body">
                @include('filament.pages.partials.accounting-data-table', [
                    'headers' => ['Yöntem', '#', 'Gelir', '%', 'Kom.', 'Net'],
                    'align' => ['start', 'end', 'end', 'end', 'end', 'end'],
                    'compact' => true,
                    'maxHeight' => '14rem',
                    'rows' => collect($report['byPaymentMethod'])->map(fn ($row) => [
                        '<span class="fi-acct-label">'.e($row['label']).'</span>',
                        '<span class="fi-acct-pill">'.e((string) $row['order_count']).'</span>',
                        '<span class="fi-acct-num">'.AccountingReportsPage::money($row['revenue']).'</span>',
                        '<span class="fi-acct-pill fi-acct-pill--muted">'.AccountingReportsPage::percent($row['commission_rate']).'</span>',
                        '<span class="fi-acct-num fi-acct-num--down">'.AccountingReportsPage::money($row['commission']).'</span>',
                        '<span class="fi-acct-num fi-acct-num--up">'.AccountingReportsPage::money($row['net']).'</span>',
                    ])->all(),
                    'empty' => 'Ödeme kaydı yok.',
                ])
            </div>
        </div>

        <div class="fi-acct-card ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="fi-acct-card__head">
                <span class="fi-acct-card__title">Son 7 gün — brüt kâr</span>
            </div>
            <div class="fi-acct-card__body">
                @include('filament.pages.partials.accounting-data-table', [
                    'headers' => ['Tarih', 'Gelir', 'Maliyet', 'Kâr'],
                    'align' => ['start', 'end', 'end', 'end'],
                    'compact' => true,
                    'maxHeight' => '14rem',
                    'rows' => collect(array_slice($report['trend'], -7))->map(fn ($row) => [
                        '<span class="fi-acct-label">'.\Carbon\Carbon::parse($row['date'])->format('d.m').'</span>',
                        '<span class="fi-acct-num">'.AccountingReportsPage::money($row['revenue']).'</span>',
                        '<span class="fi-acct-num">'.AccountingReportsPage::money($row['cogs']).'</span>',
                        $profitCell((float) $row['profit']),
                    ])->all(),
                    'empty' => 'Trend verisi yok.',
                ])
            </div>
        </div>

        <div class="fi-acct-card ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="fi-acct-card__head">
                <span class="fi-acct-card__title">En kârlı müşteriler</span>
            </div>
            <div class="fi-acct-card__body">
                @include('filament.pages.partials.accounting-data-table', [
                    'headers' => ['Müşteri', '#', 'Gelir', 'Kâr', 'Marj'],
                    'align' => ['start', 'end', 'end', 'end', 'end'],
                    'compact' => true,
                    'maxHeight' => '14rem',
                    'rows' => collect($report['customers'])->map(fn ($row) => [
                        '<div><div class="fi-acct-label">'.e($row['name']).'</div>'
                            .'<div class="fi-acct-sub">'.e($row['email']).'</div></div>',
                        '<span class="fi-acct-pill">'.e((string) $row['order_count']).'</span>',
                        '<span class="fi-acct-num">'.AccountingReportsPage::money($row['revenue']).'</span>',
                        $profitCell((float) $row['profit']),
                        '<span class="fi-acct-pill fi-acct-pill--muted">'.AccountingReportsPage::percent($row['margin']).'</span>',
                    ])->all(),
                    'empty' => 'Müşteri verisi yok.',
                ])
            </div>
        </div>

        <div class="fi-acct-card ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="fi-acct-card__head">
                <span class="fi-acct-card__title">Pasif müşteriler (90+ gün)</span>
            </div>
            <div class="fi-acct-card__body">
                @include('filament.pages.partials.accounting-data-table', [
                    'headers' => ['Müşteri', 'Gün', 'Gelir', 'Kâr'],
                    'align' => ['start', 'end', 'end', 'end'],
                    'compact' => true,
                    'maxHeight' => '14rem',
                    'rows' => collect($report['inactive'])->map(fn ($row) => [
                        '<div><div class="fi-acct-label">'.e($row['name']).'</div>'
                            .'<div class="fi-acct-sub">'.e($row['last_order_at']).'</div></div>',
                        '<span class="fi-acct-pill">'.e((string) $row['days_inactive']).'</span>',
                        '<span class="fi-acct-num">'.AccountingReportsPage::money($row['lifetime_revenue']).'</span>',
                        $profitCell((float) $row['lifetime_profit']),
                    ])->all(),
                    'empty' => 'Pasif müşteri yok.',
                ])
            </div>
        </div>
    </div>
</div>
