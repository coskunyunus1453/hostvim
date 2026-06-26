<?php

namespace App\Filament\Resources\DomainTlds\Schemas;

use App\Services\Domain\Registrar\DomainRegistrarResolver;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
                    ->helperText('Boş bırakılırsa senkron sonrası en ucuz aktif API seçilir.'),
                Toggle::make('is_active')->label('Satışta')->default(true),
                TextInput::make('sort_order')->label('Sıralama')->numeric()->default(0),
            ])->columns(2),

            Section::make('Satış Fiyatları (TRY)')->schema([
                TextInput::make('register_price')->label('Kayıt fiyatı')->numeric()->required()->prefix('₺'),
                TextInput::make('renew_price')->label('Yenileme fiyatı')->numeric()->required()->prefix('₺'),
                TextInput::make('transfer_price')->label('Transfer fiyatı')->numeric()->prefix('₺'),
                TextInput::make('markup_percent')
                    ->label('Kar marjı (%)')
                    ->numeric()
                    ->helperText('Boş = genel ayarlardaki varsayılan marj kullanılır.'),
            ])->columns(2),

            Section::make('Toptan (API senkron)')->schema([
                TextInput::make('wholesale_register')->label('Toptan kayıt')->numeric()->disabled()->dehydrated(false)->prefix('₺'),
                TextInput::make('wholesale_renew')->label('Toptan yenileme')->numeric()->disabled()->dehydrated(false)->prefix('₺'),
                TextInput::make('wholesale_registrar_api')
                    ->label('En ucuz API')
                    ->disabled()
                    ->dehydrated(false),
            ])->columns(3)->collapsed(),
        ]);
    }
}
