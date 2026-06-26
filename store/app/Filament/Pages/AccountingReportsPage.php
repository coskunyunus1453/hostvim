<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\AccountingReportService;
use App\Services\SettingsService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class AccountingReportsPage extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Faturalama & Ödemeler';

    protected static ?string $navigationLabel = 'Kârlılık Raporları';

    protected static ?string $title = 'Muhasebe & Kârlılık Raporları';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static ?string $slug = 'muhasebe-raporlari';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->data = [
            'period' => 'this_month',
            'date_from' => null,
            'date_to' => null,
            'default_domain_cost' => (string) (SiteSetting::query()
                ->where('key', 'accounting_default_domain_cost')
                ->value('value') ?? '0'),
        ];

        $this->syncDatesFromPeriod();
        $this->form->fill($this->data);
    }

    public function getHeading(): string|Htmlable
    {
        return 'Muhasebe & Kârlılık Raporları';
    }

    public function getSubheading(): string|Htmlable|null
    {
        [$from, $to] = $this->dateRange();

        return $from->format('d.m.Y').' — '.$to->format('d.m.Y');
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Filtreler')
                ->schema([
                    Select::make('period')
                        ->label('Dönem')
                        ->options([
                            'this_month' => 'Bu ay',
                            'last_month' => 'Geçen ay',
                            'this_year' => 'Bu yıl',
                            'last_90_days' => 'Son 90 gün',
                            'all_time' => 'Tüm zamanlar',
                            'custom' => 'Özel aralık',
                        ])
                        ->default('this_month')
                        ->live()
                        ->afterStateUpdated(function (): void {
                            $this->syncDatesFromPeriod();
                            $this->form->fill($this->data);
                        }),
                    DatePicker::make('date_from')
                        ->label('Başlangıç')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                    DatePicker::make('date_to')
                        ->label('Bitiş')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                    TextInput::make('default_domain_cost')
                        ->label('Domain alış (yıllık ₺)')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->placeholder('ör. 199')
                        ->helperText('Yeni domain siparişlerinde kullanılır.'),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('accounting-filters')
                ->livewireSubmitHandler('applyFilters')
                ->footer([
                    Actions::make([
                        Action::make('applyFilters')
                            ->label('Raporu Güncelle')
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->submit('applyFilters'),
                    ]),
                ]),

            Section::make()
                ->schema([
                    Placeholder::make('report_dashboard')
                        ->hiddenLabel()
                        ->content(fn (): HtmlString => new HtmlString(
                            view('filament.pages.partials.accounting-report-dashboard', [
                                'report' => $this->report,
                            ])->render()
                        )),
                ])
                ->compact(),
        ]);
    }

    public function applyFilters(): void
    {
        $this->data = $this->form->getState();

        if (($this->data['period'] ?? 'this_month') !== 'custom') {
            $this->syncDatesFromPeriod();
            $this->form->fill($this->data);
        }

        SiteSetting::updateOrCreate(
            ['key' => 'accounting_default_domain_cost'],
            [
                'value' => (string) ($this->data['default_domain_cost'] ?: '0'),
                'group' => 'accounting',
                'type' => 'number',
                'label' => 'Domain varsayılan alış maliyeti',
            ]
        );
        SettingsService::clearCache();

        Notification::make()->title('Rapor güncellendi')->success()->send();
    }

    /**
     * @return array<string, mixed>
     */
    public function getReportProperty(): array
    {
        $reports = app(AccountingReportService::class);
        [$from, $to] = $this->dateRange();

        $summary = $reports->summary($from, $to);
        $products = $reports->productProfitability($from, $to, 30);
        $customers = $reports->customerRanking($from, $to, 20);
        $inactive = $reports->inactiveCustomers(90, 20);
        $byType = $reports->revenueByServiceType($from, $to);
        $byPaymentMethod = $reports->revenueByPaymentMethod($from, $to);
        $expenses = $reports->expensesByCategory($from, $to);
        $trend = $reports->dailyTrend(30);

        return [
            'summary' => $summary,
            'products' => $products,
            'topProfit' => array_slice($products, 0, 8),
            'lowProfit' => array_slice(array_reverse($products), 0, 8),
            'customers' => $customers,
            'inactive' => $inactive,
            'byType' => $byType,
            'byPaymentMethod' => $byPaymentMethod,
            'expenses' => $expenses,
            'trend' => $trend,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function dateRange(): array
    {
        $period = $this->data['period'] ?? 'this_month';
        $dateFrom = $this->data['date_from'] ?? null;
        $dateTo = $this->data['date_to'] ?? null;

        if ($period === 'custom' && $dateFrom && $dateTo) {
            return [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ];
        }

        return match ($period) {
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_year' => [now()->startOfYear(), now()->endOfDay()],
            'last_90_days' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'all_time' => [Carbon::parse('2020-01-01')->startOfDay(), now()->endOfDay()],
            default => [now()->startOfMonth(), now()->endOfDay()],
        };
    }

    protected function syncDatesFromPeriod(): void
    {
        [$from, $to] = $this->dateRange();
        $this->data['date_from'] = $from->toDateString();
        $this->data['date_to'] = $to->toDateString();
    }

    public static function money(float $amount): string
    {
        return number_format($amount, 2, ',', '.').' ₺';
    }

    public static function percent(?float $value): string
    {
        return $value === null ? '—' : number_format($value, 1, ',', '.').'%';
    }
}
