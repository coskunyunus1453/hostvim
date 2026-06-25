<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\CacheService;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
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

class ManageGeneralSettings extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Tasarım & Yapılandırma';

    protected static ?string $navigationLabel = 'Genel & Marka';

    protected static ?string $title = 'Genel Site Ayarları';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $slug = 'genel-ayarlar';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::whereIn('key', $this->settingKeys())->pluck('value', 'key')->toArray();

        foreach ($this->settingKeys() as $key) {
            $settings[$key] ??= '';
        }

        foreach ($this->booleanKeys() as $key) {
            if (isset($settings[$key])) {
                $settings[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        foreach (['site_logo', 'site_logo_dark', 'site_favicon'] as $imageKey) {
            if (! empty($settings[$imageKey]) && is_string($settings[$imageKey])) {
                $settings[$imageKey] = [$settings[$imageKey]];
            }
        }

        $this->form->fill($settings);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Site Kimliği')->schema([
                TextInput::make('site_name')
                    ->label('Site Adı')
                    ->required()
                    ->maxLength(100)
                    ->columnSpanFull(),
            ]),

            Section::make('Logo & Favicon')
                ->description('Görselleri doğrudan yükleyin. Boyutlar piksel cinsinden ön yüzde uygulanır.')
                ->schema([
                    FileUpload::make('site_logo')
                        ->label('Site Logosu (Açık tema)')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->directory('branding')
                        ->imageEditor()
                        ->helperText('PNG veya SVG önerilir. Şeffaf arka plan ideal.'),
                    FileUpload::make('site_logo_dark')
                        ->label('Site Logosu (Koyu tema)')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->directory('branding')
                        ->imageEditor()
                        ->helperText('Boş bırakılırsa açık tema logosu kullanılır.'),
                    FileUpload::make('site_favicon')
                        ->label('Favicon')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->directory('branding')
                        ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml', 'image/jpeg'])
                        ->helperText('32×32 veya 64×64 PNG/ICO önerilir.'),
                    TextInput::make('site_logo_height')
                        ->label('Header logo yüksekliği (px)')
                        ->numeric()
                        ->minValue(20)
                        ->maxValue(120)
                        ->default(40)
                        ->suffix('px'),
                    TextInput::make('site_logo_mobile_height')
                        ->label('Mobil menü logo yüksekliği (px)')
                        ->numeric()
                        ->minValue(20)
                        ->maxValue(80)
                        ->default(36)
                        ->suffix('px'),
                    TextInput::make('site_logo_footer_height')
                        ->label('Footer logo yüksekliği (px)')
                        ->numeric()
                        ->minValue(20)
                        ->maxValue(80)
                        ->default(32)
                        ->suffix('px'),
                    Toggle::make('site_logo_show_name')
                        ->label('Logo yanında site adını göster')
                        ->default(true)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('general-form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
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

            if (is_array($value)) {
                $value = collect($value)->filter()->first() ?? '';
            }

            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? ''),
                    'group' => 'general',
                    'type' => $this->settingType($key, $value),
                    'label' => $key,
                ]
            );
        }

        SettingsService::clearCache();
        app(\App\Services\CacheInvalidator::class)->forGeneralBrandingSaved();

        Notification::make()->title('Genel ayarlar kaydedildi')->success()->send();
    }

    protected function settingType(string $key, mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (str_contains($key, 'logo') || str_contains($key, 'favicon')) {
            return 'image';
        }

        return 'text';
    }

    /** @return list<string> */
    protected function settingKeys(): array
    {
        return [
            'site_name',
            'site_logo', 'site_logo_dark', 'site_favicon',
            'site_logo_height', 'site_logo_mobile_height', 'site_logo_footer_height',
            'site_logo_show_name',
        ];
    }

    /** @return list<string> */
    protected function booleanKeys(): array
    {
        return ['site_logo_show_name'];
    }
}
