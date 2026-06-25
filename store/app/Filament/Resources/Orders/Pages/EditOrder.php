<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\Panel\PanelProvisioningService;
use App\Services\TemplatedMailService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
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
                ->label('Panelde yeniden kur')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->payment_status === 'paid'
                    && ! in_array($this->record->panel_provision_status, ['completed', 'processing'], true))
                ->requiresConfirmation()
                ->action(function (PanelProvisioningService $panel): void {
                    $panel->retry($this->record);
                    $this->refreshFormData(['panel_provision_status', 'panel_order_number', 'panel_provision_error']);
                }),
            DeleteAction::make(),
        ];
    }
}
