<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrders extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Son Siparişler';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->latest()->limit(8))
            ->heading(static::$heading)
            ->columns([
                TextColumn::make('order_number')->label('No')->searchable(),
                TextColumn::make('customer_name')->label('Müşteri')->limit(20),
                TextColumn::make('total')->label('Tutar')->money('TRY'),
                TextColumn::make('payment_status')->label('Ödeme')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Ödendi',
                        'pending' => 'Bekliyor',
                        'awaiting_transfer' => 'Havale',
                        'failed' => 'Başarısız',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        'awaiting_transfer' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->label('Tarih')->since(),
            ])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record]))
            ->paginated(false);
    }
}
