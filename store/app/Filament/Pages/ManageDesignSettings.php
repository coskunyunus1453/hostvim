<?php

namespace App\Filament\Pages;

use App\Models\HeroSection;
use App\Models\SiteSetting;
use App\Services\CacheService;
use App\Services\SettingsService;
use App\Services\ThemePresetService;
use App\Support\ThemePresets;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageDesignSettings extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Tasarım & Yapılandırma';

    protected static ?string $navigationLabel = 'Tasarım & Tema';

    protected static ?string $title = 'Ön Yüz Tasarımı';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $slug = 'tasarim-ayarlari';

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

        $hero = HeroSection::firstOrCreate(
            ['page' => 'home'],
            [
                'title' => 'İşinizi <span class="text-[#C2410C]">güçlü altyapı</span> ile büyütün',
                'subtitle' => 'Kurumsal Hosting Çözümleri',
                'description' => 'NVMe SSD hosting, yüksek performanslı VPS/VDS, dedicated sunucu ve domain hizmetleri.',
                'cta_text' => 'Paketleri Keşfet',
                'cta_url' => '/urunler',
                'secondary_cta_text' => 'Uzmanla Konuş',
                'secondary_cta_url' => '/iletisim',
                'layout_variant' => 'split',
                'stat_1_value' => '99.9%',
                'stat_1_label' => 'Uptime Garantisi',
                'stat_2_value' => '7/24',
                'stat_2_label' => 'Teknik Destek',
                'stat_3_value' => '15dk',
                'stat_3_label' => 'Ortalama Yanıt',
                'is_active' => true,
            ],
        );

        $settings['hero'] = $hero->only([
            'layout_variant', 'title', 'subtitle', 'description',
            'cta_text', 'cta_url', 'secondary_cta_text', 'secondary_cta_url',
            'image', 'stat_1_value', 'stat_1_label', 'stat_2_value', 'stat_2_label',
            'stat_3_value', 'stat_3_label', 'is_active',
        ]);
        $settings['hero']['is_active'] = (bool) ($settings['hero']['is_active'] ?? true);

        $this->form->fill($settings);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('designTabs')
                ->tabs([
                    Tab::make('presets')->label('Tema Paketleri')->icon(Heroicon::OutlinedSquares2x2)->schema([
                        Section::make('Hazır tema paketleri')
                            ->description('11 farklı hosting/sunucu teması. Seçip uygulayın; renkler, yazı tipi, header ve footer otomatik güncellenir. Ana tema mevcut görünümünüzü korur.')
                            ->schema([
                                Select::make('design_theme_preset')
                                    ->label('Tema paketi')
                                    ->options(ThemePresets::options())
                                    ->default('hostvim-main')
                                    ->helperText('Özel renkler kullanıyorsanız "Özel (manuel renkler)" seçin.')
                                    ->columnSpanFull(),
                                TextInput::make('design_font_family')
                                    ->label('Yazı tipi')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Tema uygulandığında otomatik ayarlanır. Diğer sekmelerden renk düzenlerseniz tema "Özel" olur.'),
                            ])
                            ->footerActions([
                                Action::make('applyPreset')
                                    ->label('Seçili temayı uygula')
                                    ->icon(Heroicon::OutlinedArrowPath)
                                    ->color('primary')
                                    ->requiresConfirmation()
                                    ->modalHeading('Tema paketini uygula')
                                    ->modalDescription('Seçili temanın renkleri, yazı tipi, header ve footer ayarları kaydedilecek. Devam edilsin mi?')
                                    ->action(fn () => $this->applySelectedPreset()),
                            ]),
                    ]),
                    Tab::make('theme')->label('Tema Modu')->icon(Heroicon::OutlinedSun)->schema([
                        Section::make('Gece / Gündüz Modu')->schema([
                            Select::make('design_theme_mode')
                                ->label('Varsayılan mod')
                                ->options([
                                    'system' => 'Sistem tercihi (otomatik)',
                                    'light' => 'Gündüz modu',
                                    'dark' => 'Gece modu',
                                ])
                                ->default('system')
                                ->helperText('Ziyaretçi siteye ilk girdiğinde hangi mod kullanılsın'),
                            Toggle::make('design_theme_toggle')
                                ->label('Tema değiştirme butonu göster')
                                ->default(true)
                                ->helperText('Header\'da gece/gündüz geçiş düğmesi'),
                        ])->columns(2),
                    ]),
                    Tab::make('light')->label('Gündüz Renkleri')->icon(Heroicon::OutlinedSwatch)->schema([
                        $this->colorSection('Açık tema renkleri', 'design_light'),
                    ]),
                    Tab::make('dark')->label('Gece Renkleri')->icon(Heroicon::OutlinedMoon)->schema([
                        $this->colorSection('Koyu tema renkleri', 'design_dark'),
                    ]),
                    Tab::make('header')->label('Header')->icon(Heroicon::OutlinedBars3)->schema([
                        Section::make('Üst menü tasarımı')->schema([
                            Select::make('design_header_style')
                                ->label('Header stili')
                                ->options([
                                    'glass' => 'Cam efekt (modern, bulanık)',
                                    'default' => 'Klasik beyaz/koyu',
                                    'solid' => 'Düz marka rengi',
                                ])
                                ->default('glass'),
                            Toggle::make('design_header_sticky')->label('Yapışkan header')->default(true),
                            Toggle::make('design_header_blur')->label('Bulanıklık efekti')->default(true),
                            Toggle::make('design_header_border')->label('Alt çizgi göster')->default(true),
                            ColorPicker::make('design_header_bg_light')->label('Header arka plan (gündüz)'),
                            ColorPicker::make('design_header_bg_dark')->label('Header arka plan (gece)'),
                        ])->columns(2),
                    ]),
                    Tab::make('footer')->label('Footer')->icon(Heroicon::OutlinedRectangleStack)->schema([
                        Section::make('Alt bilgi tasarımı')->schema([
                            Select::make('design_footer_style')
                                ->label('Footer stili')
                                ->options([
                                    'default' => 'Klasik 4 sütun',
                                    'minimal' => 'Minimal tek satır',
                                    'gradient' => 'Gradyan modern',
                                ])
                                ->default('default'),
                            Toggle::make('design_footer_show_stats')->label('Uptime rozeti göster')->default(true),
                            ColorPicker::make('design_footer_bg_light')->label('Footer arka plan (gündüz)'),
                            ColorPicker::make('design_footer_bg_dark')->label('Footer arka plan (gece)'),
                            Textarea::make('footer_text')->label('Footer açıklama metni')->rows(3)->columnSpanFull(),
                        ])->columns(2),
                    ]),
                    Tab::make('hero')->label('Hero Alanı')->icon(Heroicon::OutlinedRocketLaunch)->schema([
                        Section::make('Landing hero tasarımı')->description('Ana sayfa üst bölümü — 3 farklı modern şablon')->schema([
                            Select::make('hero.layout_variant')
                                ->label('Hero şablonu')
                                ->options([
                                    'split' => 'Split — Yazı + animasyonlu sunucu grafiği',
                                    'centered' => 'Centered — Başlık + yan grafik',
                                    'aurora' => 'Aurora — Mesh gradyan + cam panel grafik',
                                ])
                                ->default('split')
                                ->required()
                                ->columnSpanFull(),
                            TextInput::make('hero.subtitle')->label('Üst rozet metni')->columnSpanFull(),
                            RichEditor::make('hero.title')->label('Ana başlık (HTML destekli)')->columnSpanFull(),
                            Textarea::make('hero.description')->label('Açıklama')->rows(3)->columnSpanFull(),
                            TextInput::make('hero.cta_text')->label('Birincil buton'),
                            TextInput::make('hero.cta_url')->label('Birincil URL')->placeholder('/urunler'),
                            TextInput::make('hero.secondary_cta_text')->label('İkincil buton'),
                            TextInput::make('hero.secondary_cta_url')->label('İkincil URL')->placeholder('/iletisim'),
                            FileUpload::make('hero.image')->label('Hero görseli')->image()->directory('hero')->columnSpanFull(),
                            Toggle::make('hero.is_active')->label('Hero aktif')->default(true),
                        ])->columns(2),
                        Section::make('İstatistikler')->schema([
                            TextInput::make('hero.stat_1_value')->label('İstatistik 1 değer')->placeholder('99.9%'),
                            TextInput::make('hero.stat_1_label')->label('İstatistik 1 etiket')->placeholder('Uptime'),
                            TextInput::make('hero.stat_2_value')->label('İstatistik 2 değer'),
                            TextInput::make('hero.stat_2_label')->label('İstatistik 2 etiket'),
                            TextInput::make('hero.stat_3_value')->label('İstatistik 3 değer'),
                            TextInput::make('hero.stat_3_label')->label('İstatistik 3 etiket'),
                        ])->columns(2),
                    ]),
                ])
                ->persistTabInQueryString('design'),
        ]);
    }

    protected function colorSection(string $title, string $prefix): Section
    {
        $map = [
            'primary' => 'Ana renk (turuncu)',
            'primary_hover' => 'Ana renk hover',
            'secondary' => 'İkincil renk (yeşil)',
            'bg' => 'Sayfa arka planı',
            'surface' => 'Yüzey / bölüm arka planı',
            'surface_elevated' => 'Kart arka planı',
            'text' => 'Ana metin',
            'text_muted' => 'Soluk metin',
            'link' => 'Bağlantı rengi',
            'border' => 'Kenarlık rengi',
        ];

        $fields = [];
        foreach ($map as $suffix => $label) {
            $fields[] = ColorPicker::make("{$prefix}_{$suffix}")->label($label);
        }

        return Section::make($title)->schema($fields)->columns(3);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('design-form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Tasarımı Kaydet')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function applySelectedPreset(): void
    {
        $presetId = (string) ($this->data['design_theme_preset'] ?? 'hostvim-main');

        if ($presetId === 'custom') {
            Notification::make()
                ->title('Özel tema')
                ->body('Manuel renkler için Gündüz/Gece Renkleri sekmelerini kullanın.')
                ->info()
                ->send();

            return;
        }

        if (! ThemePresets::has($presetId)) {
            Notification::make()->title('Geçersiz tema')->danger()->send();

            return;
        }

        app(ThemePresetService::class)->apply($presetId);
        $this->mount();

        $label = ThemePresets::get($presetId)['label'] ?? $presetId;

        Notification::make()
            ->title('Tema uygulandı')
            ->body("{$label} teması ön yüze yansıtıldı.")
            ->success()
            ->send();
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $heroData = $data['hero'] ?? [];
        unset($data['hero']);

        foreach ($data as $key => $value) {
            if (! in_array($key, $this->settingKeys(), true)) {
                continue;
            }

            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? ''),
                    'group' => str_starts_with($key, 'footer_') ? 'general' : 'design',
                    'type' => is_bool($value) ? 'boolean' : (str_contains($key, '_bg_') || str_starts_with($key, 'design_') && str_contains($key, '_primary') ? 'color' : 'text'),
                    'label' => $key,
                ]
            );
        }

        if ($heroData !== []) {
            if (isset($heroData['image']) && is_array($heroData['image'])) {
                $heroData['image'] = $heroData['image'][0] ?? null;
            }

            HeroSection::updateOrCreate(
                ['page' => 'home'],
                array_merge($heroData, ['page' => 'home']),
            );
        }

        SettingsService::clearCache();
        app(\App\Services\CacheInvalidator::class)->forDesignSaved();

        Notification::make()->title('Tasarım ayarları kaydedildi')->success()->send();
    }

    /** @return list<string> */
    protected function settingKeys(): array
    {
        $keys = [
            'design_theme_preset', 'design_font_family',
            'design_theme_mode', 'design_theme_toggle',
            'design_header_style', 'design_header_sticky', 'design_header_blur', 'design_header_border',
            'design_header_bg_light', 'design_header_bg_dark',
            'design_footer_style', 'design_footer_show_stats',
            'design_footer_bg_light', 'design_footer_bg_dark',
            'footer_text',
        ];

        foreach (['light', 'dark'] as $mode) {
            foreach (['primary', 'primary_hover', 'secondary', 'bg', 'surface', 'surface_elevated', 'text', 'text_muted', 'link', 'border'] as $token) {
                $keys[] = "design_{$mode}_{$token}";
            }
        }

        return $keys;
    }

    /** @return list<string> */
    protected function booleanKeys(): array
    {
        return [
            'design_theme_toggle', 'design_header_sticky', 'design_header_blur',
            'design_header_border', 'design_footer_show_stats',
        ];
    }
}
