<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->label('Sipariş No')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Müşteri')->searchable(),
                TextColumn::make('customer_email')->label('E-posta')->searchable(),
                TextColumn::make('total')->label('Toplam')->money('TRY')->sortable(),
                TextColumn::make('status')->label('Durum')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'info',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('payment_status')->label('Ödeme')->badge(),
                TextColumn::make('panel_provision_status')->label('Panel')->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'info',
                        'failed' => 'danger',
                        'skipped' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('panel_order_number')->label('Panel Sipariş')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paymentMethod.name')->label('Yöntem'),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Durum')->options([
                    'pending' => 'Beklemede',
                    'processing' => 'İşleniyor',
                    'completed' => 'Tamamlandı',
                    'cancelled' => 'İptal',
                ]),
                SelectFilter::make('payment_status')->label('Ödeme Durumu')->options([
                    'pending' => 'Beklemede',
                    'awaiting_transfer' => 'Havale Bekleniyor',
                    'paid' => 'Ödendi',
                    'failed' => 'Başarısız',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
