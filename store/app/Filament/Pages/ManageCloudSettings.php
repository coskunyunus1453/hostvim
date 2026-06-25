<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\CacheService;
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

class ManageCloudSettings extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Sunucu & Altyapı';

    protected static ?string $navigationLabel = 'Bulut Ayarları';

    protected static ?string $title = 'Bulut VPS Ayarları';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $slug = 'bulut-ayarlari';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $keys = ['cloud_provision_enabled', 'cloud_usd_try_rate', 'cloud_eur_try_rate', 'cloud_auto_install_panel', 'cloud_panelze_install_url', 'cloud_panelze_panel_url'];
        $settings = SiteSetting::whereIn('key', $keys)->pluck('value', 'key')->toArray();

        $this->form->fill([
            'cloud_provision_enabled' => filter_var($settings['cloud_provision_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'cloud_usd_try_rate' => $settings['cloud_usd_try_rate'] ?? 35,
            'cloud_eur_try_rate' => $settings['cloud_eur_try_rate'] ?? 38,
            'cloud_auto_install_panel' => filter_var($settings['cloud_auto_install_panel'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'cloud_panelze_install_url' => $settings['cloud_panelze_install_url'] ?? '',
            'cloud_panelze_panel_url' => $settings['cloud_panelze_panel_url'] ?? '',
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Otomatik Kurulum')->schema([
                Toggle::make('cloud_provision_enabled')
                    ->label('Ödeme sonrası otomatik sunucu kurulumu')
                    ->helperText('Kapalıyken siparişler manuel işlenir.'),
                TextInput::make('cloud_usd_try_rate')->label('USD → TRY')->numeric()->required(),
                TextInput::make('cloud_eur_try_rate')->label('EUR → TRY (Hetzner)')->numeric()->required(),
            ])->columns(2),

            Section::make('Panelze Otomatik Kurulumu (cloud-init)')
                ->description('Açıkken yeni VPS oluşturulurken cloud-init ile sunucuya otomatik Panelze paneli kurulur ve müşteriye panel bilgisi mail atılır.')
                ->schema([
                    Toggle::make('cloud_auto_install_panel')
                        ->label('VPS\'e otomatik Panelze paneli kur')
                        ->helperText('Çalışması için aşağıdaki kurulum betiği URL\'i dolu olmalı.')
                        ->columnSpanFull(),
                    TextInput::make('cloud_panelze_install_url')
                        ->label('Kurulum betiği URL (remote-install.sh)')
                        ->placeholder('https://install.panelze.com/remote-install.sh')
                        ->url()
                        ->helperText('VPS\'te root olarak çalıştırılacak kurulum betiğinin erişilebilir adresi.')
                        ->columnSpanFull(),
                    TextInput::make('cloud_panelze_panel_url')
                        ->label('Panel adresi (mailde gösterilir, opsiyonel)')
                        ->placeholder('https://panel.alanadiniz.com')
                        ->columnSpanFull(),
                ])->columns(1),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('cloud-settings-form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Kaydet')->submit('save')->keyBindings(['mod+s']),
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
                is_numeric($value) => 'number',
                default => 'text',
            };
            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'cloud',
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'type' => $type,
                    'label' => $key,
                ]
            );
        }
        SettingsService::clearCache();
        Notification::make()->title('Bulut ayarları kaydedildi')->success()->send();
    }
}
