<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\User;
use Filament\Actions\ViewAction;
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
                TextColumn::make('name')->label('Ad')->searchable()->sortable()->weight('bold'),
                TextColumn::make('email')->label('E-posta')->searchable()->copyable()
                    ->description(fn (User $r): ?string => $r->phone),
                IconColumn::make('panel_user_id')->label('Panel')->boolean()
                    ->getStateUsing(fn ($record): bool => $record->panel_user_id !== null)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle'),
                TextColumn::make('orders_count')->label('Sipariş')->counts('orders')->sortable()->badge()->color('info'),
                TextColumn::make('paid_total')->label('Harcama')
                    ->state(fn (User $r): float => (float) $r->orders()->where('payment_status', 'paid')->sum('total'))
                    ->money('TRY')->weight('bold'),
                TextColumn::make('created_at')->label('Kayıt')->dateTime('d.m.Y')->sortable(),
            ])
            ->recordUrl(fn (User $record): string => CustomerResource::getUrl('view', ['record' => $record]))
            ->recordActions([ViewAction::make()->label('Detay')]);
    }
}
