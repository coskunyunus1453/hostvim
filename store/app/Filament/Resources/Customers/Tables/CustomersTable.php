<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable()->sortable(),
                TextColumn::make('email')->label('E-posta')->searchable()->copyable(),
                TextColumn::make('phone')->label('Telefon')->placeholder('—')->toggleable(),
                IconColumn::make('panel_user_id')->label('Panel')->boolean()
                    ->getStateUsing(fn ($record): bool => $record->panel_user_id !== null)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle'),
                TextColumn::make('orders_count')->label('Sipariş')->counts('orders')->sortable(),
                TextColumn::make('created_at')->label('Kayıt')->dateTime('d.m.Y')->sortable(),
            ]);
    }
}
