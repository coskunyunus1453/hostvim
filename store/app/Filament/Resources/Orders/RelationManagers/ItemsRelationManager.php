<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\DomainName;
use App\Models\OrderItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Sipariş Kalemleri';

    protected static ?string $modelLabel = 'kalem';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Sipariş Kalemleri — müşterinin aldığı ürün ve hizmetler')
            ->columns([
                TextColumn::make('item_type')
                    ->label('Tür')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'domain_register' => 'Alan Adı',
                        'hosting' => 'Hosting',
                        'cloud' => 'Bulut Sunucu',
                        'manual' => 'Manuel Hizmet',
                        'product' => 'Ürün',
                        default => $state ?: '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'domain_register' => 'info',
                        'hosting' => 'success',
                        'cloud' => 'warning',
                        'manual' => 'gray',
                        default => 'primary',
                    }),

                TextColumn::make('product_name')
                    ->label('Ürün / Hizmet')
                    ->description(fn (OrderItem $r): ?string => $r->domain_name
                        ? '🌐 '.$r->domain_name.($r->domain_years ? ' · '.$r->domain_years.' yıl' : '')
                        : ($r->service_domain ? '🔗 '.$r->service_domain : null))
                    ->weight('bold')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('billing_cycle')
                    ->label('Dönem')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'monthly' => 'Aylık',
                        'quarterly' => '3 Aylık',
                        'semiannual' => '6 Aylık',
                        'yearly' => 'Yıllık',
                        'biennial' => '2 Yıllık',
                        'triennial' => '3 Yıllık',
                        'onetime' => 'Tek Sefer',
                        default => $state ?: '—',
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('quantity')->label('Adet')->alignCenter(),

                TextColumn::make('unit_price')->label('Birim')->money('TRY')->alignEnd(),

                TextColumn::make('total')->label('Tutar')->money('TRY')->weight('bold')->alignEnd(),

                TextColumn::make('provision_state')
                    ->label('Durum')
                    ->badge()
                    ->state(fn (OrderItem $r): string => $this->provisionLabel($r))
                    ->color(fn (string $state): string => match ($state) {
                        'Kuruldu', 'Kayıtlı' => 'success',
                        'Kuruluyor', 'Kaydediliyor' => 'info',
                        'Başarısız' => 'danger',
                        'Bekliyor' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->paginated(false);
    }

    private function provisionLabel(OrderItem $item): string
    {
        $order = $item->order;
        $type = (string) ($item->item_type ?? '');

        if ($type === 'domain_register') {
            $status = $item->domain_name
                ? DomainName::query()->where('domain', strtolower(trim($item->domain_name)))->value('status')
                : null;

            return match ($status) {
                'registered', 'active' => 'Kayıtlı',
                'registering' => 'Kaydediliyor',
                'failed' => 'Başarısız',
                null => $order?->payment_status === 'paid' ? 'Bekliyor' : '—',
                default => (string) $status,
            };
        }

        if ($type === 'cloud') {
            return $this->statusLabel($order?->cloud_provision_status);
        }

        if (in_array($type, ['hosting', 'manual', 'product'], true)) {
            return $this->statusLabel($order?->panel_provision_status);
        }

        return '—';
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'completed' => 'Kuruldu',
            'processing' => 'Kuruluyor',
            'failed' => 'Başarısız',
            'pending' => 'Bekliyor',
            'skipped' => 'Atlandı',
            default => '—',
        };
    }
}
