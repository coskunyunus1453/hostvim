<?php

namespace App\Filament\Pages;

use App\Http\Controllers\SitemapController;
use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SiteSetting;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page as FilamentPage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class ManageSitemap extends FilamentPage
{
    protected static string|\UnitEnum|null $navigationGroup = 'Tasarım & Yapılandırma';

    protected static ?string $navigationLabel = 'Sitemap';

    protected static ?string $title = 'Sitemap (XML) Yönetimi';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $slug = 'sitemap';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, int> */
    public array $counts = [];

    public int $totalUrls = 0;

    /**
     * @return array<string, string> Bölüm anahtarı => görünen ad
     */
    public static function sections(): array
    {
        return [
            'home' => 'Ana Sayfa',
            'products' => 'Hosting / Ürünler',
            'categories' => 'Ürün Kategorileri',
            'domain' => 'Domain',
            'blog' => 'Blog Yazıları',
            'pages' => 'Sayfalar (CMS)',
            'contact' => 'İletişim',
        ];
    }

    public function mount(): void
    {
        $keys = $this->settingKeys();
        $settings = SiteSetting::whereIn('key', $keys)->pluck('value', 'key')->toArray();

        $settings['seo_sitemap_enabled'] = filter_var($settings['seo_sitemap_enabled'] ?? '1', FILTER_VALIDATE_BOOLEAN);

        foreach (array_keys(static::sections()) as $key) {
            [$defEnabled, $defFreq, $defPri] = SitemapController::SECTION_DEFAULTS[$key];
            $settings["sitemap_{$key}_enabled"] = filter_var($settings["sitemap_{$key}_enabled"] ?? ($defEnabled ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
            $settings["sitemap_{$key}_changefreq"] = $settings["sitemap_{$key}_changefreq"] ?? $defFreq;
            $settings["sitemap_{$key}_priority"] = $settings["sitemap_{$key}_priority"] ?? $defPri;
        }

        $this->computeCounts();
        $this->form->fill($settings);
    }

    private function computeCounts(): void
    {
        $this->counts = [
            'home' => 1,
            'products' => 1 + Product::where('is_active', true)->where('no_index', false)->count(),
            'categories' => ProductCategory::where('is_active', true)->where('no_index', false)->count(),
            'domain' => 1,
            'blog' => 1 + BlogPost::where('is_published', true)->where('no_index', false)->count(),
            'pages' => Page::where('is_published', true)->where('no_index', false)->count(),
            'contact' => 1,
        ];

        $total = 0;
        foreach (array_keys(static::sections()) as $key) {
            [$defEnabled] = SitemapController::SECTION_DEFAULTS[$key];
            $enabled = filter_var(
                SiteSetting::where('key', "sitemap_{$key}_enabled")->value('value') ?? ($defEnabled ? '1' : '0'),
                FILTER_VALIDATE_BOOLEAN
            );
            if ($enabled) {
                $total += $this->counts[$key] ?? 0;
            }
        }
        $this->totalUrls = $total;
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $sectionFields = [];
        foreach (static::sections() as $key => $label) {
            $count = $this->counts[$key] ?? 0;
            $sectionFields[] = Fieldset::make($label.' ('.$count.' URL)')->schema([
                Toggle::make("sitemap_{$key}_enabled")->label('Sitemap\'e dahil et')->inline(false),
                Select::make("sitemap_{$key}_changefreq")->label('Güncelleme sıklığı')->options($this->changefreqOptions())->native(false),
                TextInput::make("sitemap_{$key}_priority")->label('Öncelik (0.0 – 1.0)')->numeric()->minValue(0)->maxValue(1)->step('0.1'),
            ])->columns(3);
        }

        return $schema->components([
            Section::make('Genel')->schema([
                Toggle::make('seo_sitemap_enabled')->label('Sitemap Aktif')
                    ->helperText('Kapalıyken /sitemap.xml 404 döner ve robots.txt\'den çıkarılır.'),
                Placeholder::make('sitemap_url')->label('Sitemap adresi')
                    ->content(new HtmlString('<a href="'.e(url('/sitemap.xml')).'" target="_blank" class="text-primary-600 underline">'.e(url('/sitemap.xml')).'</a>')),
                Placeholder::make('total_urls')->label('Toplam URL (aktif bölümler)')
                    ->content((string) $this->totalUrls),
            ])->columns(3),

            Section::make('Bölümler')
                ->description('Her içerik türünü sitemap\'e dahil edin ve SEO için öncelik/sıklık değerlerini ayarlayın.')
                ->schema($sectionFields),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('sitemap-form')
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_sitemap')
                ->label('Sitemap\'i Aç')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(url('/sitemap.xml'), shouldOpenInNewTab: true),
            Action::make('refresh_cache')
                ->label('Önbelleği Yenile')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (): void {
                    Cache::forget('sitemap_xml');
                    $this->computeCounts();
                    Notification::make()->title('Sitemap önbelleği temizlendi — bir sonraki istekte yeniden üretilecek.')->success()->send();
                }),
            Action::make('search_console')
                ->label('Search Console')
                ->icon(Heroicon::OutlinedMagnifyingGlass)
                ->color('gray')
                ->url('https://search.google.com/search-console', shouldOpenInNewTab: true),
        ];
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
                    'group' => 'seo',
                    'type' => is_bool($value) ? 'boolean' : 'text',
                    'label' => $key,
                ]
            );
        }

        SettingsService::clearCache();
        Cache::forget('sitemap_xml');
        $this->computeCounts();

        Notification::make()->title('Sitemap ayarları kaydedildi')->success()->send();
    }

    /** @return array<string, string> */
    protected function changefreqOptions(): array
    {
        return [
            'always' => 'always (her zaman)',
            'hourly' => 'hourly (saatlik)',
            'daily' => 'daily (günlük)',
            'weekly' => 'weekly (haftalık)',
            'monthly' => 'monthly (aylık)',
            'yearly' => 'yearly (yıllık)',
            'never' => 'never (asla)',
        ];
    }

    /** @return list<string> */
    protected function settingKeys(): array
    {
        $keys = ['seo_sitemap_enabled'];
        foreach (array_keys(static::sections()) as $key) {
            $keys[] = "sitemap_{$key}_enabled";
            $keys[] = "sitemap_{$key}_changefreq";
            $keys[] = "sitemap_{$key}_priority";
        }

        return $keys;
    }
}
