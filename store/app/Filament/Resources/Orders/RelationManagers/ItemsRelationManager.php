<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\DomainName;
use App\Models\OrderItem;
use App\Services\Domain\DomainProvisioningService;
use App\Services\Panel\PanelProvisioningService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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
            ->recordActions([
                Action::make('retryDomainItem')
                    ->label('Tekrar dene')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (OrderItem $r): bool => ($r->item_type ?? '') === 'domain_register'
                        && $r->order?->payment_status === 'paid'
                        && $this->provisionLabel($r) !== 'Kayıtlı')
                    ->requiresConfirmation()
                    ->modalHeading('Alan adı kaydını yeniden dene')
                    ->modalDescription('Aynı sipariş üzerinden Spaceship kaydı tekrarlanır. Yeni sipariş oluşmaz.')
                    ->action(function (OrderItem $record, DomainProvisioningService $domains): void {
                        $order = $record->order;
                        if ($order === null) {
                            return;
                        }
                        $summary = $domains->retry($order);
                        $body = implode("\n", $summary['messages'] ?: ['—']);
                        Notification::make()
                            ->title($summary['succeeded'] > 0 ? 'Domain kaydı tamamlandı' : 'Domain kaydı')
                            ->body($body)
                            ->{$summary['succeeded'] > 0 ? 'success' : ($summary['failed'] > 0 ? 'danger' : 'info')}()
                            ->persistent()
                            ->send();
                    }),
                Action::make('retryHostingItem')
                    ->label('Tekrar dene')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (OrderItem $r): bool => in_array($r->item_type ?? '', ['hosting', 'manual', 'product'], true)
                        && $r->order?->payment_status === 'paid'
                        && in_array($r->order?->panel_provision_status, ['pending', 'failed', 'processing'], true))
                    ->requiresConfirmation()
                    ->modalHeading('Hosting kurulumunu yeniden dene')
                    ->modalDescription('Panel fulfill aynı store sipariş numarasıyla çalışır; çift panel siparişi oluşmaz.')
                    ->action(function (OrderItem $record, PanelProvisioningService $panel): void {
                        $order = $record->order;
                        if ($order === null) {
                            return;
                        }
                        $result = $panel->retry($order);
                        Notification::make()
                            ->title($result['ok'] ? 'Hosting kurulumu' : 'Kurulum başlatılamadı')
                            ->body($result['message'])
                            ->{$result['ok'] ? 'success' : 'danger'}()
                            ->persistent()
                            ->send();
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
