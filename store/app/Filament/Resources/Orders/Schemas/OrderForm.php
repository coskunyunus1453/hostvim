<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\DomainName;
use App\Models\Order;
use App\Services\Domain\DomainProvisioningService;
use App\Services\Panel\PanelProvisioningService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sipariş')->schema([
                TextInput::make('order_number')->label('Sipariş No')->disabled(),
                Select::make('status')->label('Durum')->options([
                    'pending' => 'Beklemede',
                    'processing' => 'İşleniyor',
                    'completed' => 'Tamamlandı',
                    'cancelled' => 'İptal',
                ]),
                Select::make('payment_status')->label('Ödeme Durumu')->options([
                    'pending' => 'Beklemede',
                    'awaiting_transfer' => 'Havale Bekleniyor',
                    'paid' => 'Ödendi',
                    'failed' => 'Başarısız',
                ])->disabled(fn (?string $state): bool => $state === 'paid'),
                TextInput::make('paymentMethod.name')->label('Ödeme Yöntemi')->disabled(),
                TextInput::make('subtotal')->label('Ara Toplam')->prefix('₺')->disabled(),
                TextInput::make('discount_amount')->label('İndirim')->prefix('₺')->disabled()
                    ->helperText(fn ($record): ?string => $record?->coupon_code ? 'Kupon: '.$record->coupon_code : null),
                TextInput::make('total')->label('Toplam')->prefix('₺')->disabled(),
                TextInput::make('payment_reference')->label('Ödeme Referansı'),
                Textarea::make('notes')->label('Notlar')->columnSpanFull(),
            ])->columns(2),

            Section::make('Alan Adı Kaydı')
                ->description('Spaceship bakiyesi bittiğinde veya kayıt hata verdiğinde aynı siparişte yeniden deneyin — yeni sipariş oluşmaz.')
                ->schema([
                    Placeholder::make('domain_provision_summary')
                        ->label('Domain durumu')
                        ->content(fn (?Order $record): string => self::domainSummary($record))
                        ->columnSpanFull(),
                ])
                ->headerActions([
                    Action::make('retryDomainProvisionInline')
                        ->label('Tekrar dene')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (?Order $record): bool => self::canRetryDomain($record))
                        ->requiresConfirmation()
                        ->modalHeading('Alan adı kaydını yeniden dene')
                        ->modalDescription('Spaceship üzerinden aynı siparişe bağlı alan adları yeniden kaydedilir. Zaten kayıtlı olanlar atlanır (çift ücret / çift sipariş yok). Bakiyeniz yetersizse yine başarısız olur.')
                        ->action(function (?Order $record, DomainProvisioningService $domains): void {
                            if ($record === null) {
                                return;
                            }
                            $summary = $domains->retry($record);
                            self::notifyDomainRetry($summary);
                        }),
                ])
                ->columns(1)
                ->collapsed(fn (?Order $record): bool => ! self::canRetryDomain($record))
                ->visible(fn (?Order $record): bool => self::orderHasDomainItems($record)),

            Section::make('Panelze (Hosting Kurulumu)')
                ->description('Kurulum hatasında aynı sipariş numarasıyla yeniden deneyin — panelde çift sipariş oluşmaz.')
                ->schema([
                    TextInput::make('panel_order_number')->label('Panel Sipariş No')->disabled(),
                    TextInput::make('panel_provision_status')->label('Panel Durumu')->disabled(),
                    Textarea::make('panel_provision_error')->label('Panel Hata')->disabled()->columnSpanFull(),
                ])
                ->headerActions([
                    Action::make('retryPanelProvisionInline')
                        ->label('Tekrar dene')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (?Order $record): bool => self::canRetryPanel($record))
                        ->requiresConfirmation()
                        ->modalHeading('Hosting / panel kurulumunu yeniden dene')
                        ->modalDescription('Önce panelde mevcut sipariş aranır (çift kurulum yok). Yoksa aynı store sipariş numarasıyla yeniden fulfill edilir.')
                        ->action(function (?Order $record, PanelProvisioningService $panel): void {
                            if ($record === null) {
                                return;
                            }
                            $result = $panel->retry($record);
                            Notification::make()
                                ->title($result['ok'] ? 'Hosting kurulumu' : 'Kurulum başlatılamadı')
                                ->body($result['message'])
                                ->{$result['ok'] ? 'success' : 'danger'}()
                                ->persistent()
                                ->send();
                        }),
                ])
                ->columns(2)
                ->collapsed(fn (?Order $record): bool => ! self::canRetryPanel($record)),

            Section::make('Bulut Sunucu Kurulumu')->schema([
                TextInput::make('cloud_provision_status')->label('Bulut Durumu')->disabled(),
                TextInput::make('cloud_provisioned_at')->label('Kurulum Tarihi')->disabled(),
                Textarea::make('cloud_provision_error')->label('Bulut Hata')->disabled()->columnSpanFull(),
            ])->columns(2)->collapsed(),

            Section::make('Müşteri')->schema([
                TextInput::make('customer_name')->label('Ad Soyad')->disabled(),
                TextInput::make('customer_email')->label('E-posta')->disabled(),
                TextInput::make('customer_phone')->label('Telefon')->disabled(),
                TextInput::make('customer_company')->label('Şirket')->disabled(),
                Textarea::make('customer_address')->label('Adres')->disabled()->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    private static function orderHasDomainItems(?Order $order): bool
    {
        if ($order === null) {
            return false;
        }

        return $order->items()->where('item_type', 'domain_register')->exists();
    }

    private static function canRetryDomain(?Order $order): bool
    {
        if ($order === null || $order->payment_status !== 'paid') {
            return false;
        }

        $domains = $order->items()
            ->where('item_type', 'domain_register')
            ->pluck('domain_name')
            ->filter()
            ->map(fn ($d) => strtolower(trim((string) $d)))
            ->unique()
            ->values();

        if ($domains->isEmpty()) {
            return false;
        }

        $done = DomainName::query()
            ->whereIn('domain', $domains->all())
            ->whereIn('status', ['registered', 'active'])
            ->count();

        return $done < $domains->count();
    }

    private static function canRetryPanel(?Order $order): bool
    {
        if ($order === null || $order->payment_status !== 'paid') {
            return false;
        }

        return in_array($order->panel_provision_status, ['pending', 'failed', 'processing'], true);
    }

    private static function domainSummary(?Order $order): string
    {
        if ($order === null) {
            return '—';
        }

        $lines = [];
        foreach ($order->items()->where('item_type', 'domain_register')->get() as $item) {
            $domain = strtolower(trim((string) ($item->domain_name ?? '')));
            if ($domain === '') {
                continue;
            }
            $row = DomainName::query()->where('domain', $domain)->first();
            $status = $row?->status ?? 'yok';
            $err = is_array($row?->meta ?? null) ? ($row->meta['register_error'] ?? null) : null;
            $label = match ($status) {
                'registered', 'active' => 'Kayıtlı',
                'registering' => 'Kaydediliyor',
                'failed' => 'Başarısız',
                'yok' => 'Henüz oluşturulmadı',
                default => $status,
            };
            $line = "{$domain} — {$label}";
            if (is_string($err) && $err !== '') {
                $line .= ' | '.$err;
            }
            $lines[] = $line;
        }

        return $lines === [] ? 'Bu siparişte domain kalemi yok.' : implode("\n", $lines);
    }

    /**
     * @param  array{attempted: int, succeeded: int, failed: int, messages: list<string>}  $summary
     */
    private static function notifyDomainRetry(array $summary): void
    {
        $body = implode("\n", $summary['messages'] ?: ['İşlem yapılacak domain kalmadı.']);

        if ($summary['failed'] > 0 && $summary['succeeded'] === 0) {
            Notification::make()
                ->title('Domain kaydı başarısız')
                ->body($body."\n\nSpaceship bakiyesini kontrol edin.")
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        if ($summary['succeeded'] > 0) {
            Notification::make()
                ->title('Domain kaydı tamamlandı')
                ->body($body)
                ->success()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Domain kaydı')
            ->body($body)
            ->info()
            ->send();
    }
}
