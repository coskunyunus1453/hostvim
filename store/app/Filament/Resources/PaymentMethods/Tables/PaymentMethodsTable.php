<?php

namespace App\Filament\Resources\PaymentMethods\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Ad')->searchable()->sortable(),
                TextColumn::make('code')->label('Sağlayıcı')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paytr' => 'PayTR',
                        'iyzico' => 'iyzico',
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'payoneer' => 'Payoneer',
                        'bank_transfer' => 'Havale',
                        default => $state,
                    }),
                TextColumn::make('description')->label('Açıklama')->limit(40)->toggleable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('sort_order')->label('Sıra')->sortable(),
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
