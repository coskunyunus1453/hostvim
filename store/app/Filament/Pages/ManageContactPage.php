<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\CacheInvalidator;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageContactPage extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Tasarım & Yapılandırma';

    protected static ?string $navigationLabel = 'İletişim Sayfası';

    protected static ?string $title = 'İletişim Sayfası Ayarları';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $slug = 'iletisim-ayarlari';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::query()
            ->whereIn('key', $this->settingKeys())
            ->pluck('value', 'key')
            ->toArray();

        foreach ($this->settingKeys() as $key) {
            $settings[$key] ??= $this->defaults()[$key] ?? '';
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
            Section::make('Sayfa içeriği')
                ->description('Ön yüzdeki iletişim sayfası başlık ve açıklamaları.')
                ->schema([
                    TextInput::make('contact_page_title')
                        ->label('Sayfa başlığı')
                        ->maxLength(120)
                        ->columnSpanFull(),
                    Textarea::make('contact_page_subtitle')
                        ->label('Alt başlık / açıklama')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),

            Section::make('İletişim bilgileri')
                ->description('Form yanında ve footer\'da gösterilir.')
                ->schema([
                    TextInput::make('contact_phone')
                        ->label('Telefon')
                        ->tel()
                        ->maxLength(40),
                    TextInput::make('contact_email')
                        ->label('E-posta')
                        ->email()
                        ->maxLength(120),
                    TextInput::make('contact_whatsapp')
                        ->label('WhatsApp (opsiyonel)')
                        ->placeholder('+905551234567')
                        ->maxLength(30),
                    TextInput::make('contact_hours')
                        ->label('Çalışma saatleri')
                        ->placeholder('Pzt–Cum 09:00–18:00')
                        ->maxLength(120),
                    Textarea::make('contact_address')
                        ->label('Adres')
                        ->rows(2)
                        ->maxLength(300)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Kaydet')
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($this->settingKeys() as $key) {
            $value = $data[$key] ?? '';
            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $value,
                    'group' => 'contact',
                    'type' => 'text',
                    'label' => $key,
                ],
            );
        }

        SettingsService::clearCache();
        app(CacheInvalidator::class)->forSeoSettingsSaved();

        Notification::make()->title('İletişim ayarları kaydedildi')->success()->send();
    }

    /** @return array<string, string> */
    protected function defaults(): array
    {
        return [
            'contact_page_title' => 'Bize Ulaşın',
            'contact_page_subtitle' => 'Sorularınız, teklif talepleriniz ve teknik destek için ekibimiz yanınızda.',
            'contact_phone' => '+90 (212) 555 00 00',
            'contact_email' => 'destek@hostvim.com',
            'contact_address' => 'İstanbul, Türkiye',
            'contact_whatsapp' => '',
            'contact_hours' => '7/24 Türkçe destek',
        ];
    }

    /** @return list<string> */
    protected function settingKeys(): array
    {
        return array_keys($this->defaults());
    }
}
