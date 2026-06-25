<?php

namespace App\Filament\Resources\DomainRegistrars\Pages;

use App\Filament\Resources\DomainRegistrars\DomainRegistrarResource;
use App\Services\Domain\DomainPricingSyncService;
use App\Services\Domain\Registrar\DomainRegistrarResolver;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDomainRegistrar extends EditRecord
{
    protected static string $resource = DomainRegistrarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label('Bağlantıyı test et')
                ->action(function (): void {
                    $resolver = app(DomainRegistrarResolver::class);
                    $result = $resolver->driver($this->record->api_name)->testConnection($this->record);
                    Notification::make()
                        ->title($result['ok'] ? 'Bağlantı başarılı' : 'Bağlantı başarısız')
                        ->body($result['message'])
                        ->{$result['ok'] ? 'success' : 'danger'}()
                        ->send();
                }),
            Action::make('sync')
                ->label('Fiyatları senkronize et')
                ->requiresConfirmation()
                ->action(function (DomainPricingSyncService $sync): void {
                    $result = $sync->syncRegistrar($this->record);
                    $body = "Güncellenen: {$result['updated']}, yeni: {$result['created']}";
                    if ($result['errors'] !== []) {
                        $body .= "\n".implode("\n", $result['errors']);
                    }
                    Notification::make()
                        ->title('Senkron tamamlandı')
                        ->body($body)
                        ->success()
                        ->send();
                }),
        ];
    }
}
