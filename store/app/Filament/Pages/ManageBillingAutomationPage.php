<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\Panel\PanelSettingsSyncService;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageBillingAutomationPage extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Faturalama & Ödemeler';

    protected static ?string $navigationLabel = 'Faturalama & Otomasyon';

    protected static ?string $title = 'Faturalama & Otomasyon';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $slug = 'faturalama-otomasyonu';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $keys = $this->settingKeys();
        $settings = SiteSetting::query()->whereIn('key', $keys)->pluck('value', 'key');

        $contactEmail = SiteSetting::query()->where('key', 'contact_email')->value('value');
        $siteName = SiteSetting::query()->where('key', 'site_name')->value('value');

        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $settings->get($key);
        }

        $data['billing.company_name'] ??= $siteName ?? '';
        $data['billing.support_email'] ??= $contactEmail ?? '';

        foreach ($this->booleanKeys() as $key) {
            if (isset($data[$key])) {
                $data[$key] = filter_var($data[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        $this->form->fill($data);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Firma & Destek')
                ->description('Panelze faturalama ve bildirimlerle senkron edilir.')
                ->schema([
                    TextInput::make('billing.company_name')->label('Firma adı')->maxLength(150),
                    TextInput::make('billing.company_tax_id')->label('Vergi no')->maxLength(60),
                    Textarea::make('billing.company_address')->label('Firma adresi')->rows(2)->columnSpanFull(),
                    TextInput::make('billing.support_email')->label('Destek e-postası')->email()->helperText('İletişim e-postası ile aynı tutulması önerilir.'),
                ])->columns(2),

            Section::make('Vergi & Para birimi')->schema([
                Select::make('billing.currency')->label('Para birimi')->options([
                    'TRY' => 'TRY',
                    'USD' => 'USD',
                    'EUR' => 'EUR',
                ])->default('TRY'),
                TextInput::make('billing.tax_rate')->label('KDV (%)')->numeric()->default(20),
                Toggle::make('billing.tax_inclusive')->label('Fiyatlara KDV dahil'),
            ])->columns(3),

            Section::make('Otomasyon')->schema([
                Toggle::make('billing.enabled')->label('Panel faturalama otomasyonu')->default(true),
                TextInput::make('billing.due_days')->label('Fatura vadesi (gün)')->numeric()->default(7),
                TextInput::make('billing.renew_generate_days_before')->label('Yenileme faturası (gün önce)')->numeric()->default(10),
                TextInput::make('billing.suspend_after_days')->label('Askıya alma (vade+gün)')->numeric()->default(3),
                TextInput::make('billing.terminate_after_days')->label('Sonlandırma (askı+gün)')->numeric()->default(15),
                Toggle::make('billing.auto_suspend')->label('Otomatik askıya alma')->default(true),
                Toggle::make('billing.auto_terminate')->label('Otomatik sonlandırma'),
            ])->columns(2),

            Section::make('Panel ödeme eşlemesi')
                ->description('Ödeme API anahtarları Ödeme Yöntemleri ekranından yönetilir; kayıtta Panelze ile senkron edilir.')
                ->schema([
                    Select::make('billing.payment_provider')->label('Panel varsayılan sağlayıcı')->options([
                        'auto' => 'Otomatik',
                        'paytr' => 'PayTR',
                        'iyzico' => 'iyzico',
                        'stripe' => 'Stripe',
                        'manual' => 'Manuel / Havale',
                    ])->default('auto'),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('billing-automation-form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('syncPanel')
                            ->label('Panelze ile senkron et')
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->color('gray')
                            ->action(fn () => $this->pushToPanel(manual: true)),
                        Action::make('save')
                            ->label('Kaydet')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if (! in_array($key, $this->settingKeys(), true)) {
                continue;
            }

            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'group' => str_starts_with($key, 'billing.') ? 'billing' : 'billing',
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? ''),
                    'type' => is_bool($value) ? 'boolean' : 'text',
                    'label' => $key,
                ]
            );
        }

        if (! empty($data['billing.support_email'])) {
            SiteSetting::updateOrCreate(
                ['key' => 'contact_email'],
                [
                    'group' => 'contact',
                    'value' => (string) $data['billing.support_email'],
                    'type' => 'email',
                    'label' => 'E-posta',
                ]
            );
        }

        SettingsService::clearCache();
        $synced = $this->pushToPanel();

        Notification::make()
            ->title('Faturalama ayarları kaydedildi')
            ->body($synced ? 'Panelze ile senkron edildi.' : 'Panelze senkronu atlandı veya başarısız (API kontrol edin).')
            ->success()
            ->send();
    }

    private function pushToPanel(bool $manual = false): bool
    {
        $synced = app(PanelSettingsSyncService::class)->syncBillingSafe();

        if ($manual && ! $synced) {
            Notification::make()
                ->title('Panelze senkronu başarısız')
                ->body('PANELZE_API_URL ve PANELZE_STORE_SECRET ayarlarını kontrol edin.')
                ->warning()
                ->send();
        }

        return $synced;
    }

    /** @return list<string> */
    protected function settingKeys(): array
    {
        return [
            'billing.company_name',
            'billing.company_tax_id',
            'billing.company_address',
            'billing.support_email',
            'billing.currency',
            'billing.tax_rate',
            'billing.tax_inclusive',
            'billing.enabled',
            'billing.due_days',
            'billing.renew_generate_days_before',
            'billing.suspend_after_days',
            'billing.terminate_after_days',
            'billing.auto_suspend',
            'billing.auto_terminate',
            'billing.payment_provider',
        ];
    }

    /** @return list<string> */
    protected function booleanKeys(): array
    {
        return [
            'billing.tax_inclusive',
            'billing.enabled',
            'billing.auto_suspend',
            'billing.auto_terminate',
        ];
    }
}
