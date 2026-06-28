<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('items'))
            ->columns([
                TextColumn::make('order_number')->label('Sipariş No')->searchable()->sortable()->weight('bold'),
                TextColumn::make('customer_name')->label('Müşteri')->searchable()
                    ->description(fn (Order $r): ?string => $r->customer_email),
                TextColumn::make('items_summary')
                    ->label('İçerik')
                    ->state(fn (Order $r): string => self::itemsSummary($r))
                    ->wrap()
                    ->tooltip(fn (Order $r): ?string => self::itemsTooltip($r)),
                TextColumn::make('total')->label('Toplam')->money('TRY')->sortable()->alignEnd()->weight('bold'),
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
                        'pending' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('panel_provision_status')->label('Panel')->badge()->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => self::provisionLabel($state))
                    ->color(fn (?string $state): string => self::provisionColor($state))
                    ->toggleable(),
                TextColumn::make('cloud_provision_status')->label('Bulut')->badge()->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => self::provisionLabel($state))
                    ->color(fn (?string $state): string => self::provisionColor($state))
                    ->toggleable(),
                TextColumn::make('panel_order_number')->label('Panel Sipariş')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paymentMethod.name')->label('Yöntem')->placeholder('—')->toggleable(),
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
                Filter::make('provision_failed')
                    ->label('Kurulumu başarısız')
                    ->query(fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q
                        ->where('panel_provision_status', 'failed')
                        ->orWhere('cloud_provision_status', 'failed')))
                    ->toggle(),
                Filter::make('has_domain')
                    ->label('Alan adı içerir')
                    ->query(fn (Builder $query): Builder => $query->whereHas('items', fn (Builder $q) => $q->where('item_type', 'domain_register')))
                    ->toggle(),
            ])
            ->recordActions([EditAction::make()->label('Detay')])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
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

    private static function itemsTooltip(Order $order): ?string
    {
        $items = $order->items;
        if ($items->isEmpty()) {
            return null;
        }

        return $items
            ->map(fn ($i) => trim(($i->quantity > 1 ? $i->quantity.'× ' : '').($i->domain_name ?: $i->product_name)))
            ->implode("\n");
    }

    private static function provisionLabel(?string $state): string
    {
        return match ($state) {
            'completed' => 'Kuruldu',
            'processing' => 'Kuruluyor',
            'failed' => 'Başarısız',
            'pending' => 'Bekliyor',
            'skipped' => 'Yok',
            default => '—',
        };
    }

    private static function provisionColor(?string $state): string
    {
        return match ($state) {
            'completed' => 'success',
            'processing' => 'info',
            'failed' => 'danger',
            'pending' => 'warning',
            default => 'gray',
        };
    }
}
