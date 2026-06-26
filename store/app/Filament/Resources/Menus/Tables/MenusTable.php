<?php

namespace App\Filament\Resources\Menus\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Menü')->searchable(),
                TextColumn::make('location')
                    ->label('Konum')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'header' => 'Üst Menü',
                        'footer' => 'Alt Menü',
                        default => $state,
                    })
                    ->badge(),
                TextColumn::make('items_count')
                    ->label('Öğe sayısı')
                    ->counts('items'),
            ])
            ->defaultSort('location')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
