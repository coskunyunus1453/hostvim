<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Siparişler';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->label('Sipariş No')->weight('bold')->searchable(),
                TextColumn::make('items_summary')
                    ->label('İçerik')
                    ->state(fn (Order $r): string => self::itemsSummary($r))
                    ->wrap(),
                TextColumn::make('total')->label('Tutar')->money('TRY')->alignEnd(),
                TextColumn::make('status')->label('Durum')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Tamamlandı',
                        'processing' => 'İşleniyor',
                        'pending' => 'Beklemede',
                        'cancelled' => 'İptal',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'info',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('payment_status')->label('Ödeme')->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'paid' => 'Ödendi',
                        'awaiting_transfer' => 'Havale Bekleniyor',
                        'pending' => 'Beklemede',
                        'failed' => 'Başarısız',
                        default => (string) $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'awaiting_transfer' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record]))
            ->paginated([10, 25]);
    }

    private static function itemsSummary(Order $order): string
    {
        $items = $order->items;
        if ($items->isEmpty()) {
            return '—';
        }

        $first = $items->first();
        $label = $first->domain_name ?: $first->product_name ?: 'Kalem';
        $rest = $items->count() - 1;

        return $rest > 0 ? $label.' +'.$rest : $label;
    }
}
