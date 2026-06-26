<?php

namespace App\Filament\Resources\DomainTlds\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainTldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tld')->label('TLD')->searchable()->sortable(),
                TextColumn::make('register_price')->label('Kayıt')->money('TRY')->sortable(),
                TextColumn::make('renew_price')->label('Yenileme')->money('TRY'),
                TextColumn::make('registrar_api_name')
                    ->label('API')
                    ->formatStateUsing(fn (?string $state, $record) => $state ?: ($record->wholesale_registrar_api ?: 'otomatik'))
                    ->badge(),
                TextColumn::make('wholesale_registrar_api')->label('En ucuz')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('prices_synced_at')->label('Senkron')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
