<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('campaignTabs')->tabs([
                Tab::make('general')->label('Genel')->icon(Heroicon::OutlinedMegaphone)->schema([
                    Section::make()->schema([
                        TextInput::make('name')
                            ->label('Kampanya Adı (dahili)')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                        TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
                        TextInput::make('title')->label('Başlık (ön yüz)')->required()->columnSpanFull(),
                        Textarea::make('description')->label('Açıklama')->rows(3)->columnSpanFull(),
                        TextInput::make('badge_text')->label('Rozet metni')->placeholder('%30 İndirim')->helperText('Boşsa indirim değeri otomatik gösterilir'),
                        Toggle::make('is_active')->label('Aktif')->default(true),
                        TextInput::make('sort_order')->label('Öncelik sırası')->numeric()->default(0)->helperText('Düşük sayı önce gösterilir'),
                    ])->columns(2),
                ]),
                Tab::make('discount')->label('İndirim')->icon(Heroicon::OutlinedReceiptPercent)->schema([
                    Section::make()->schema([
                        Select::make('discount_type')
                            ->label('İndirim tipi')
                            ->options(['percent' => 'Yüzde (%)', 'fixed' => 'Sabit tutar (₺)'])
                            ->default('percent')
                            ->required()
                            ->live(),
                        TextInput::make('discount_value')
                            ->label('İndirim değeri')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->suffix(fn (Get $get) => $get('discount_type') === 'percent' ? '%' : '₺'),
                        TextInput::make('code')
                            ->label('Kupon kodu')
                            ->placeholder('YAZ30')
                            ->unique(ignoreRecord: true)
                            ->helperText('Ödeme sayfasında kullanılacak kod. Boş bırakılırsa otomatik indirim uygulanır.'),
                        Toggle::make('requires_code')
                            ->label('Kupon kodu zorunlu')
                            ->helperText('Açıksa indirim yalnızca kod girildiğinde ödemede uygulanır')
                            ->default(false),
                        TextInput::make('min_order')->label('Minimum sipariş (₺)')->numeric()->prefix('₺'),
                        TextInput::make('max_uses')->label('Maksimum kullanım')->numeric()->helperText('Boş = sınırsız'),
                    ])->columns(2),
                ]),
                Tab::make('target')->label('Hedefleme')->icon(Heroicon::OutlinedCursorArrowRays)->schema([
                    Section::make()->schema([
                        Select::make('applies_to')
                            ->label('Geçerli olduğu alan')
                            ->options([
                                'all' => 'Tüm ürünler',
                                'category' => 'Belirli kategoriler',
                                'product' => 'Belirli ürünler',
                            ])
                            ->default('all')
                            ->required()
                            ->live(),
                        Select::make('target_ids')
                            ->label(fn (Get $get) => match ($get('applies_to')) {
                                'category' => 'Kategoriler',
                                'product' => 'Ürünler',
                                default => 'Hedefler',
                            })
                            ->multiple()
                            ->options(function (Get $get) {
                                return match ($get('applies_to')) {
                                    'category' => ProductCategory::orderBy('name')->pluck('name', 'id'),
                                    'product' => Product::orderBy('name')->pluck('name', 'id'),
                                    default => [],
                                };
                            })
                            ->visible(fn (Get $get) => in_array($get('applies_to'), ['category', 'product'], true))
                            ->columnSpanFull(),
                        CheckboxList::make('billing_cycles')
                            ->label('Fatura dönemleri')
                            ->options([
                                'monthly' => 'Aylık',
                                'yearly' => 'Yıllık',
                                'onetime' => 'Tek seferlik',
                            ])
                            ->helperText('Hiçbiri seçilmezse tüm dönemlerde geçerli')
                            ->columnSpanFull(),
                    ])->columns(2),
                ]),
                Tab::make('display')->label('Görünüm')->icon(Heroicon::OutlinedComputerDesktop)->schema([
                    Section::make()->schema([
                        CheckboxList::make('display_modes')
                            ->label('Ön yüzde nerede gösterilsin?')
                            ->options([
                                'flash_bar' => 'Flaş indirim şeridi (üst banner)',
                                'popup' => 'Popup penceresi',
                                'pricing' => 'Fiyat kartlarında indirim rozeti',
                                'checkout' => 'Ödeme sayfasında kupon alanı',
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                        Toggle::make('show_countdown')->label('Geri sayım göster')->default(true),
                        DateTimePicker::make('starts_at')->label('Başlangıç')->seconds(false),
                        DateTimePicker::make('ends_at')->label('Bitiş')->seconds(false),
                        ColorPicker::make('bar_color')->label('Banner rengi')->helperText('Boşsa tema rengi kullanılır'),
                        TextInput::make('cta_text')->label('Buton metni')->placeholder('Hemen İncele'),
                        TextInput::make('cta_url')->label('Buton URL')->placeholder('/urunler'),
                        FileUpload::make('popup_image')->label('Popup görseli')->image()->directory('campaigns')->columnSpanFull(),
                    ])->columns(2),
                ]),
            ])->columnSpanFull(),
        ]);
    }
}
