<?php

namespace App\Filament\Resources\SiteSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('group')
            ->columns([
                TextColumn::make('label')->label('Etiket')->searchable()->placeholder('—'),
                TextColumn::make('key')->label('Anahtar')->searchable()->sortable(),
                TextColumn::make('group')->label('Grup')->badge()->sortable(),
                TextColumn::make('value')->label('Değer')->limit(40)->placeholder('—'),
                TextColumn::make('type')->label('Tip')->badge()->toggleable(),
            ])
            ->filters([])
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
