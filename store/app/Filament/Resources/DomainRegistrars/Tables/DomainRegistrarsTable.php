<?php

namespace App\Filament\Resources\DomainRegistrars\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainRegistrarsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('api_name')->label('API Adı')->badge()->searchable(),
                TextColumn::make('display_name')->label('Platform')->searchable(),
                TextColumn::make('catalog_tagline')
                    ->label('Öne çıkan özellik')
                    ->state(fn ($record) => config('domain_registrars.providers.'.$record->api_name.'.tagline', '—'))
                    ->wrap(),
                IconColumn::make('is_enabled')->label('Aktif')->boolean(),
                IconColumn::make('configured')
                    ->label('Yapılandırıldı')
                    ->boolean()
                    ->state(fn ($record) => $record->isConfigured()),
                TextColumn::make('last_synced_at')->label('Son senkron')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()]);
    }
}
