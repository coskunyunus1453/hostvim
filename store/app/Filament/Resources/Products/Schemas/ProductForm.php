<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Services\Cloud\Provider\CloudProviderResolver;
use App\Services\Panel\PanelzeApiService;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Genel Bilgiler')->schema([
                Select::make('product_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label('Ürün Adı')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->label('URL Slug')->required()->unique(ignoreRecord: true),
                TextInput::make('short_description')->label('Kısa Açıklama')->columnSpanFull(),
                RichEditor::make('description')->label('Detaylı Açıklama')->columnSpanFull(),
            ])->columns(2),

            Section::make('Panelze Entegrasyonu')->schema([
                Select::make('provision_type')
                    ->label('Kurulum Tipi')
                    ->options([
                        'hosting' => 'Web Hosting (otomatik panel kurulumu)',
                        'cloud' => 'Bulut VPS (Hetzner/Vultr/DO/Linode — otomatik sunucu)',
                        'domain' => 'Domain (domain sayfasından satılır)',
                        'manual' => 'Manuel (Dedicated — ödeme sonrası destek talebi)',
                    ])
                    ->default('hosting')
                    ->required()
                    ->live()
                    ->helperText('Ürünün ödeme sonrası nasıl işleneceğini belirler.'),
                Select::make('panel_package_id')
                    ->label('Panel Hosting Paketi')
                    ->options(function () {
                        $api = app(PanelzeApiService::class);
                        if (! $api->isConfigured()) {
                            return [];
                        }

                        return collect($api->packages())->mapWithKeys(
                            fn (array $p) => [(int) $p['id'] => $p['name'].' (#'.$p['id'].')']
                        )->all();
                    })
                    ->searchable()
                    ->visible(fn ($get) => $get('provision_type') === 'hosting')
                    ->helperText('Paneldeki teknik paket eşlemesi. Fiyatlar burada (store) yönetilir.'),
            ])->columns(1),

            Section::make('Bulut VPS API Eşlemesi')
                ->description('Ödeme sonrası seçilen sağlayıcıda otomatik sunucu açılır.')
                ->schema([
                    Select::make('cloud_provider_api')
                        ->label('Bulut API (cloud api name)')
                        ->options(function () {
                            $resolver = app(CloudProviderResolver::class);
                            $out = [];
                            foreach ($resolver->apiNames() as $api) {
                                $out[$api] = $resolver->providerLabel($api).' ('.$api.')';
                            }

                            return $out;
                        })
                        ->searchable()
                        ->live()
                        ->required(fn ($get) => $get('provision_type') === 'cloud'),
                    Select::make('cloud_region')
                        ->label('Bölge / Lokasyon')
                        ->options(fn ($get) => config('cloud_providers.providers.'.$get('cloud_provider_api').'.default_regions', []))
                        ->searchable()
                        ->required(fn ($get) => $get('provision_type') === 'cloud'),
                    Select::make('cloud_plan')
                        ->label('Sunucu planı / boyutu')
                        ->options(fn ($get) => config('cloud_providers.providers.'.$get('cloud_provider_api').'.default_sizes', []))
                        ->searchable()
                        ->required(fn ($get) => $get('provision_type') === 'cloud'),
                    Select::make('cloud_image')
                        ->label('İşletim sistemi imajı')
                        ->options(fn ($get) => config('cloud_providers.providers.'.$get('cloud_provider_api').'.default_images', []))
                        ->searchable()
                        ->required(fn ($get) => $get('provision_type') === 'cloud'),
                ])
                ->columns(2)
                ->visible(fn ($get) => $get('provision_type') === 'cloud'),

            Section::make('Fiyatlandırma')->schema([
                TextInput::make('price_monthly')->label('Satış — Aylık')->numeric()->prefix('₺'),
                TextInput::make('price_quarterly')->label('Satış — 3 Aylık')->numeric()->prefix('₺'),
                TextInput::make('price_semiannual')->label('Satış — 6 Aylık')->numeric()->prefix('₺'),
                TextInput::make('price_yearly')->label('Satış — Yıllık')->numeric()->prefix('₺'),
                TextInput::make('price_biennial')->label('Satış — 2 Yıllık')->numeric()->prefix('₺'),
                TextInput::make('price_triennial')->label('Satış — 3 Yıllık')->numeric()->prefix('₺'),
                TextInput::make('price_onetime')->label('Satış — Tek Seferlik')->numeric()->prefix('₺'),
                TextInput::make('currency')->label('Para Birimi')->default('TRY')->maxLength(3),
            ])->columns(2),

            Section::make('Alış Maliyeti (Toptan)')->schema([
                TextInput::make('cost_monthly')->label('Alış — Aylık')->numeric()->prefix('₺')
                    ->helperText('Tedarikçiden ödediğiniz aylık maliyet.'),
                TextInput::make('cost_quarterly')->label('Alış — 3 Aylık')->numeric()->prefix('₺'),
                TextInput::make('cost_semiannual')->label('Alış — 6 Aylık')->numeric()->prefix('₺'),
                TextInput::make('cost_yearly')->label('Alış — Yıllık')->numeric()->prefix('₺'),
                TextInput::make('cost_biennial')->label('Alış — 2 Yıllık')->numeric()->prefix('₺'),
                TextInput::make('cost_triennial')->label('Alış — 3 Yıllık')->numeric()->prefix('₺'),
                TextInput::make('cost_onetime')->label('Alış — Tek Seferlik')->numeric()->prefix('₺'),
            ])->columns(2)
                ->description('Kârlılık raporları için zorunlu değil ama net kâr hesabı için girilmesi önerilir.'),

            Section::make('Özellikler & Teknik Detaylar')->schema([
                TagsInput::make('features')->label('Özellikler')->columnSpanFull(),
                KeyValue::make('specs')->label('Teknik Özellikler (RAM, CPU, Disk vb.)')->columnSpanFull(),
            ]),

            Section::make('Görünürlük & SEO')->schema([
                Toggle::make('is_popular')->label('Popüler Plan'),
                Toggle::make('is_active')->label('Aktif')->default(true),
                Toggle::make('no_index')->label('Arama motorlarında gizle (noindex)'),
                TextInput::make('sort_order')->label('Sıralama')->numeric()->default(0),
                TextInput::make('meta_title')->label('Meta Başlık'),
                TextInput::make('meta_description')->label('Meta Açıklama')->columnSpanFull(),
                TextInput::make('meta_keywords')->label('Anahtar Kelimeler')->columnSpanFull(),
                FileUpload::make('og_image')->label('OG Görseli')->image()->directory('seo/products'),
            ])->columns(2),
        ]);
    }
}
