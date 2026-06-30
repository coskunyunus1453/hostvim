<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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

class ManageSecuritySettings extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Tasarım & Yapılandırma';

    protected static ?string $navigationLabel = 'Güvenlik & Captcha';

    protected static ?string $title = 'Güvenlik & Bot Koruması';

    protected static ?int $navigationSort = 90;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $slug = 'guvenlik-ayarlari';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::whereIn('key', $this->settingKeys())->pluck('value', 'key')->toArray();

        $defaults = [
            'registration_enabled' => false,
            'captcha_enabled' => true,
            'captcha_provider' => 'native',
            'captcha_site_key' => '',
            'captcha_secret_key' => '',
            'captcha_ctx_login' => true,
            'captcha_ctx_register' => true,
            'captcha_ctx_checkout' => true,
            'captcha_ctx_password' => true,
            'captcha_ctx_contact' => true,
        ];

        foreach ($this->booleanKeys() as $key) {
            if (array_key_exists($key, $settings)) {
                $settings[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $settings) || $settings[$key] === '') {
                $settings[$key] = $value;
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
            Section::make('Üye Kaydı')
                ->description('Ziyaretçilerin kendi başına hesap açmasını kontrol edin.')
                ->schema([
                    Toggle::make('registration_enabled')
                        ->label('Üye kaydına izin ver')
                        ->helperText('Kapalıyken ön yüzdeki kayıt sayfası devre dışı kalır ve müşteriler yalnızca yönetim panelinden (Müşteriler) açılır. Misafir siparişlerinde otomatik hesap oluşturma çalışmaya devam eder.')
                        ->columnSpanFull(),
                ]),

            Section::make('Captcha / Bot Koruması')
                ->description('Giriş ve sipariş formlarını botlara karşı koruyun.')
                ->schema([
                    Toggle::make('captcha_enabled')
                        ->label('Captcha korumasını etkinleştir')
                        ->columnSpanFull(),
                    Select::make('captcha_provider')
                        ->label('Sağlayıcı')
                        ->options([
                            'native' => 'Yerleşik (anahtarsız – basit matematik sorusu)',
                            'turnstile' => 'Cloudflare Turnstile (önerilir)',
                            'recaptcha' => 'Google reCAPTCHA v2',
                        ])
                        ->default('native')
                        ->native(false)
                        ->helperText('Turnstile/reCAPTCHA için aşağıdaki Site ve Gizli anahtarları girilmelidir. "Yerleşik" hiçbir anahtar gerektirmez ve hemen çalışır.')
                        ->columnSpanFull(),
                    TextInput::make('captcha_site_key')
                        ->label('Site Anahtarı (Site Key)')
                        ->maxLength(255)
                        ->helperText('Yalnızca Turnstile / reCAPTCHA için gereklidir.'),
                    TextInput::make('captcha_secret_key')
                        ->label('Gizli Anahtar (Secret Key)')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->helperText('Yalnızca Turnstile / reCAPTCHA için gereklidir.'),
                ])->columns(2),

            Section::make('Nerede uygulansın?')
                ->description('Captcha’nın gösterileceği formları seçin. Giriş yapmış kullanıcılara captcha sorulmaz.')
                ->schema([
                    Toggle::make('captcha_ctx_login')->label('Giriş'),
                    Toggle::make('captcha_ctx_register')->label('Kayıt'),
                    Toggle::make('captcha_ctx_checkout')->label('Ödeme / Sipariş'),
                    Toggle::make('captcha_ctx_password')->label('Şifremi unuttum'),
                    Toggle::make('captcha_ctx_contact')->label('İletişim'),
                ])->columns(3),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('security-form')
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

            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? ''),
                    'group' => 'security',
                    'type' => is_bool($value) ? 'boolean' : 'text',
                    'label' => $key,
                ]
            );
        }

        SettingsService::clearCache();

        Notification::make()->title('Güvenlik ayarları kaydedildi')->success()->send();
    }

    /** @return list<string> */
    protected function settingKeys(): array
    {
        return [
            'registration_enabled',
            'captcha_enabled',
            'captcha_provider',
            'captcha_site_key',
            'captcha_secret_key',
            'captcha_ctx_login',
            'captcha_ctx_register',
            'captcha_ctx_checkout',
            'captcha_ctx_password',
            'captcha_ctx_contact',
        ];
    }

    /** @return list<string> */
    protected function booleanKeys(): array
    {
        return [
            'registration_enabled',
            'captcha_enabled',
            'captcha_ctx_login',
            'captcha_ctx_register',
            'captcha_ctx_checkout',
            'captcha_ctx_password',
            'captcha_ctx_contact',
        ];
    }
}
