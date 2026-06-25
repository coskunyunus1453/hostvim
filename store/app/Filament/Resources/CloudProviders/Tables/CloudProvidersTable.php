<?php

namespace App\Filament\Resources\CloudProviders\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CloudProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('api_name')->label('API Adı')->badge(),
                TextColumn::make('display_name')->label('Platform'),
                TextColumn::make('tagline')
                    ->label('Özellik')
                    ->state(fn ($record) => config('cloud_providers.providers.'.$record->api_name.'.tagline', '—'))
                    ->wrap(),
                IconColumn::make('is_enabled')->label('Aktif')->boolean(),
                IconColumn::make('configured')->label('Yapılandırıldı')->boolean()
                    ->state(fn ($record) => $record->isConfigured()),
                TextColumn::make('last_tested_at')->label('Son test')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()]);
    }
}
