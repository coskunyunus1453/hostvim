<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\CacheService;
use App\Services\PageContentService;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageLandingPages extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Site İçeriği';

    protected static ?string $navigationLabel = 'Hosting & Sunucu Sayfaları';

    protected static ?string $title = 'Hosting & Sunucu Sayfa İçerikleri';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $slug = 'tanitim-sayfalari';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(PageContentService $content): void
    {
        $this->form->fill([
            'hosting' => $content->hosting(),
            'cloud' => $content->cloud(),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('pageTabs')
                ->tabs([
                    Tab::make('hosting')
                        ->label('Web Hosting (/hosting)')
                        ->icon(Heroicon::OutlinedServerStack)
                        ->schema($this->pageSchema('hosting')),
                    Tab::make('cloud')
                        ->label('Bulut Sunucu (/sunucu)')
                        ->icon(Heroicon::OutlinedCloud)
                        ->schema($this->pageSchema('cloud')),
                ])
                ->persistTabInQueryString('sayfa'),
        ]);
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    protected function pageSchema(string $p): array
    {
        return [
            Section::make('Üst Bölüm (Hero)')
                ->description('Sayfanın en üstündeki büyük başlık alanı.')
                ->schema([
                    TextInput::make("{$p}.hero.badge")->label('Rozet (küçük üst etiket)')->maxLength(120),
                    TextInput::make("{$p}.hero.title")->label('Başlık (H1)')->required()->maxLength(160)->columnSpanFull(),
                    Textarea::make("{$p}.hero.subtitle")->label('Alt açıklama')->rows(2)->columnSpanFull(),
                    TextInput::make("{$p}.hero.primary_label")->label('1. Buton metni'),
                    TextInput::make("{$p}.hero.primary_url")->label('1. Buton bağlantısı')->placeholder('#paketler'),
                    TextInput::make("{$p}.hero.secondary_label")->label('2. Buton metni'),
                    TextInput::make("{$p}.hero.secondary_url")->label('2. Buton bağlantısı')->placeholder('/iletisim'),
                ])->columns(2)->collapsible(),

            Section::make('SEO')
                ->description('Arama motorları için başlık ve açıklama.')
                ->schema([
                    TextInput::make("{$p}.seo.title")->label('Meta başlık')->maxLength(160)->columnSpanFull(),
                    Textarea::make("{$p}.seo.description")->label('Meta açıklama')->rows(2)->maxLength(320)->columnSpanFull(),
                ])->collapsed(),

            Section::make('Paketler Bölümü Başlığı')
                ->description('Paket kartlarının üstündeki başlık ve açıklama. Paketler "Ürünler"den otomatik gelir.')
                ->schema([
                    TextInput::make("{$p}.intro.title")->label('Başlık')->maxLength(160)->columnSpanFull(),
                    Textarea::make("{$p}.intro.text")->label('Açıklama')->rows(2)->columnSpanFull(),
                ])->collapsed(),

            Section::make('Neden Biz? (Özellik Kartları)')
                ->schema([
                    Repeater::make("{$p}.features")
                        ->label('Özellikler')
                        ->schema([
                            TextInput::make('icon')->label('Simge (emoji)')->maxLength(8)->columnSpan(1),
                            TextInput::make('title')->label('Başlık')->required()->columnSpan(3),
                            Textarea::make('text')->label('Açıklama')->rows(2)->columnSpanFull(),
                        ])
                        ->columns(4)
                        ->reorderableWithDragAndDrop()
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Yeni özellik')
                        ->addActionLabel('Özellik ekle')
                        ->columnSpanFull(),
                ])->collapsed(),

            Section::make('Altyapı & Teknoloji')
                ->schema([
                    Repeater::make("{$p}.tech")
                        ->label('Teknoloji blokları')
                        ->schema([
                            TextInput::make('title')->label('Başlık')->required()->columnSpanFull(),
                            Textarea::make('text')->label('Açıklama')->rows(2)->columnSpanFull(),
                        ])
                        ->reorderableWithDragAndDrop()
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Yeni blok')
                        ->addActionLabel('Teknoloji bloğu ekle')
                        ->columnSpanFull(),
                ])->collapsed(),

            Section::make('Hizmet Detayları (SEO İçerik)')
                ->description('Uzun, SEO odaklı açıklama blokları. Paragrafları boş satır ile ayırın.')
                ->schema([
                    Repeater::make("{$p}.details")
                        ->label('Detay blokları')
                        ->schema([
                            TextInput::make('title')->label('Başlık')->required()->columnSpanFull(),
                            Textarea::make('body')->label('İçerik (paragrafları boş satırla ayırın)')->rows(5)->columnSpanFull(),
                        ])
                        ->reorderableWithDragAndDrop()
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Yeni detay')
                        ->addActionLabel('Detay bloğu ekle')
                        ->columnSpanFull(),
                ])->collapsed(),

            Section::make('Sıkça Sorulan Sorular')
                ->description('Sayfa altındaki SSS bölümü. Ayrıca Google için yapısal veri (FAQ schema) üretir.')
                ->schema([
                    Repeater::make("{$p}.faqs")
                        ->label('Sorular')
                        ->schema([
                            TextInput::make('q')->label('Soru')->required()->columnSpanFull(),
                            Textarea::make('a')->label('Cevap')->rows(3)->required()->columnSpanFull(),
                        ])
                        ->reorderableWithDragAndDrop()
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state): ?string => $state['q'] ?? 'Yeni soru')
                        ->addActionLabel('Soru ekle')
                        ->columnSpanFull(),
                ])->collapsed(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('landing-pages-form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Kaydet')->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(CacheService $cache): void
    {
        $data = $this->form->getState();

        foreach (['hosting' => 'hosting_page', 'cloud' => 'cloud_page'] as $tab => $key) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'pages',
                    'value' => json_encode($data[$tab] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'type' => 'json',
                    'label' => $key,
                ]
            );
        }

        SettingsService::clearCache();
        $cache->clearPageCacheForPaths(['hosting', 'sunucu']);

        Notification::make()->title('Sayfa içerikleri kaydedildi')->success()->send();
    }
}
