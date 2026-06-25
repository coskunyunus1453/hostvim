<?php

namespace App\Filament\Resources\Campaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Kampanya')->searchable()->sortable(),
                TextColumn::make('discount_value')
                    ->label('İndirim')
                    ->formatStateUsing(fn ($record) => $record->discountLabel()),
                TextColumn::make('code')->label('Kod')->placeholder('—')->copyable(),
                TextColumn::make('display_modes')
                    ->label('Görünüm')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'flash_bar' => 'Flaş',
                        'popup' => 'Popup',
                        'pricing' => 'Fiyat',
                        'checkout' => 'Kupon',
                        default => $state,
                    }),
                TextColumn::make('ends_at')->label('Bitiş')->dateTime('d.m.Y H:i')->placeholder('Süresiz'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('used_count')->label('Kullanım')->suffix(fn ($record) => $record->max_uses ? ' / ' . $record->max_uses : ''),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
