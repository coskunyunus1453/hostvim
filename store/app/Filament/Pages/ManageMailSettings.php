<?php

namespace App\Filament\Pages;

use App\Mail\TemplatedMail;
use App\Models\SiteSetting;
use App\Services\OutboundMailConfigurator;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ManageMailSettings extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Tasarım & Yapılandırma';

    protected static ?string $navigationLabel = 'E-posta (SMTP)';

    protected static ?string $title = 'Giden E-posta Ayarları';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $slug = 'eposta-ayarlari';

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

        if (($settings['outbound_mail.driver'] ?? '') === '') {
            $settings['outbound_mail.driver'] = config('mail.default', 'smtp');
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
            Section::make('Gönderim yöntemi')
                ->description('Sipariş onayı, hoş geldin ve havale bildirimleri bu ayarlarla gönderilir. Kuyruk worker çalışıyor olmalıdır.')
                ->schema([
                    Select::make('outbound_mail.driver')
                        ->label('Sürücü')
                        ->options([
                            'smtp' => 'SMTP',
                            'sendmail' => 'Sendmail (yerel Postfix)',
                            'log' => 'Log (test — e-posta gitmez)',
                        ])
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    TextInput::make('outbound_mail.smtp_host')
                        ->label('SMTP sunucusu')
                        ->placeholder('smtp.ornek.com')
                        ->visible(fn ($get) => $get('outbound_mail.driver') === 'smtp'),
                    TextInput::make('outbound_mail.smtp_port')
                        ->label('Port')
                        ->numeric()
                        ->default(587)
                        ->visible(fn ($get) => $get('outbound_mail.driver') === 'smtp'),
                    Select::make('outbound_mail.smtp_encryption')
                        ->label('Şifreleme')
                        ->options([
                            '' => 'Yok / otomatik',
                            'tls' => 'TLS (STARTTLS — 587)',
                            'ssl' => 'SSL (465)',
                        ])
                        ->visible(fn ($get) => $get('outbound_mail.driver') === 'smtp'),
                    TextInput::make('outbound_mail.smtp_username')
                        ->label('Kullanıcı adı')
                        ->visible(fn ($get) => $get('outbound_mail.driver') === 'smtp'),
                    TextInput::make('outbound_mail.smtp_password')
                        ->label('Şifre')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn ($state) => filled($state))
                        ->helperText('Boş bırakırsanız kayıtlı şifre korunur.')
                        ->visible(fn ($get) => $get('outbound_mail.driver') === 'smtp'),
                    TextInput::make('outbound_mail.from_address')
                        ->label('Gönderen e-posta')
                        ->email()
                        ->required(),
                    TextInput::make('outbound_mail.from_name')
                        ->label('Gönderen adı')
                        ->required(),
                ])->columns(2),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('mail-form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('testMail')
                            ->label('Test e-postası gönder')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->color('gray')
                            ->action(fn () => $this->sendTestMail()),
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

            if ($key === 'outbound_mail.smtp_password' && ! filled($value)) {
                continue;
            }

            $stored = $key === 'outbound_mail.smtp_password' && filled($value)
                ? encrypt((string) $value)
                : (string) ($value ?? '');

            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $stored,
                    'group' => 'mail',
                    'type' => 'text',
                    'label' => $key,
                ]
            );
        }

        SettingsService::clearCache();
        OutboundMailConfigurator::apply();
        app(\App\Services\Panel\PanelSettingsSyncService::class)->syncMailSafe();

        Notification::make()->title('E-posta ayarları kaydedildi')->success()->send();
    }

    protected function sendTestMail(): void
    {
        $this->save();
        OutboundMailConfigurator::apply();

        $to = Auth::user()?->email;
        if (! is_string($to) || $to === '') {
            Notification::make()->title('Giriş yapan kullanıcının e-postası bulunamadı')->danger()->send();

            return;
        }

        try {
            Mail::to($to)->send(new TemplatedMail(
                'HostVim Store — Test E-postası',
                '<p>Bu bir test mesajıdır. SMTP ayarlarınız çalışıyor.</p><p>'.e(now()->format('d.m.Y H:i')).'</p>',
            ));

            Notification::make()
                ->title('Test e-postası gönderildi')
                ->body($to.' adresini kontrol edin (spam klasörü dahil).')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Test e-postası gönderilemedi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /** @return array<string, string> */
    protected function defaults(): array
    {
        return [
            'outbound_mail.driver' => (string) config('mail.default', 'smtp'),
            'outbound_mail.smtp_host' => (string) config('mail.mailers.smtp.host', ''),
            'outbound_mail.smtp_port' => (string) config('mail.mailers.smtp.port', 587),
            'outbound_mail.smtp_encryption' => '',
            'outbound_mail.smtp_username' => (string) (config('mail.mailers.smtp.username') ?? ''),
            'outbound_mail.from_address' => (string) config('mail.from.address', ''),
            'outbound_mail.from_name' => (string) config('mail.from.name', 'HostVim'),
        ];
    }

    /** @return list<string> */
    protected function settingKeys(): array
    {
        return [
            'outbound_mail.driver',
            'outbound_mail.smtp_host',
            'outbound_mail.smtp_port',
            'outbound_mail.smtp_encryption',
            'outbound_mail.smtp_username',
            'outbound_mail.smtp_password',
            'outbound_mail.from_address',
            'outbound_mail.from_name',
        ];
    }
}
