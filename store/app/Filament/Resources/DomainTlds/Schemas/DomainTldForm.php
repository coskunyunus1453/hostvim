<?php

namespace App\Filament\Resources\DomainTlds\Schemas;

use App\Services\Domain\Registrar\DomainRegistrarResolver;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DomainTldForm
{
    public static function configure(Schema $schema): Schema
    {
        $resolver = app(DomainRegistrarResolver::class);
        $apiOptions = ['' => 'Otomatik (en ucuz API)'];
        foreach ($resolver->apiNames() as $apiName) {
            $apiOptions[$apiName] = $resolver->providerLabel($apiName).' ('.$apiName.')';
        }

        return $schema->components([
            Section::make('Uzantı')->schema([
                TextInput::make('tld')
                    ->label('TLD')
                    ->required()
                    ->placeholder('.com')
                    ->helperText('Nokta ile başlamalı: .com, .com.tr')
                    ->unique(ignoreRecord: true),
                Select::make('registrar_api_name')
                    ->label('Domain API (kayıt sağlayıcısı)')
                    ->options($apiOptions)
                    ->helperText('Kaydı hangi API üzerinden yapılacağı. Fiyatla ilgisi yok.'),
                Toggle::make('is_active')->label('Satışta')->default(true),
                TextInput::make('sort_order')->label('Sıralama')->numeric()->default(0),
            ])->columns(2),

            Section::make('Fiyatlandırma')
                ->description('Otomatik mod açıkken maliyeti girin; satış fiyatı kur ve kar marjı ile otomatik hesaplanır.')
                ->schema([
                    Toggle::make('auto_price')
                        ->label('Otomatik fiyat (maliyet + kur + kar marjı)')
                        ->helperText('Kapatırsanız satış fiyatlarını elle girebilirsiniz.')
                        ->default(true)
                        ->live()
                        ->columnSpanFull(),

                    TextInput::make('wholesale_register')
                        ->label('Maliyet — kayıt')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Registrar maliyeti (seçtiğiniz para biriminde).')
                        ->required(fn (Get $get): bool => (bool) $get('auto_price'))
                        ->visible(fn (Get $get): bool => (bool) $get('auto_price')),
                    TextInput::make('wholesale_renew')
                        ->label('Maliyet — yenileme')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Boş bırakılırsa kayıt maliyeti kullanılır.')
                        ->visible(fn (Get $get): bool => (bool) $get('auto_price')),
                    Select::make('wholesale_currency')
                        ->label('Maliyet para birimi')
                        ->options([
                            'USD' => 'USD ($)',
                            'TRY' => 'TRY (₺)',
                            'EUR' => 'EUR (€)',
                            'GBP' => 'GBP (£)',
                        ])
                        ->default('USD')
                        ->required(fn (Get $get): bool => (bool) $get('auto_price'))
                        ->visible(fn (Get $get): bool => (bool) $get('auto_price')),
                    TextInput::make('markup_percent')
                        ->label('Kar marjı (%)')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Boş = genel ayarlardaki varsayılan marj.'),

                    TextInput::make('register_price')
                        ->label('Satış fiyatı — kayıt (₺)')
                        ->numeric()
                        ->prefix('₺')
                        ->required(fn (Get $get): bool => ! $get('auto_price'))
                        ->disabled(fn (Get $get): bool => (bool) $get('auto_price'))
                        ->dehydrated()
                        ->helperText(fn (Get $get): ?string => $get('auto_price') ? 'Kaydedince otomatik hesaplanır.' : null),
                    TextInput::make('renew_price')
                        ->label('Satış fiyatı — yenileme (₺)')
                        ->numeric()
                        ->prefix('₺')
                        ->required(fn (Get $get): bool => ! $get('auto_price'))
                        ->disabled(fn (Get $get): bool => (bool) $get('auto_price'))
                        ->dehydrated()
                        ->helperText(fn (Get $get): ?string => $get('auto_price') ? 'Kaydedince otomatik hesaplanır.' : null),
                    TextInput::make('transfer_price')
                        ->label('Transfer fiyatı (₺)')
                        ->numeric()
                        ->prefix('₺'),
                ])->columns(2),
        ]);
    }
}
