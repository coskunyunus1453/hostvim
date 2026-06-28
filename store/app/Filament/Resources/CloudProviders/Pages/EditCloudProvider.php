<?php

namespace App\Filament\Resources\CloudProviders\Pages;

use App\Filament\Resources\CloudProviders\CloudProviderResource;
use App\Services\Cloud\CloudProvisioningService;
use App\Services\Cloud\Provider\CloudProviderResolver;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCloudProvider extends EditRecord
{
    protected static string $resource = CloudProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label('Bağlantıyı test et')
                ->action(function (CloudProviderResolver $resolver): void {
                    $result = $resolver->driver($this->record->api_name)->testConnection($this->record);
                    $this->record->update([
                        'last_tested_at' => now(),
                        'last_test_status' => $result['ok'] ? 'ok' : 'error',
                        'last_test_message' => $result['message'],
                    ]);
                    Notification::make()
                        ->title($result['ok'] ? 'Bağlantı başarılı' : 'Bağlantı başarısız')
                        ->body($result['message'])
                        ->{$result['ok'] ? 'success' : 'danger'}()
                        ->send();
                }),

            Action::make('syncServers')
                ->label('Sunucuları senkronize et')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Sağlayıcıdaki tüm sunucular çekilip panele aktarılır (IP/durum güncellenir).')
                ->action(function (CloudProvisioningService $provisioning): void {
                    if (! $this->record->is_enabled || ! $this->record->isConfigured()) {
                        Notification::make()
                            ->title('Senkron yapılamadı')
                            ->body('Sağlayıcı aktif değil veya API bilgileri eksik.')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $result = $provisioning->syncServers($this->record);
                        Notification::make()
                            ->title('Senkron tamamlandı')
                            ->body("Toplam {$result['total']} sunucu — yeni: {$result['imported']}, güncellenen: {$result['updated']}.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Senkron başarısız')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
