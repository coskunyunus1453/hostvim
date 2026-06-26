<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class ManageSeoSettings extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Tasarım & Yapılandırma';

    protected static ?string $navigationLabel = 'SEO Ayarları';

    protected static ?string $title = 'SEO & Schema Ayarları';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $slug = 'seo-ayarlari';

  /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $keys = $this->settingKeys();
        $settings = SiteSetting::whereIn('key', $keys)->pluck('value', 'key')->toArray();

        foreach ($keys as $key) {
            $settings[$key] ??= '';
        }

        if (isset($settings['seo_sitemap_enabled'])) {
            $settings['seo_sitemap_enabled'] = filter_var($settings['seo_sitemap_enabled'], FILTER_VALIDATE_BOOLEAN);
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
            Section::make('Genel SEO')->schema([
                TextInput::make('seo_title_suffix')->label('Başlık Soneki')->placeholder(' | HostVim')->helperText('Sayfa başlıklarının sonuna eklenir'),
                Textarea::make('meta_description')->label('Varsayılan Meta Açıklama')->rows(3),
                TextInput::make('seo_default_keywords')->label('Varsayılan Anahtar Kelimeler')->placeholder('hosting, vps, domain'),
                TextInput::make('seo_google_verification')->label('Google Search Console Doğrulama Kodu'),
                TextInput::make('seo_bing_verification')->label('Bing Webmaster Doğrulama Kodu'),
                Toggle::make('seo_sitemap_enabled')->label('Sitemap Aktif')->default(true),
                Textarea::make('seo_robots_txt')->label('robots.txt Ek Kurallar')->rows(4)->helperText('Sitemap ve temel kurallar otomatik eklenir'),
            ])->columns(2),

            Section::make('Sayfa Başlıkları')->schema([
                TextInput::make('seo_home_title')->label('Ana Sayfa Başlığı'),
                Textarea::make('seo_home_description')->label('Ana Sayfa Açıklaması')->rows(2),
                TextInput::make('seo_products_title')->label('Ürünler Sayfası Başlığı'),
                Textarea::make('seo_products_description')->label('Ürünler Sayfası Açıklaması')->rows(2),
                TextInput::make('seo_blog_title')->label('Blog Sayfası Başlığı'),
                Textarea::make('seo_blog_description')->label('Blog Sayfası Açıklaması')->rows(2),
            ])->columns(2),

            Section::make('Open Graph & Sosyal')->schema([
                FileUpload::make('seo_default_og_image')->label('Varsayılan OG Görseli')->image()->directory('seo')->helperText('1200x630 önerilir'),
                TextInput::make('social_facebook')->label('Facebook URL'),
                TextInput::make('social_twitter')->label('Twitter/X URL'),
                TextInput::make('social_instagram')->label('Instagram URL'),
                TextInput::make('social_linkedin')->label('LinkedIn URL'),
            ])->columns(2),

            Section::make('Schema.org (Yapılandırılmış Veri)')->schema([
                TextInput::make('schema_org_name')->label('Kuruluş Adı'),
                TextInput::make('schema_org_url')->label('Kuruluş URL')->url(),
                FileUpload::make('schema_org_logo')->label('Kuruluş Logosu (Schema)')->image()->directory('seo'),
            ])->columns(2),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('seo-form')
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
                $value = $value[0] ?? '';
            }

            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? ''),
                    'group' => str_starts_with($key, 'schema_') ? 'seo' : (str_starts_with($key, 'social_') ? 'social' : 'seo'),
                    'type' => is_bool($value) ? 'boolean' : 'text',
                    'label' => $key,
                ]
            );
        }

        SettingsService::clearCache();
        app(\App\Services\CacheInvalidator::class)->forSeoSettingsSaved();

        Notification::make()->title('SEO ayarları kaydedildi')->success()->send();
    }

    /** @return list<string> */
    protected function settingKeys(): array
    {
        return [
            'seo_title_suffix', 'meta_description', 'seo_default_keywords',
            'seo_google_verification', 'seo_bing_verification',
            'seo_sitemap_enabled', 'seo_robots_txt',
            'seo_home_title', 'seo_home_description',
            'seo_products_title', 'seo_products_description',
            'seo_blog_title', 'seo_blog_description',
            'seo_default_og_image',
            'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin',
            'schema_org_name', 'schema_org_url', 'schema_org_logo',
        ];
    }
}
