<?php

namespace App\Filament\Resources\DomainNames\Pages;

use App\Filament\Resources\DomainNames\DomainNameResource;
use App\Services\Domain\DomainManagementService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDomainNames extends ListRecords
{
    protected static string $resource = DomainNameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncDomains')
                ->label('Domainleri senkronize et')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalDescription('Gerçek domain yönetimini destekleyen sağlayıcılardan (Spaceship) tüm domainler çekilir.')
                ->action(function (DomainManagementService $service): void {
                    try {
                        $apis = $service->manageableApis();
                        if ($apis === []) {
                            Notification::make()
                                ->title('Senkron yapılamadı')
                                ->body('Gerçek domain yönetimini destekleyen aktif sağlayıcı yok (Spaceship API anahtarını ekleyin).')
                                ->warning()
                                ->send();

                            return;
                        }
                        $result = $service->syncAll();
                        Notification::make()
                            ->title('Senkron tamamlandı')
                            ->body("Toplam {$result['total']} domain — yeni: {$result['imported']}, güncellenen: {$result['updated']}.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Senkron başarısız')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
