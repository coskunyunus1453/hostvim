<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\CacheService;
use App\Services\Domain\DomainPricingSyncService;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
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

class ManageDomainSettings extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Domain Yönetimi';

    protected static ?string $navigationLabel = 'Domain Ayarları';

    protected static ?string $title = 'Domain Satış Ayarları';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $slug = 'domain-ayarlari';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $keys = ['domain_register_enabled', 'domain_usd_try_rate', 'domain_eur_try_rate', 'domain_gbp_try_rate', 'domain_default_markup_percent', 'domain_auto_import_tlds', 'domain_value_gemini_api_key'];
        $settings = SiteSetting::whereIn('key', $keys)->pluck('value', 'key')->toArray();

        $this->form->fill([
            'domain_register_enabled' => filter_var($settings['domain_register_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'domain_usd_try_rate' => $settings['domain_usd_try_rate'] ?? config('domain_registrars.default_usd_try_rate', 35),
            'domain_eur_try_rate' => $settings['domain_eur_try_rate'] ?? 0,
            'domain_gbp_try_rate' => $settings['domain_gbp_try_rate'] ?? 0,
            'domain_default_markup_percent' => $settings['domain_default_markup_percent'] ?? config('domain_registrars.default_markup_percent', 15),
            'domain_auto_import_tlds' => filter_var($settings['domain_auto_import_tlds'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'domain_value_gemini_api_key' => $settings['domain_value_gemini_api_key'] ?? '',
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Genel')->schema([
                Toggle::make('domain_register_enabled')->label('Domain satışı aktif'),
                TextInput::make('domain_default_markup_percent')
                    ->label('Varsayılan kar marjı (%)')
                    ->numeric()
                    ->required()
                    ->helperText('TLD bazında marj girilmezse bu oran kullanılır.'),
                Toggle::make('domain_auto_import_tlds')
                    ->label('API senkronunda yeni TLD otomatik ekle')
                    ->helperText('Açıksa API\'den gelen yeni uzantılar pasif olarak listeye eklenir.'),
            ])->columns(2),

            Section::make('Döviz Kurları')
                ->description('Maliyetler bu kurlarla TRY\'ye çevrilir. Kuru değiştirip kaydedince otomatik fiyatlı TLD\'lerin satış fiyatı yeniden hesaplanır.')
                ->schema([
                    TextInput::make('domain_usd_try_rate')
                        ->label('USD → TRY kuru')
                        ->numeric()
                        ->required(),
                    TextInput::make('domain_eur_try_rate')
                        ->label('EUR → TRY kuru')
                        ->numeric()
                        ->helperText('0 = USD kuru üzerinden yaklaşık çevir.'),
                    TextInput::make('domain_gbp_try_rate')
                        ->label('GBP → TRY kuru')
                        ->numeric()
                        ->helperText('0 = USD kuru üzerinden yaklaşık çevir.'),
                ])->columns(3),

            Section::make('Domain Değer Sorgulama (AI)')
                ->description('Gemini API anahtarı ile yapay zeka destekli değerleme aktif olur. Anahtar yoksa premium sözlük ve kural motoru kullanılır.')
                ->schema([
                    TextInput::make('domain_value_gemini_api_key')
                        ->label('Gemini API anahtarı')
                        ->password()
                        ->revealable()
                        ->helperText('Google AI Studio\'dan alınır. .env GEMINI_API_KEY de kullanılabilir.'),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('domain-settings-form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Kaydet')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                        Action::make('recalcPrices')
                            ->label('Tüm TLD fiyatlarını yeniden hesapla')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalDescription('Otomatik fiyatlı tüm uzantıların satış fiyatı, güncel kur ve kar marjı ile yeniden hesaplanır.')
                            ->action(function (): void {
                                $count = $this->recalculateAllPrices();
                                Notification::make()
                                    ->title('Fiyatlar güncellendi')
                                    ->body("{$count} uzantının satış fiyatı yeniden hesaplandı.")
                                    ->success()
                                    ->send();
                            }),
                        Action::make('syncPrices')
                            ->label('API katalog fiyatlarını çek')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->modalDescription('Sadece toplu fiyat veren API\'ler (ör. Porkbun) için. Spaceship toplu TLD fiyatı vermez.')
                            ->action(function (DomainPricingSyncService $sync): void {
                                $result = $sync->syncAll();
                                Notification::make()
                                    ->title('Senkron tamamlandı')
                                    ->body("Güncellenen: {$result['updated']}, yeni: {$result['created']}")
                                    ->success()
                                    ->send();
                            }),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            $type = match (true) {
                is_bool($value) => 'boolean',
                $key === 'domain_value_gemini_api_key' => 'password',
                default => 'number',
            };
            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'domain',
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'type' => $type,
                    'label' => $key,
                ]
            );
        }

        SettingsService::clearCache();
        app(CacheService::class)->clearPageCacheForPaths(['domain']);
        app(\App\Services\Panel\PanelSettingsSyncService::class)->syncBillingSafe();

        // Kur/marj degismis olabilir: otomatik fiyatli TLD'leri yeniden hesapla.
        $recalculated = $this->recalculateAllPrices();

        Notification::make()
            ->title('Domain ayarları kaydedildi')
            ->body($recalculated > 0 ? "{$recalculated} uzantının satış fiyatı güncellendi." : null)
            ->success()
            ->send();
    }

    /**
     * Otomatik fiyatli (auto_price) tum TLD'lerin satis fiyatlarini guncel kur ve marj ile yeniden hesaplar.
     */
    protected function recalculateAllPrices(): int
    {
        $count = 0;
        \App\Models\DomainTld::query()
            ->where('auto_price', true)
            ->whereNotNull('wholesale_register')
            ->where('wholesale_register', '>', 0)
            ->each(function (\App\Models\DomainTld $tld) use (&$count): void {
                $tld->save(); // saving observer recalculatePrices() calistirir
                $count++;
            });

        return $count;
    }
}
