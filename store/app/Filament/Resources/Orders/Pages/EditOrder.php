<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\DomainName;
use App\Services\Domain\DomainProvisioningService;
use App\Services\Panel\PanelProvisioningService;
use App\Services\TemplatedMailService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirmBankTransfer')
                ->label('Havaleyi onayla')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn () => $this->record->payment_status === 'awaiting_transfer')
                ->requiresConfirmation()
                ->modalHeading('Havale ödemesini onayla')
                ->modalDescription('Ödeme alındı olarak işaretlenecek ve hosting/domain provizyonu başlayacaktır.')
                ->action(function (TemplatedMailService $mail): void {
                    $order = $this->record;
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                    ]);

                    $mail->send('payment-received', $order->customer_email, [
                        'customer_name' => $order->customer_name,
                        'order_number' => $order->order_number,
                        'total' => number_format((float) $order->total, 2, ',', '.'),
                    ]);

                    $this->refreshFormData(['payment_status', 'status']);
                }),
            Action::make('retryPanelProvision')
                ->label('Hosting: Tekrar dene')
                ->icon('heroicon-o-server-stack')
                ->color('warning')
                ->visible(fn () => $this->record->payment_status === 'paid'
                    && in_array($this->record->panel_provision_status, ['pending', 'failed', 'processing'], true))
                ->requiresConfirmation()
                ->modalHeading('Hosting / panel kurulumunu yeniden dene')
                ->modalDescription('Aynı sipariş numarasıyla devam edilir; panelde çift sipariş oluşmaz. Önce mevcut panel kaydı kontrol edilir.')
                ->action(function (PanelProvisioningService $panel): void {
                    $result = $panel->retry($this->record);
                    Notification::make()
                        ->title($result['ok'] ? 'Hosting kurulumu' : 'Kurulum başlatılamadı')
                        ->body($result['message'])
                        ->{$result['ok'] ? 'success' : 'danger'}()
                        ->persistent()
                        ->send();
                    $this->refreshFormData([
                        'panel_provision_status',
                        'panel_order_number',
                        'panel_provision_error',
                        'status',
                    ]);
                    $this->record->refresh();
                }),
            Action::make('retryDomainProvision')
                ->label('Domain: Tekrar dene')
                ->icon('heroicon-o-globe-alt')
                ->color('info')
                ->visible(fn () => $this->record->payment_status === 'paid' && $this->hasPendingDomains())
                ->requiresConfirmation()
                ->modalHeading('Alan adı kaydını yeniden dene')
                ->modalDescription('Spaceship bakiyeniz varsa kayıt tamamlanır ve müşteriye tanımlanır. Zaten kayıtlı domainler atlanır (çift sipariş / çift ücret yok).')
                ->action(function (DomainProvisioningService $domains): void {
                    $summary = $domains->retry($this->record);
                    $body = implode("\n", $summary['messages'] ?: ['İşlem yapılacak domain yok.']);
                    if ($summary['failed'] > 0 && $summary['succeeded'] === 0) {
                        Notification::make()
                            ->title('Domain kaydı başarısız')
                            ->body($body."\n\nSpaceship bakiyesini kontrol edin.")
                            ->danger()
                            ->persistent()
                            ->send();
                    } elseif ($summary['succeeded'] > 0) {
                        Notification::make()
                            ->title('Domain kaydı tamamlandı')
                            ->body($body)
                            ->success()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()->title('Domain kaydı')->body($body)->info()->send();
                    }
                    $this->record->refresh();
                }),
            DeleteAction::make(),
        ];
    }

    private function hasPendingDomains(): bool
    {
        $domains = $this->record->items()
            ->where('item_type', 'domain_register')
            ->pluck('domain_name')
            ->filter()
            ->map(fn ($d) => strtolower(trim((string) $d)));

        if ($domains->isEmpty()) {
            return false;
        }

        $registered = DomainName::query()
            ->whereIn('domain', $domains->all())
            ->whereIn('status', ['registered', 'active'])
            ->count();

        return $registered < $domains->count();
    }
}
