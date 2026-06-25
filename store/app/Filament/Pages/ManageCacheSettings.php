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

class ManageCacheSettings extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Tasarım & Yapılandırma';

    protected static ?string $navigationLabel = 'Önbellek Ayarları';

    protected static ?string $title = 'Önbellek & Performans';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $slug = 'onbellek-ayarlari';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $keys = $this->settingKeys();
        $settings = SiteSetting::whereIn('key', $keys)->pluck('value', 'key')->toArray();

        foreach ($keys as $key) {
            $settings[$key] ??= '';
        }

        foreach ($this->booleanKeys() as $key) {
            if (isset($settings[$key])) {
                $settings[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
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
            Section::make('Sunucu Önbelleği')->schema([
                Toggle::make('cache_page_enabled')->label('Sayfa HTML önbelleği')->default(true)->helperText('Sepet, ödeme ve oturumlu sayfalar otomatik hariç tutulur'),
                TextInput::make('cache_page_ttl')->label('Sayfa önbellek süresi (sn)')->numeric()->minValue(60)->default(3600)->helperText('Örn: 3600 = 1 saat'),
                Toggle::make('cache_query_enabled')->label('Veritabanı sorgu önbelleği')->default(true)->helperText('Menü, kategori ve layout verileri'),
                TextInput::make('cache_query_ttl')->label('Sorgu önbellek süresi (sn)')->numeric()->minValue(60)->default(1800),
            ])->columns(2),

            Section::make('Tarayıcı & Sıkıştırma')->schema([
                Toggle::make('cache_browser_enabled')->label('Tarayıcı Cache-Control başlıkları')->default(true),
                TextInput::make('cache_browser_html_ttl')->label('HTML tarayıcı süresi (sn)')->numeric()->minValue(0)->default(300)->helperText('0 = her seferinde doğrula'),
                TextInput::make('cache_browser_assets_ttl')->label('CSS/JS/görsel süresi (sn)')->numeric()->minValue(3600)->default(31536000)->helperText('Örn: 31536000 = 1 yıl'),
                Toggle::make('cache_gzip_enabled')->label('Gzip sıkıştırma (PHP)')->default(false)->helperText('Nginx gzip açıksa kapalı bırakın; PHP gzip TTFB\'yi uzatır'),
            ])->columns(2),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('cache-form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('clearCache')
                            ->label('Tüm Önbelleği Temizle')
                            ->color('warning')
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->requiresConfirmation()
                            ->modalHeading('Önbellek temizlensin mi?')
                            ->modalDescription('Sayfa, uygulama, view ve sitemap önbelleği silinir.')
                            ->action(fn () => $this->clearAllCache()),
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
                    'group' => 'cache',
                    'type' => is_bool($value) ? 'boolean' : 'number',
                    'label' => $key,
                ]
            );
        }

        SettingsService::clearCache();

        Notification::make()->title('Önbellek ayarları kaydedildi')->success()->send();
    }

    protected function clearAllCache(): void
    {
        $cleared = app(CacheService::class)->clearAll();

        Notification::make()
            ->title('Önbellek temizlendi')
            ->body(implode(' · ', $cleared))
            ->success()
            ->send();
    }

    /** @return list<string> */
    protected function settingKeys(): array
    {
        return [
            'cache_page_enabled', 'cache_page_ttl',
            'cache_query_enabled', 'cache_query_ttl',
            'cache_browser_enabled', 'cache_browser_html_ttl', 'cache_browser_assets_ttl',
            'cache_gzip_enabled',
        ];
    }

    /** @return list<string> */
    protected function booleanKeys(): array
    {
        return [
            'cache_page_enabled', 'cache_query_enabled',
            'cache_browser_enabled', 'cache_gzip_enabled',
        ];
    }
}
