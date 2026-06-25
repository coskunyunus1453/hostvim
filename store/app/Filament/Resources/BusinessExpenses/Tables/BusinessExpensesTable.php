<?php

namespace App\Filament\Resources\BusinessExpenses\Tables;

use App\Models\BusinessExpense;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BusinessExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_date')->label('Tarih')->date('d.m.Y')->sortable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->formatStateUsing(fn (string $state) => BusinessExpense::CATEGORIES[$state] ?? $state)
                    ->badge(),
                TextColumn::make('title')->label('Başlık')->searchable()->limit(40),
                TextColumn::make('vendor')->label('Tedarikçi')->toggleable(),
                TextColumn::make('amount')->label('Tutar')->money('TRY')->sortable(),
                IconColumn::make('is_recurring')->label('Tekrar')->boolean(),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(BusinessExpense::CATEGORIES),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
