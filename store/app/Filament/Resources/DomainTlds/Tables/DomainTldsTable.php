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
                TextColumn::make('wholesale_register')
                    ->label('Maliyet')
                    ->formatStateUsing(fn ($state, $record) => $state > 0 ? number_format((float) $state, 2).' '.($record->wholesale_currency ?: 'USD') : '—')
                    ->toggleable(),
                TextColumn::make('register_price')->label('Satış (kayıt)')->money('TRY')->sortable(),
                TextColumn::make('renew_price')->label('Satış (yenileme)')->money('TRY'),
                TextColumn::make('markup_percent')
                    ->label('Marj')
                    ->formatStateUsing(fn ($state) => $state !== null ? rtrim(rtrim((string) $state, '0'), '.').'%' : 'varsayılan')
                    ->toggleable(),
                IconColumn::make('auto_price')->label('Otomatik')->boolean()->toggleable(),
                TextColumn::make('registrar_api_name')
                    ->label('API')
                    ->formatStateUsing(fn (?string $state, $record) => $state ?: ($record->wholesale_registrar_api ?: '—'))
                    ->badge()
                    ->toggleable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('prices_synced_at')->label('Hesaplandı')->dateTime('d.m.Y H:i')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
