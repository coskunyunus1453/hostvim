<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\EInvoice\EInvoiceResolver;
use App\Services\EInvoice\EInvoiceSettings;
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

class ManageEInvoiceSettings extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Tasarım & Yapılandırma';

    protected static ?string $navigationLabel = 'E-Fatura';

    protected static ?string $title = 'E-Fatura / e-Arşiv Ayarları';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $slug = 'efatura-ayarlari';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var list<string> */
    private const BOOL_KEYS = [
        'e_invoice.test_mode',
        'e_invoice.auto_draft',
        'e_invoice.auto_issue',
        'e_invoice.price_includes_tax',
    ];

    public function mount(): void
    {
        $settings = SiteSetting::query()
            ->where('group', EInvoiceSettings::GROUP)
            ->pluck('value', 'key')
            ->toArray();

        $data = [];
        foreach ($this->settingKeys() as $key) {
            if (in_array($key, EInvoiceSettings::SECRET_KEYS, true)) {
                $data[$key] = null; // gizli alanlar boş gösterilir
                $data[$key.'__set'] = ($settings[$key] ?? '') !== '';

                continue;
            }
            if (in_array($key, self::BOOL_KEYS, true)) {
                $data[$key] = isset($settings[$key])
                    ? filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN)
                    : ($this->defaults()[$key] ?? false);

                continue;
            }
            $data[$key] = $settings[$key] ?? ($this->defaults()[$key] ?? '');
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
            Section::make('Genel')
                ->description('E-fatura entegrasyonunu açın ve davranışını belirleyin.')
                ->schema([
                    Select::make('e_invoice.provider')
                        ->label('Sağlayıcı')
                        ->options(EInvoiceResolver::options())
                        ->default('none')
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    Toggle::make('e_invoice.test_mode')
                        ->label('Test ortamı (sandbox)')
                        ->helperText('Açıkken faturalar sağlayıcının test ortamına gönderilir.')
                        ->default(true),
                    Toggle::make('e_invoice.auto_draft')
                        ->label('Ödeme sonrası otomatik taslak/proforma PDF oluştur')
                        ->default(true),
                    Toggle::make('e_invoice.auto_issue')
                        ->label('Ödeme sonrası otomatik resmi e-Fatura kes')
                        ->helperText('Kapalıyken faturalar admin panelden manuel kesilir. Kontör tüketir.')
                        ->default(false),
                    Toggle::make('e_invoice.price_includes_tax')
                        ->label('Fiyatlara KDV dahil')
                        ->helperText('Açıkken sipariş tutarından KDV ayrıştırılır.')
                        ->default(true),
                    TextInput::make('e_invoice.tax_rate')
                        ->label('KDV oranı (%)')
                        ->numeric()->minValue(0)->maxValue(100)->step(0.01)->suffix('%')->default(20),
                ])->columns(2),

            Section::make('Satıcı firma bilgileri')
                ->description('Faturada görünecek kendi firma bilgileriniz.')
                ->schema([
                    TextInput::make('e_invoice.company_title')->label('Ünvan / Firma adı')->columnSpanFull(),
                    TextInput::make('e_invoice.company_tax_office')->label('Vergi dairesi'),
                    TextInput::make('e_invoice.company_tax_number')->label('VKN / TCKN'),
                    TextInput::make('e_invoice.company_phone')->label('Telefon'),
                    TextInput::make('e_invoice.company_email')->label('E-posta')->email(),
                    Textarea::make('e_invoice.company_address')->label('Adres')->rows(2)->columnSpanFull(),
                ])->columns(2),

            Section::make('Nilvera')
                ->visible(fn ($get) => $get('e_invoice.provider') === 'nilvera')
                ->schema([
                    $this->secretField('e_invoice.nilvera_api_key', 'API Anahtarı (Bearer Token)'),
                ]),

            Section::make('Paraşüt')
                ->visible(fn ($get) => $get('e_invoice.provider') === 'parasut')
                ->schema([
                    TextInput::make('e_invoice.parasut_client_id')->label('Client ID'),
                    $this->secretField('e_invoice.parasut_client_secret', 'Client Secret'),
                    TextInput::make('e_invoice.parasut_username')->label('Kullanıcı adı (e-posta)'),
                    $this->secretField('e_invoice.parasut_password', 'Şifre'),
                    TextInput::make('e_invoice.parasut_company_id')->label('Şirket ID (company_id)'),
                ])->columns(2),

            Section::make('Mükellef')
                ->visible(fn ($get) => $get('e_invoice.provider') === 'mukellef')
                ->schema([
                    $this->secretField('e_invoice.mukellef_api_key', 'API Anahtarı'),
                ]),
        ]);
    }

    private function secretField(string $key, string $label): TextInput
    {
        return TextInput::make($key)
            ->label($label)
            ->password()
            ->revealable()
            ->dehydrated(fn ($state) => filled($state))
            ->helperText('Kayıtlıysa boş bırakın; değiştirmek için yeniden girin.');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('einvoice-form')
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

        foreach ($this->settingKeys() as $key) {
            $isSecret = in_array($key, EInvoiceSettings::SECRET_KEYS, true);
            $value = $data[$key] ?? null;

            if ($isSecret && ! filled($value)) {
                continue; // mevcut gizli değeri koru
            }

            if ($isSecret) {
                $stored = encrypt((string) $value);
            } elseif (in_array($key, self::BOOL_KEYS, true)) {
                $stored = $value ? '1' : '0';
            } else {
                $stored = (string) ($value ?? '');
            }

            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $stored, 'group' => EInvoiceSettings::GROUP, 'type' => 'text', 'label' => $key],
            );
        }

        EInvoiceSettings::clearCache();

        Notification::make()->title('E-fatura ayarları kaydedildi')->success()->send();
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'e_invoice.provider' => 'none',
            'e_invoice.test_mode' => true,
            'e_invoice.auto_draft' => true,
            'e_invoice.auto_issue' => false,
            'e_invoice.price_includes_tax' => true,
            'e_invoice.tax_rate' => '20',
        ];
    }

    /** @return list<string> */
    protected function settingKeys(): array
    {
        return [
            'e_invoice.provider',
            'e_invoice.test_mode',
            'e_invoice.auto_draft',
            'e_invoice.auto_issue',
            'e_invoice.price_includes_tax',
            'e_invoice.tax_rate',
            'e_invoice.company_title',
            'e_invoice.company_tax_office',
            'e_invoice.company_tax_number',
            'e_invoice.company_phone',
            'e_invoice.company_email',
            'e_invoice.company_address',
            'e_invoice.nilvera_api_key',
            'e_invoice.parasut_client_id',
            'e_invoice.parasut_client_secret',
            'e_invoice.parasut_username',
            'e_invoice.parasut_password',
            'e_invoice.parasut_company_id',
            'e_invoice.mukellef_api_key',
        ];
    }
}
