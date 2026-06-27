<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sipariş')->schema([
                TextInput::make('order_number')->label('Sipariş No')->disabled(),
                Select::make('status')->label('Durum')->options([
                    'pending' => 'Beklemede',
                    'processing' => 'İşleniyor',
                    'completed' => 'Tamamlandı',
                    'cancelled' => 'İptal',
                ]),
                Select::make('payment_status')->label('Ödeme Durumu')->options([
                    'pending' => 'Beklemede',
                    'awaiting_transfer' => 'Havale Bekleniyor',
                    'paid' => 'Ödendi',
                    'failed' => 'Başarısız',
                ])->disabled(fn (?string $state): bool => $state === 'paid'),
                TextInput::make('total')->label('Toplam')->prefix('₺')->disabled(),
                TextInput::make('payment_reference')->label('Ödeme Referansı'),
                Textarea::make('notes')->label('Notlar')->columnSpanFull(),
            ])->columns(2),

            Section::make('Panelze')->schema([
                TextInput::make('panel_order_number')->label('Panel Sipariş No')->disabled(),
                TextInput::make('panel_provision_status')->label('Panel Durumu')->disabled(),
                Textarea::make('panel_provision_error')->label('Panel Hata')->disabled()->columnSpanFull(),
            ])->columns(2)->collapsed(),

            Section::make('Müşteri')->schema([
                TextInput::make('customer_name')->label('Ad Soyad')->disabled(),
                TextInput::make('customer_email')->label('E-posta')->disabled(),
                TextInput::make('customer_phone')->label('Telefon')->disabled(),
                TextInput::make('customer_company')->label('Şirket')->disabled(),
                Textarea::make('customer_address')->label('Adres')->disabled()->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
