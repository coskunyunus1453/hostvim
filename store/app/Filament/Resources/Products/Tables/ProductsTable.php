<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ürün')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Kategori')->sortable(),
                TextColumn::make('price_monthly')->label('Satış (Ay)')->money('TRY')->sortable(),
                TextColumn::make('cost_monthly')->label('Alış (Ay)')->money('TRY')->sortable()->toggleable(),
                TextColumn::make('margin_monthly')
                    ->label('Marj %')
                    ->state(fn (Product $record) => $record->estimatedMarginPercent('monthly'))
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : $state.'%')
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 30 ? 'success' : ($state >= 10 ? 'warning' : 'danger'))),
                IconColumn::make('is_popular')->label('Popüler')->boolean(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('sort_order')->label('Sıra')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
