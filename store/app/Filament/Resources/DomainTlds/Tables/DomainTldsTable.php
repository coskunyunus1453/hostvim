<?php

namespace App\Filament\Resources\DomainTlds\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DomainTldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tld')->label('TLD')->searchable()->sortable()->weight('bold'),

                // ALIS (maliyet) - satir ici duzenlenebilir
                TextInputColumn::make('wholesale_register')
                    ->label('Alış (maliyet)')
                    ->type('number')
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->placeholder('—')
                    ->tooltip('Registrar maliyeti (alış). Para birimi yan sütunda.'),
                SelectColumn::make('wholesale_currency')
                    ->label('Para')
                    ->options(['USD' => 'USD', 'TRY' => 'TRY', 'EUR' => 'EUR', 'GBP' => 'GBP'])
                    ->selectablePlaceholder(false),

                // Marj - satir ici
                TextInputColumn::make('markup_percent')
                    ->label('Marj %')
                    ->type('number')
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->placeholder('varsayılan')
                    ->tooltip('Boş = genel ayardaki varsayılan marj'),

                // Otomatik fiyat acik/kapali - satir ici
                ToggleColumn::make('auto_price')
                    ->label('Oto')
                    ->tooltip('Açık: satış = alış × kur × (1+marj) otomatik. Kapalı: satışı elle gir.'),

                // SATIS (kayit) - satir ici duzenlenebilir
                TextInputColumn::make('register_price')
                    ->label('Satış (kayıt ₺)')
                    ->type('number')
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->tooltip('Müşteriye satış fiyatı (₺). Oto açıkken kaydedince otomatik hesaplanır.'),
                TextInputColumn::make('renew_price')
                    ->label('Satış (yenileme ₺)')
                    ->type('number')
                    ->rules(['nullable', 'numeric', 'min:0']),

                ToggleColumn::make('is_active')->label('Satışta'),

                TextColumn::make('prices_synced_at')
                    ->label('Hesaplandı')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
