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
            Action::make('retryDomainProvision')
                ->label('Domain kaydını yeniden dene')
                ->icon('heroicon-o-globe-alt')
                ->color('info')
                ->visible(fn () => $this->record->payment_status === 'paid' && $this->hasPendingDomains())
                ->requiresConfirmation()
                ->modalHeading('Alan adı kaydını yeniden dene')
                ->modalDescription('Siparişteki alan adları sağlayıcıda (Spaceship) yeniden kaydedilmeye çalışılır. Zaten kayıtlı olanlar atlanır. Kayıt ücreti Spaceship bakiyenizden düşer.')
                ->action(function (DomainProvisioningService $domains): void {
                    $domains->process($this->record);
                    Notification::make()
                        ->title('Domain kaydı tetiklendi')
                        ->body('Sonucu "Alan Adları" ekranından kontrol edebilirsiniz. Başarısızsa Spaceship bakiyenizi kontrol edin.')
                        ->success()
                        ->send();
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
